<?php

namespace App\Events;

use App\Models\User;
use App\Models\Lesson;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LessonProgressUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user,
        public Lesson $lesson,
        public int $watchedSeconds,
        public bool $isCompleted
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->user->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'lesson.progress.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'lesson_id' => $this->lesson->id,
            'lesson_title' => $this->lesson->title,
            'watched_seconds' => $this->watchedSeconds,
            'is_completed' => $this->isCompleted,
            'updated_at' => now()->toISOString(),
        ];
    }
}
