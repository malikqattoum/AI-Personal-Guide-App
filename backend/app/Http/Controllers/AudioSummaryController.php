<?php

namespace App\Http\Controllers;

use App\Models\AudioSummary;
use App\Models\Document;
use App\Services\OpenAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AudioSummaryController extends Controller
{
    protected OpenAIService $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    public function generate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'document_uuid' => 'required|uuid',
            'title' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $document = $request->user()
            ->documents()
            ->where('uuid', $request->document_uuid)
            ->first();

        if (!$document) {
            return response()->json(['error' => 'Document not found'], 404);
        }

        if (empty($document->extracted_text)) {
            return response()->json(['error' => 'Document has no extractable text'], 400);
        }

        if (!$request->user()->canUse('audio_summary')) {
            return response()->json([
                'error' => 'Monthly audio summary limit reached',
                'upgrade' => true,
            ], 403);
        }
        $request->user()->logUsage('audio_summary');

        $title = $request->title ?? "Summary: {$document->title}";

        // Check if audio already exists
        $existing = AudioSummary::where('document_id', $document->id)
            ->where('user_id', $request->user()->id)
            ->first();

        if ($existing) {
            return response()->json([
                'audio_summary' => $existing,
                'message' => 'Audio summary already exists',
            ]);
        }

        try {
            // Create pending record
            $audioSummary = AudioSummary::create([
                'uuid' => Str::uuid(),
                'document_id' => $document->id,
                'user_id' => $request->user()->id,
                'title' => $title,
                'audio_path' => '',
                'duration_seconds' => 0,
                'status' => 'processing',
            ]);

            // Generate summary text
            $summaryText = $this->openAIService->summarize($document->extracted_text);

            // Estimate duration before generating audio
            $estimatedDuration = $this->openAIService->estimateAudioDuration($summaryText);

            // Generate audio
            $audioContent = $this->openAIService->textToSpeech($summaryText, 'alloy');

            // Save audio file
            $filename = $audioSummary->uuid;
            $audioPath = $this->openAIService->saveAudioFile($audioContent, $filename);

            // Update record
            $audioSummary->update([
                'audio_path' => $audioPath,
                'transcript' => $summaryText,
                'duration_seconds' => $estimatedDuration,
                'status' => 'completed',
            ]);

            return response()->json([
                'audio_summary' => $audioSummary,
                'message' => 'Audio summary generated successfully',
            ], 201);
        } catch (\Exception $e) {
            if (isset($audioSummary)) {
                $audioSummary->update(['status' => 'failed']);
            }

            return response()->json([
                'error' => 'Failed to generate audio summary',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, string $uuid): JsonResponse
    {
        $audioSummary = $request->user()
            ->audioSummaries()
            ->where('uuid', $uuid)
            ->first();

        if (!$audioSummary) {
            return response()->json(['error' => 'Audio summary not found'], 404);
        }

        return response()->json(['audio_summary' => $audioSummary]);
    }

    public function stream(Request $request, string $uuid): JsonResponse
    {
        $audioSummary = $request->user()
            ->audioSummaries()
            ->where('uuid', $uuid)
            ->first();

        if (!$audioSummary) {
            return response()->json(['error' => 'Audio summary not found'], 404);
        }

        if (!Storage::disk('local')->exists($audioSummary->audio_path)) {
            return response()->json(['error' => 'Audio file not found'], 404);
        }

        $file = Storage::disk('local')->get($audioSummary->audio_path);

        return response($file, 200, [
            'Content-Type' => 'audio/mpeg',
            'Content-Disposition' => 'inline',
        ]);
    }

    public function destroy(Request $request, string $uuid): JsonResponse
    {
        $audioSummary = $request->user()
            ->audioSummaries()
            ->where('uuid', $uuid)
            ->first();

        if (!$audioSummary) {
            return response()->json(['error' => 'Audio summary not found'], 404);
        }

        // Delete file
        if ($audioSummary->audio_path && Storage::disk('local')->exists($audioSummary->audio_path)) {
            Storage::disk('local')->delete($audioSummary->audio_path);
        }

        $audioSummary->delete();

        return response()->json(['message' => 'Audio summary deleted successfully']);
    }

    public function byDocument(Request $request, string $documentUuid): JsonResponse
    {
        $document = $request->user()
            ->documents()
            ->where('uuid', $documentUuid)
            ->first();

        if (!$document) {
            return response()->json(['error' => 'Document not found'], 404);
        }

        $audioSummaries = $document->audioSummaries()->get();

        return response()->json(['audio_summaries' => $audioSummaries]);
    }
}
