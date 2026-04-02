<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class YouTubeService
{
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = (string) (config('services.youtube.api_key') ?? env('YOUTUBE_API_KEY', ''));
    }

    public function extractVideoId(string $url): ?string
    {
        $patterns = [
            '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/',
            '/youtube\.com\/shorts\/([a-zA-Z0-9_-]{11})/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    public function getVideoInfo(string $videoId): ?array
    {
        if (empty($this->apiKey)) {
            return $this->getVideoInfoFallback($videoId);
        }

        $cacheKey = "youtube_video_{$videoId}";

        return Cache::remember($cacheKey, 3600, function () use ($videoId) {
            $response = Http::get("https://www.googleapis.com/youtube/v3/videos", [
                'part' => 'snippet,contentDetails',
                'id' => $videoId,
                'key' => $this->apiKey,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['items'][0])) {
                    $item = $data['items'][0];
                    return [
                        'title' => $item['snippet']['title'],
                        'description' => $item['snippet']['description'],
                        'thumbnail_url' => $item['snippet']['thumbnails']['high']['url'] ?? null,
                        'duration' => $this->parseDuration($item['contentDetails']['duration']),
                    ];
                }
            }

            return null;
        });
    }

    protected function getVideoInfoFallback(string $videoId): ?array
    {
        $oembedUrl = "https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v={$videoId}&format=json";

        try {
            $response = Http::timeout(5)->get($oembedUrl);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'title' => $data['title'] ?? 'YouTube Video',
                    'description' => '',
                    'thumbnail_url' => "https://img.youtube.com/vi/{$videoId}/hqdefault.jpg",
                    'duration' => 0,
                ];
            }
        } catch (\Exception $e) {
            return [
                'title' => 'YouTube Video',
                'description' => '',
                'thumbnail_url' => "https://img.youtube.com/vi/{$videoId}/hqdefault.jpg",
                'duration' => 0,
            ];
        }

        return null;
    }

    public function getTranscript(string $videoId): ?string
    {
        $cacheKey = "youtube_transcript_{$videoId}";

        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        // Use invidious instance for transcript (no API key needed)
        $instances = [
            'https://yewtu.be',
            'https://invidious.snopyta.org',
            'https://invidious.kavin.rocks',
        ];

        foreach ($instances as $instance) {
            try {
                $response = Http::timeout(10)->get("{$instance}/api/v1/videos/{$videoId}");

                if ($response->successful()) {
                    $data = $response->json();

                    if (!empty($data['subtitles'])) {
                        // Get English transcript if available
                        foreach ($data['subtitles'] as $subtitle) {
                            if (strpos($subtitle['lang'], 'en') !== false) {
                                $transcript = $this->fetchTranscript($subtitle['url']);
                                if ($transcript) {
                                    Cache::put($cacheKey, $transcript, 86400);
                                    return $transcript;
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // Fallback: return a placeholder indicating transcript unavailable
        return null;
    }

    protected function fetchTranscript(string $url): ?string
    {
        try {
            $response = Http::timeout(10)->get($url);

            if ($response->successful()) {
                $data = $response->json();

                if (!empty($data['events'])) {
                    $transcript = '';

                    foreach ($data['events'] as $event) {
                        if (!empty($event['segs'])) {
                            foreach ($event['segs'] as $seg) {
                                $transcript .= $seg['utf8'] ?? '';
                            }
                            $transcript .= " ";
                        }
                    }

                    return trim($transcript);
                }
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }

    protected function parseDuration(string $isoDuration): int
    {
        preg_match('/PT(\d+H)?(\d+M)?(\d+S)?/', $isoDuration, $matches);

        $hours = intval($matches[1] ?? 0);
        $minutes = intval($matches[2] ?? 0);
        $seconds = intval($matches[3] ?? 0);

        return $hours * 3600 + $minutes * 60 + $seconds;
    }
}
