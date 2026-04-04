<?php

namespace App\Services;

use App\Models\User;
use App\Models\Course;
class CourseAccessService
{

    public function getUserLevel(User $user)
    {
        $subscription = $user->member->subscription;

        return $subscription?->package->level ?? 0;
    }

    public function canAccessCourse(User $user, Course $courseLevel): bool
    {
        $userLevel = $this->getUserLevel($user);

        return $userLevel >= $courseLevel;
    }

    public function getAvailableCourses(User $user){
        $userLevel = $this->getUserLevel($user);

        return Course::where('level', '<=', $userLevel)
                    ->where('is_published', true)
                    ->get();
    }
}
