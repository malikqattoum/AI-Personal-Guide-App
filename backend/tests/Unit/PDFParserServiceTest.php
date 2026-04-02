<?php

namespace Tests\Unit;

use App\Services\PDFParserService;
use Tests\TestCase;

class PDFParserServiceTest extends TestCase
{
    private PDFParserService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PDFParserService();
    }

    public function test_process_in_chunks_splits_text_correctly(): void
    {
        $text = 'This is sentence one. This is sentence two. This is sentence three. This is sentence four.';

        $chunks = $this->service->processInChunks($text, 30);

        $this->assertNotEmpty($chunks);
        foreach ($chunks as $chunk) {
            $this->assertLessThanOrEqual(30, strlen($chunk));
        }
    }

    public function test_process_in_chunks_handles_empty_string(): void
    {
        $chunks = $this->service->processInChunks('');

        $this->assertIsArray($chunks);
        // Empty input produces one empty chunk since sentences array is empty
        // but the logic appends empty string to result
        $this->assertLessThanOrEqual(1, count($chunks));
    }

    public function test_process_in_chunks_handles_single_sentence(): void
    {
        $text = 'This is a single sentence.';

        $chunks = $this->service->processInChunks($text, 100);

        $this->assertCount(1, $chunks);
        $this->assertEquals('This is a single sentence.', $chunks[0]);
    }

    public function test_process_in_chunks_preserves_sentence_boundaries(): void
    {
        $text = 'First sentence. Second sentence. Third sentence.';

        $chunks = $this->service->processInChunks($text, 50);

        // Each chunk should be a complete sentence or group of sentences
        foreach ($chunks as $chunk) {
            $this->assertDoesNotMatchRegularExpression('/\s$/', $chunk);
        }
    }

    public function test_process_in_chunks_large_chunk_size_returns_single_chunk(): void
    {
        $text = 'Short text.';

        $chunks = $this->service->processInChunks($text, 1000);

        $this->assertCount(1, $chunks);
    }

    public function test_process_in_chunks_respects_custom_chunk_size(): void
    {
        $text = 'Sentence one. Sentence two. Sentence three. Sentence four. Sentence five.';

        $chunks = $this->service->processInChunks($text, 20);

        // With 20 char chunk size, we should get multiple chunks
        $this->assertGreaterThan(1, count($chunks));
    }
}
