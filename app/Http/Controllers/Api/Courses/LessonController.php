<?php

namespace App\Http\Controllers\Api\Courses;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Lesson;
use App\Http\Resources\LessonResource;
use App\Models\Course;
use App\Services\BunnyStreamService;
use App\Services\CourseAccessService;

class LessonController extends Controller
{
    public function __construct(private BunnyStreamService $bunny, private CourseAccessService $courseAccessService){}

    // upload a lesson video
    public function upload(Request $request, Lesson $lesson){
        $request->validate([
            'video' => 'required|file|mimes:mp4,mov,avi,mkv|max:2048000', // max 500MB
        ]);

        $file = $request->file('video');

        // Create a video slot in Bunny
        $videoId = $this->bunny->createVideo($lesson->title);

        // Upload file to Bunny
        $uploaded = $this->bunny->uploadVideo($videoId, $file->getRealPath());

        if (!$uploaded){
            return response()->json(['message' => 'Failed to upload video to Bunny.'], 500);
        }

        // Save vide ID to lesson
        $lesson->update([
            'video_id' => $videoId,
            'video_status' => 'processing',
        ]);

        return response()->json(['message' => 'Video uploaded successfully, processing started.']);
    }

    // Get a signed stream URL (for authenticated and enrolled users only)
    public function stream(Lesson $lesson){
        $user = auth()->user();

        // Check if user is enrolled in the course
        $courseLevel = $lesson?->section?->course?->level;
        $isEnrolled = $this->courseAccessService->canAccessCourse($user, $courseLevel);

        if (!$isEnrolled) {
            return response()->json(['message' => 'Not enrolled in this course'], 403);
        }

        if (!$lesson->video_id || $lesson->video_status !== 'ready') {
            return response()->json(['message' => 'Video not available'], 404);
        }

        // Generate token valid for 2 hours
        $videoId = $lesson?->video_id;
        $signedUrl = $this->bunny->getSignedStreamUrl($videoId, 7200);

        return response()->json(['stream_url' => $signedUrl]);
    }
}
