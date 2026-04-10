<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseEnrollmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->course->id,
            'title' => $this->course->title,
            'level' => $this->course->level,
            'duration' => $this->course->duration,
            'progress' => $this->progress ?? 0,
            'thumbnail' => $this->course->thumbnail,
            'instructor' => $this->course->instructor?->name,
            'status' => $this->getStatus(),
            'created_at' => $this->created_at->toDateTimeString(),
            'last_accessed_at' => $this->updated_at?->diffForHumans(),
        ];
    }

    private function getStatus()
    {
        return match (true) {
            $this->progress == 0 => 'not_started',
            $this->progress == 100 => 'completed',
            default => 'in_progress',
        };
    }
}
