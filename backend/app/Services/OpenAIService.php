<?php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Storage;

class OpenAIService
{
    protected string $model;
    protected string $ttsModel;

    public function __construct()
    {
        $this->model = config('services.openai.model', 'gpt-4o-mini');
        $this->ttsModel = 'tts-1';
    }

    /**
     * Estimate token count (rough approximation: 1 token ≈ 4 characters)
     */
    protected function estimateTokens(string $text): int
    {
        return (int) ceil(strlen($text) / 4);
    }

    public function chat(string $prompt, array $context = [], array $history = []): string
    {
        $messages = [];

        // System message
        $systemPrompt = "You are a helpful study assistant. You help users understand their documents, generate flashcards, and provide concise summaries.";
        if (!empty($context['document_text'])) {
            $systemPrompt .= "\n\nThe user is studying the following document:\n\n" . substr($context['document_text'], 0, 10000);
        }

        // Truncate system prompt if too long
        if ($this->estimateTokens($systemPrompt) > 4000) {
            $systemPrompt = substr($systemPrompt, 0, 16000);
        }
        $messages[] = ['role' => 'system', 'content' => $systemPrompt];

        // Add conversation history with token budget
        $tokenBudget = 6000 - $this->estimateTokens($systemPrompt) - 500; // Reserve 500 for response
        foreach ($history as $msg) {
            $msgTokens = $this->estimateTokens($msg['content']);
            if ($tokenBudget >= $msgTokens) {
                $messages[] = ['role' => $msg['role'], 'content' => $msg['content']];
                $tokenBudget -= $msgTokens;
            } else {
                break; // Stop adding history if we're out of budget
            }
        }

        // Current message
        $messages[] = ['role' => 'user', 'content' => $prompt];

        $response = OpenAI::chat()->create([
            'model' => $this->model,
            'messages' => $messages,
            'max_tokens' => 1000,
            'temperature' => 0.7,
        ]);

        return $response->choices[0]->message->content;
    }

    public function generateFlashcards(string $content, int $count = 5): array
    {
        $truncatedContent = substr($content, 0, 15000);

        $prompt = "Generate exactly {$count} flashcards from the following content. Each flashcard should have a front (question/term) and back (answer/definition). Format your response as a JSON array with objects containing 'front' and 'back' fields only. No other text.

Content:
{$truncatedContent}";

        $response = OpenAI::chat()->create([
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a flashcard generation assistant. Only output valid JSON.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => 2000,
            'temperature' => 0.5,
        ]);

        $content = $response->choices[0]->message->content;

        // Parse JSON from response
        $content = trim($content);
        if (str_starts_with($content, '```json')) {
            $content = substr($content, 7);
        }
        if (str_starts_with($content, '```')) {
            $content = substr($content, 3);
        }
        if (str_ends_with($content, '```')) {
            $content = substr($content, 0, -3);
        }

        $flashcards = json_decode($content, true);

        if (!is_array($flashcards)) {
            throw new \Exception('Failed to parse flashcards from OpenAI response');
        }

        return array_slice($flashcards, 0, $count);
    }

    public function summarize(string $content, int $maxWords = 300): string
    {
        $truncatedContent = substr($content, 0, 15000);

        $prompt = "Summarize the following content in approximately {$maxWords} words. Create a clear, concise summary that captures the main points. This summary will be converted to audio, so make it flow naturally when spoken.

Content:
{$truncatedContent}";

        $response = OpenAI::chat()->create([
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a summarization assistant. Create clear, flowing summaries suitable for text-to-speech.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => 1500,
            'temperature' => 0.5,
        ]);

        return $response->choices[0]->message->content;
    }

    public function textToSpeech(string $text, string $voice = null): string
    {
        // OpenAI TTS has a limit of ~4096 characters
        $text = substr($text, 0, 4000);

        // Use configurable voice, default to 'alloy'
        $voice = $voice ?? config('services.openai.tts_voice', 'alloy');

        // Validate voice is one of the allowed values
        $allowedVoices = ['alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer'];
        if (!in_array($voice, $allowedVoices)) {
            $voice = 'alloy';
        }

        $response = OpenAI::audio()->speech([
            'model' => $this->ttsModel,
            'input' => $text,
            'voice' => $voice,
            'response_format' => 'mp3',
        ]);

        return $response;
    }

    public function saveAudioFile(string $audioContent, string $filename): string
    {
        $path = 'audio/' . date('Y/m') . '/' . $filename . '.mp3';
        Storage::disk('local')->put($path, $audioContent);

        return $path;
    }

    public function estimateAudioDuration(string $text): int
    {
        // Average speaking rate is ~150 words per minute
        // With pauses, we'll use ~130 words per minute
        $wordCount = str_word_count($text);
        return (int) ceil(($wordCount / 130) * 60);
    }
}
