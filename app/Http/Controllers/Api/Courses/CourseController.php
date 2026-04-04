<?php

namespace App\Http\Controllers\Api\Courses;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CourseAccessService;
use App\Models\Course;
class CourseController extends Controller
{
    protected $courseAccessService;

    public function __construct(CourseAccessService $courseAccessService)
    {
        $this->courseAccessService = $courseAccessService;
    }

    public function index(Request $request)
    {
        try{
            $query = Course::with(['category', 'skills', 'instructor']);

            // Filter by category
            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            // Filter by skills (multiple skills can be selected)
            if ($request->has('skills') && !empty($request->skills)) {
                $skillIds = is_array($request->skills) ? $request->skills : explode(',', $request->skills);
                $query->whereHas('skills', function($q) use ($skillIds) {
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

            $courses = $query->get();

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
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve courses: ' . $e->getMessage()
            ], 500);
            }
    }

    public function show(Course $course)
    {
        try{
            return response()->json([
                'success' => true,
                'data' => $course->load('category', 'instructor', 'sections', 'skills') // Eager load lessons and skills with the course
            ]);
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve course: ' . $e->getMessage()
            ], 500);
        }
    }
}
