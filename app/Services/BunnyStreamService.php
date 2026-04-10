<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class BunnyStreamService
{
    protected $apiKey;
    protected $libraryId;
    protected $cdnHostname;
    protected $securityKey;

    public function __construct()
    {
        $this->apiKey = config('services.bunny.api_key');
        $this->libraryId = config('services.bunny.library_id');
        $this->cdnHostname = config('services.bunny.cdn_hostname');
        $this->securityKey = config('services.bunny.security_key');
    }

    // -- step 1: create a video upload URL
    public function createVideo(string $title): string
    {
        $response = Http::withHeaders([
            'AccessKey' => $this->apiKey,
            'Content-type' => 'application/json',
        ])->post("https://video.bunnycdn.com/library/{$this->libraryId}/videos", [
            'title' => $title,
        ]);

        return $response->json('guid');
    }

    // -- step 2: generate a signed URL for uploading the video file
    public function uploadVideo(string $videoId, string $filePath): bool
    {
        $response = Http::withHeaders([
            'AccessKey' => $this->apiKey,
            'Content-type' => 'application/json',
        ])->withBody(fopen($filePath, 'r'), 'video/*')
            ->put("https://video.bunnycdn.com/library/{$this->libraryId}/videos/{$videoId}");

        return $response->successful();
    }

    // -- step 3: generate a signed URL for streaming the video
    public function getSignedStreamUrl(string $videoId, int $expiresInSeconds = 7200): string
    {
        $expires = time() + $expiresInSeconds;

        // Raw binary SHA256 → base64url
        $hash = hash('sha256', $this->securityKey . $videoId . $expires, true);
        $token = rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');

        return "https://iframe.mediadelivery.net/embed/{$this->libraryId}/{$videoId}"
            . "?token={$token}&expires={$expires}";
    }

    // ── Optional: Delete a video ──
    public function deleteVideo(string $videoId): bool
    {
        $response = Http::withHeaders(['AccessKey' => $this->apiKey])
            ->delete("https://video.bunnycdn.com/library/{$this->libraryId}/videos/{$videoId}");

        return $response->successful();
    }
}
