<?php

namespace App\Services;

use App\Models\User;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Services\CourseAccessService;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use App\Events\CourseStarted;
use App\Events\CourseCompleted;
use App\Events\CourseProgressUpdated;
use App\Events\LessonProgressUpdated;

class CourseTrackingService
{
    public function __construct(private CourseAccessService $courseAccessService) {}

    public function startCourse(User $user, Course $course)
    {
        if (!$this->courseAccessService->canAccessCourse($user, $course->level)) {
            throw new AccessDeniedHttpException('User cannot access this course');
        }
        $user->courses()->syncWithoutDetaching([
            $course->id => [
                'enrolled_at' => now()
            ]
        ]);

        event(new CourseStarted($user, $course));
    }

    public function updateProgress(User $user, Course $course, float $progress)
    {
        $progress = max(0, min(100, $progress));

        // Get current progress for event
        $currentPivot = $user->courses()->where('course_id', $course->id)->first()?->pivot;
        $previousProgress = $currentPivot?->progress ?? 0;

        $user->courses()->updateExistingPivot($course->id, [
            'progress' => $progress
        ]);

        // Fire progress update event
        event(new CourseProgressUpdated($user, $course, $previousProgress, $progress));

        if ($progress >= 100 && !$this->isCourseCompleted($user, $course)) {
            $user->courses()->updateExistingPivot($course->id, [
                'completed_at' => now()
            ]);
            event(new CourseCompleted($user, $course));
        }
    }

    private function isCourseCompleted(User $user, Course $course): bool
    {
        return $user->courses()->where('course_id', $course->id)->whereNotNull('completed_at')->exists();
    }

    public function updateLessonProgress(User $user, Lesson $lesson, int $watchedSeconds, bool $isCompleted = false): void
    {
        // Check if user has access to the course containing this lesson
        $course = $lesson->section->course;
        if (!$this->courseAccessService->canAccessCourse($user, $course->level)) {
            throw new AccessDeniedHttpException('User cannot access this course');
        }

        $lessonProgress = LessonProgress::updateOrCreate(
            ['user_id' => $user->id, 'lesson_id' => $lesson->id],
            [
                'watched_seconds' => $watchedSeconds,
                'is_completed' => $isCompleted,
            ]
        );

        event(new LessonProgressUpdated($user, $lesson, $watchedSeconds, $isCompleted));

        // Update overall course progress based on lesson completion
        $this->recalculateCourseProgress($user, $course);
    }

    private function recalculateCourseProgress(User $user, Course $course): void
    {
        $totalLessons = $course->sections()->withCount('lessons')->get()->sum('lessons_count');
        $completedLessons = LessonProgress::where('user_id', $user->id)
            ->whereHas('lesson.section', function ($query) use ($course) {
                $query->where('course_id', $course->id);
            })
            ->where('is_completed', true)
            ->count();

        $progress = $totalLessons > 0 ? ($completedLessons / $totalLessons) * 100 : 0;

        $this->updateProgress($user, $course, $progress);
    }
}
