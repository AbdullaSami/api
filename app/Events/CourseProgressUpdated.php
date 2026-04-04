<?php

namespace App\Events;

use App\Models\User;
use App\Models\Course;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CourseProgressUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user,
        public Course $course,
        public float $previousProgress,
        public float $newProgress
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->user->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'course.progress.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'course_id' => $this->course->id,
            'course_title' => $this->course->title,
            'previous_progress' => $this->previousProgress,
            'new_progress' => $this->newProgress,
            'progress_change' => $this->newProgress - $this->previousProgress,
            'updated_at' => now()->toISOString(),
        ];
    }
}
