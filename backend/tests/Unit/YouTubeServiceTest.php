<?php

namespace Tests\Unit;

use App\Services\YouTubeService;
use Tests\TestCase;

class YouTubeServiceTest extends TestCase
{
    private YouTubeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new YouTubeService();
    }

    /**
     * @dataProvider videoUrlProvider
     */
    public function test_extract_video_id_from_various_urls(string $url, ?string $expectedId): void
    {
        $result = $this->service->extractVideoId($url);
        $this->assertEquals($expectedId, $result);
    }

    public static function videoUrlProvider(): array
    {
        return [
            'standard watch url' => [
                'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'dQw4w9WgXcQ'
            ],
            'short url' => [
                'https://youtu.be/dQw4w9WgXcQ',
                'dQw4w9WgXcQ'
            ],
            'embed url' => [
                'https://www.youtube.com/embed/dQw4w9WgXcQ',
                'dQw4w9WgXcQ'
            ],
            'shorts url' => [
                'https://www.youtube.com/shorts/dQw4w9WgXcQ',
                'dQw4w9WgXcQ'
            ],
            'with additional params' => [
                'https://www.youtube.com/watch?v=dQw4w9WgXcQ&list=xxx',
                'dQw4w9WgXcQ'
            ],
            'invalid url' => [
                'https://example.com/video',
                null
            ],
            'empty string' => [
                '',
                null
            ],
        ];
    }

    public function test_parse_duration_pt_format(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('parseDuration');

        // PT1H2M3S = 1 hour, 2 minutes, 3 seconds = 3723 seconds
        $result = $method->invoke($this->service, 'PT1H2M3S');
        $this->assertEquals(3723, $result);
    }

    public function test_parse_duration_without_hours(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('parseDuration');

        // PT5M30S = 5 minutes, 30 seconds = 330 seconds
        $result = $method->invoke($this->service, 'PT5M30S');
        $this->assertEquals(330, $result);
    }

    public function test_parse_duration_seconds_only(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('parseDuration');

        // PT45S = 45 seconds
        $result = $method->invoke($this->service, 'PT45S');
        $this->assertEquals(45, $result);
    }

    public function test_parse_duration_empty_match_returns_zero(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('parseDuration');

        $result = $method->invoke($this->service, '');
        $this->assertEquals(0, $result);
    }
}
