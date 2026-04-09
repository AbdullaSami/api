<?php

namespace App\Http\Controllers\Api\Courses;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\CourseAccessService;
use App\Models\Course;
use App\Models\CoursesCategory;
use App\Models\Skill;
use App\Models\Package;
use App\Models\User;

class CourseController extends Controller
{
    protected $courseAccessService;

    public function __construct(CourseAccessService $courseAccessService)
    {
        $this->courseAccessService = $courseAccessService;
    }

    public function index(Request $request)
    {
        try {
            $query = Course::with(['category', 'skills', 'instructor']);

            // Filter by category
            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            // Filter by skills (multiple skills can be selected)
            if ($request->has('skills') && !empty($request->skills)) {
                $skillIds = is_array($request->skills) ? $request->skills : explode(',', $request->skills);
                $query->whereHas('skills', function ($q) use ($skillIds) {
                    $q->whereIn('skills.id', $skillIds);
                });
            }

            // Filter by package level (courses accessible to specific package level)
            if ($request->has('package_level')) {
                $query->where('level', '<=', $request->package_level);
            }

            // Filter by duration range
            if ($request->has('duration_min')) {
                $query->where('duration', '>=', $request->duration_min);
            }
            if ($request->has('duration_max')) {
                $query->where('duration', '<=', $request->duration_max);
            }

            // Filter by exact duration
            if ($request->has('duration')) {
                $query->where('duration', $request->duration);
            }

            // Filter by course level
            if ($request->has('level')) {
                if (is_array($request->level)) {
                    $query->whereIn('level', $request->level);
                } else {
                    $query->where('level', $request->level);
                }
            }

            // Filter by published status (default to only published courses)
            if ($request->has('is_published')) {
                $query->where('is_published', $request->boolean('is_published'));
            } else {
                $query->where('is_published', true);
            }

            // Sort options
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');

            if (in_array($sortBy, ['title', 'duration', 'level', 'created_at'])) {
                $query->orderBy($sortBy, $sortOrder);
            }


            $courses = $query
                ->when($request->filled('title'), function ($q) use ($request) {
                    $q->where('title', 'like', '%' . $request->title . '%');
                })
                ->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $courses,
                'filters_applied' => [
                    'category_id' => $request->get('category_id'),
                    'skills' => $request->get('skills'),
                    'package_level' => $request->get('package_level'),
                    'duration_min' => $request->get('duration_min'),
                    'duration_max' => $request->get('duration_max'),
                    'duration' => $request->get('duration'),
                    'level' => $request->get('level'),
                    'is_published' => $request->get('is_published', true),
                    'sort_by' => $sortBy,
                    'sort_order' => $sortOrder
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve courses: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($course)
    {
        try {

            $course = Course::with(['category', 'instructor', 'sections.lessons', 'skills'])->where('slug', $course)->first();
            return response()->json([
                'success' => true,
                'data' => $course->load('category', 'instructor', 'sections', 'sections.lessons', 'skills') // Eager load lessons and skills with the course
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve course: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getFilterData()
    {
        try {
            $categories = CoursesCategory::all();
            $skills = Skill::all();
            $levels = Course::select('level')
                ->distinct()
                ->pluck('level')
                ->map(fn($level) => [
                    'value' => $level,
                    'label' => match ($level) {
                        1 => 'Beginner',
                        2 => 'Intermediate',
                        3 => 'Pro',
                        4 => 'Advanced',
                        default => 'Unknown',
                    }
                ]);
            $packages = Package::all();

            return response()->json([
                'success' => true,
                'data' => [
                    'categories' => $categories,
                    'skills' => $skills,
                    'levels' => $levels,
                    'packages' => $packages
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve filter data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function enroll(Request $request, $slug)
    {
        try {
            // Authenticate user - get authenticated user
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Please login to enroll in courses.'
                ], 401);
            }

            // Validate that slug exists in courses table
            $request->validate([
                'slug' => 'required|string|exists:courses,slug'
            ]);

            // Find course with proper error handling
            $course = Course::where('slug', $slug)->firstOrFail();

            // Check if user is already enrolled
            if ($user->courses()->where('course_id', $course->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are already enrolled in this course.'
                ], 409); // Conflict status code
            }

            // Check if course is published
            if (!$course->is_published) {
                return response()->json([
                    'success' => false,
                    'message' => 'This course is not available for enrollment.'
                ], 403);
            }

            // Check if user has access to course
            if (!$this->courseAccessService->canAccessCourse($user, $course->level)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to enroll in this course. Please upgrade your package.'
                ], 403);
            }

            // Enroll user in course using pivot table
            $user->courses()->attach($course->id, [
                'enrolled_at' => now(),
                'progress' => 0
            ]);

            // Clear cache for user's available courses
            cache()->forget("available_courses_{$user->id}_{$this->courseAccessService->getUserLevel($user)}");

            return response()->json([
                'success' => true,
                'message' => 'Successfully enrolled in the course.',
                'data' => [
                    'course_id' => $course->id,
                    'course_title' => $course->title,
                    'enrolled_at' => now()->toISOString(),
                    'user_id' => $user->id
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Course not found.'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Course enrollment failed: ' . $e->getMessage(), [
                'slug' => $slug,
                'user_id' => $request->user()?->id,
                'exception' => $e
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to enroll in course. Please try again later.'
            ], 500);
        }
    }
}
