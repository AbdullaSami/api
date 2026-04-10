<?php

namespace App\Http\Controllers\Api\Courses;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Http\Resources\CourseEnrollmentResource;
use Symfony\Component\HttpFoundation\JsonResponse;
use Illuminate\Support\Facades\Log;

class UserProfileController extends Controller
{
    public function userProfile(): JsonResponse
    {

        try {
            $user = auth()->user()->load('member.subscription.package');
            $subscriptionLevel = $user->member?->subscription?->package?->level ?? 1;
            $completedPercentage = 100;
            //Total courses available for the user's subscription level
            $totalCourses = Course::where('level', '<=', $subscriptionLevel)->count();
            // state and aggregate to fetch the data cleanly
            $stats = $user->courseEnrollments()
                ->selectRaw("
        COUNT(*) as total,
        SUM(CASE WHEN progress = 0 THEN 1 ELSE 0 END) as not_started,
        SUM(CASE WHEN progress > 0 AND progress < 100 THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN progress = 100 THEN 1 ELSE 0 END) as completed
        ")
                ->first();
            // =========================================================================================
            //Total courses Subscribed by the user
            $totalSubscribedCourses = $stats->total ?? 0;
            //Total courses Subscribed and not started yet
            $totalNotStartedCourses = $stats->not_started ?? 0;
            //Total courses Subscribed and in progress
            $totalInProgressCourses = $stats->in_progress ?? 0;
            //Total courses Subscribed and completed
            $totalCompletedCourses = $stats->completed ?? 0;
            // ==========================================================================================
            //Courses completion percentage
            if ($totalSubscribedCourses > 0) {
                $coursesCompletionPercentage = ($totalCompletedCourses / $totalSubscribedCourses) * 100;
            } else {
                $coursesCompletionPercentage = 0;
            }
            //Subscribed Courses VS Percentage Completion
            $subscribedCoursesVsPercentageCompletion = [
                'subscribed' => $totalSubscribedCourses,
                'percentage' => $coursesCompletionPercentage,
            ];
            //Recent Courses User subscribed to
            $recentCourses = $user->courseEnrollments()
                ->with('course')
                ->latest()
                ->take(5)
                ->get();
            //Completed Courses
            $completedCourses = $user->courseEnrollments()
                ->with('course')
                ->where('progress', $completedPercentage)
                ->limit(10)
                ->get();

            return response()->json([
                'total_courses' => $totalCourses,
                'total_subscribed_courses' => $totalSubscribedCourses,
                'total_not_started_courses' => $totalNotStartedCourses,
                'total_in_progress_courses' => $totalInProgressCourses,
                'total_completed_courses' => $totalCompletedCourses,
                'courses_completion_percentage' => $coursesCompletionPercentage,
                'subscribed_courses_vs_percentage_completion' => $subscribedCoursesVsPercentageCompletion,
                'recent_courses' => CourseEnrollmentResource::collection($recentCourses),
                'completed_courses' => CourseEnrollmentResource::collection($completedCourses),
            ]);
        } catch (\Exception $e) {
            \Log::error('User profile error', [
                'exception' => $e
            ]);
            return response()->json([
                'message' => 'Something went wrong!',
            ], 500);
        }
    }
}
