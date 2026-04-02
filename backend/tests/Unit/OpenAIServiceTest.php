<?php

namespace Tests\Unit;

use App\Services\OpenAIService;
use Tests\TestCase;

class OpenAIServiceTest extends TestCase
{
    private OpenAIService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OpenAIService();
    }

    public function test_estimate_tokens_calculation(): void
    {
        $text = 'Hello, world!'; // 13 chars
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('estimateTokens');
        $method->setAccessible(true);

        $tokens = $method->invoke($this->service, $text);

        $this->assertEquals(4, $tokens); // ceil(13/4) = 4
    }

    public function test_estimate_tokens_with_empty_string(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('estimateTokens');
        $method->setAccessible(true);

        $tokens = $method->invoke($this->service, '');

        $this->assertEquals(0, $tokens);
    }

    public function test_estimate_tokens_with_long_text(): void
    {
        $text = str_repeat('a', 100);
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('estimateTokens');
        $method->setAccessible(true);

        $tokens = $method->invoke($this->service, $text);

        $this->assertEquals(25, $tokens); // ceil(100/4) = 25
    }
}
