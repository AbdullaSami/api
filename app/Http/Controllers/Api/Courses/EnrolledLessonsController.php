<?php

namespace App\Http\Controllers\Api\Courses;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Services\CourseTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EnrolledLessonsController extends Controller
{
    public function __construct(private CourseTrackingService $courseTrackingService) {}

    public function index(Request $request, string $course)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized.'
                ], 401);
            }

            $courseModel = Course::with(['sections.lessons'])->where('slug', $course)->firstOrFail();

            if (!$user->courses()->where('course_id', $courseModel->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not enrolled in this course.'
                ], 403);
            }

            $lessonIds = $courseModel->sections->flatMap(fn($s) => $s->lessons->pluck('id'))->values();

            $progressByLessonId = LessonProgress::query()
                ->where('user_id', $user->id)
                ->whereIn('lesson_id', $lessonIds)
                ->get()
                ->keyBy('lesson_id');

            $sections = $courseModel->sections
                ->sortBy('order')
                ->values()
                ->map(function ($section) use ($progressByLessonId) {
                    return [
                        'id' => $section->id,
                        'title' => $section->title,
                        'slug' => $section->slug,
                        'description' => $section->description,
                        'order' => $section->order,
                        'lessons' => $section->lessons
                            ->sortBy('order')
                            ->values()
                            ->map(function ($lesson) use ($progressByLessonId) {
                                $progress = $progressByLessonId->get($lesson->id);

                                return [
                                    'id' => $lesson->id,
                                    'title' => $lesson->title,
                                    'slug' => $lesson->slug,
                                    'description' => $lesson->description,
                                    'duration' => $lesson->duration,
                                    'order' => $lesson->order,
                                    'is_preview' => (bool) $lesson->is_preview,
                                    'progress' => [
                                        'watched_seconds' => $progress?->watched_seconds ?? 0,
                                        'is_completed' => (bool) ($progress?->is_completed ?? false),
                                        'updated_at' => $progress?->updated_at?->toISOString(),
                                    ],
                                ];
                            })
                    ];
                });

            $pivot = $user->courses()->where('course_id', $courseModel->id)->first()?->pivot;

            return response()->json([
                'success' => true,
                'data' => [
                    'course' => [
                        'id' => $courseModel->id,
                        'title' => $courseModel->title,
                        'slug' => $courseModel->slug,
                    ],
                    'enrollment' => [
                        'enrolled_at' => $pivot?->enrolled_at,
                        'completed_at' => $pivot?->completed_at,
                        'progress' => $pivot?->progress ?? 0,
                    ],
                    'sections' => $sections,
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Course not found.'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve enrolled course lessons: ' . $e->getMessage(), [
                'course' => $course,
                'user_id' => $request->user()?->id,
                'exception' => $e
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve lessons. Please try again later.'
            ], 500);
        }
    }

    public function show(Request $request, string $course, string $lesson)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized.'
                ], 401);
            }

            $courseModel = Course::where('slug', $course)->firstOrFail();

            if (!$user->courses()->where('course_id', $courseModel->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not enrolled in this course.'
                ], 403);
            }

            $lessonModel = Lesson::query()
                ->where('slug', $lesson)
                ->whereHas('section', function ($q) use ($courseModel) {
                    $q->where('course_id', $courseModel->id);
                })
                ->firstOrFail();

            $progress = LessonProgress::query()
                ->where('user_id', $user->id)
                ->where('lesson_id', $lessonModel->id)
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'course' => [
                        'id' => $courseModel->id,
                        'title' => $courseModel->title,
                        'slug' => $courseModel->slug,
                    ],
                    'lesson' => [
                        'id' => $lessonModel->id,
                        'title' => $lessonModel->title,
                        'slug' => $lessonModel->slug,
                        'description' => $lessonModel->description,
                        'video_id' => $lessonModel->video_id,
                        'duration' => $lessonModel->duration,
                        'order' => $lessonModel->order,
                        'is_preview' => (bool) $lessonModel->is_preview,
                    ],
                    'progress' => [
                        'watched_seconds' => $progress?->watched_seconds ?? 0,
                        'is_completed' => (bool) ($progress?->is_completed ?? false),
                        'updated_at' => $progress?->updated_at?->toISOString(),
                    ],
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Course or lesson not found.'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve enrolled lesson: ' . $e->getMessage(), [
                'course' => $course,
                'lesson' => $lesson,
                'user_id' => $request->user()?->id,
                'exception' => $e
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve lesson. Please try again later.'
            ], 500);
        }
    }

    public function updateProgress(Request $request, string $course, string $lesson)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized.'
                ], 401);
            }

            $validated = $request->validate([
                'watched_seconds' => 'required|integer|min:0',
                'is_completed' => 'sometimes|boolean',
            ]);

            $courseModel = Course::where('slug', $course)->firstOrFail();

            if (!$user->courses()->where('course_id', $courseModel->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not enrolled in this course.'
                ], 403);
            }

            $lessonModel = Lesson::query()
                ->where('slug', $lesson)
                ->whereHas('section', function ($q) use ($courseModel) {
                    $q->where('course_id', $courseModel->id);
                })
                ->firstOrFail();

            $this->courseTrackingService->updateLessonProgress(
                $user,
                $lessonModel,
                (int) $validated['watched_seconds'],
                (bool) ($validated['is_completed'] ?? false)
            );

            $lessonProgress = LessonProgress::query()
                ->where('user_id', $user->id)
                ->where('lesson_id', $lessonModel->id)
                ->first();

            $pivot = $user->courses()->where('course_id', $courseModel->id)->first()?->pivot;

            return response()->json([
                'success' => true,
                'message' => 'Progress updated successfully.',
                'data' => [
                    'lesson_progress' => [
                        'watched_seconds' => $lessonProgress?->watched_seconds ?? 0,
                        'is_completed' => (bool) ($lessonProgress?->is_completed ?? false),
                        'updated_at' => $lessonProgress?->updated_at?->toISOString(),
                    ],
                    'course_progress' => [
                        'progress' => $pivot?->progress ?? 0,
                        'completed_at' => $pivot?->completed_at,
                    ],
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
                'message' => 'Course or lesson not found.'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to update lesson progress: ' . $e->getMessage(), [
                'course' => $course,
                'lesson' => $lesson,
                'user_id' => $request->user()?->id,
                'exception' => $e
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update progress. Please try again later.'
            ], 500);
        }
    }
}
