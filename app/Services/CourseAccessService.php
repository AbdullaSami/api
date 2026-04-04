<?php

namespace App\Services;

use App\Models\User;
use App\Models\Course;
use Illuminate\Support\Collection;
class CourseAccessService
{

    public function getUserLevel(User $user)
    {
        if (!$user->member || !$user->member->subscription) {
            return 0;
        }
        $subscription = $user->member->subscription;

        return $subscription?->package->level ?? 0;
    }

    public function canAccessCourse(User $user, Course $courseLevel): bool
    {
        $userLevel = $this->getUserLevel($user);
        return $userLevel >= $courseLevel;
    }

    public function getAvailableCourses(User $user): Collection
    {
        $userLevel = $this->getUserLevel($user);

        return cache()->remember(
            "available_courses_{$user->id}_{$userLevel}",
            3600,
            fn() => Course::where('level', '<=', $userLevel)
                ->where('is_published', true)
                ->get()
        );
    }
}
