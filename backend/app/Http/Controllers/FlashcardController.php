<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Flashcard;
use App\Services\OpenAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class FlashcardController extends Controller
{
    protected OpenAIService $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    public function index(Request $request): JsonResponse
    {
        $flashcards = $request->user()
            ->flashcards()
            ->with('document:id,title,uuid')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['flashcards' => $flashcards]);
    }

    public function generate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'document_uuid' => 'required|uuid',
            'count' => 'nullable|integer|min:1|max:20',
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

        $count = $request->count ?? 5;

        try {
            $flashcardData = $this->openAIService->generateFlashcards($document->extracted_text, $count);

            $flashcards = [];
            foreach ($flashcardData as $data) {
                $flashcard = Flashcard::create([
                    'uuid' => Str::uuid(),
                    'document_id' => $document->id,
                    'user_id' => $request->user()->id,
                    'front_text' => $data['front'],
                    'back_text' => $data['back'],
                    'difficulty' => 'medium',
                ]);
                $flashcards[] = $flashcard;
            }

            return response()->json([
                'flashcards' => $flashcards,
                'message' => "Generated {$count} flashcards successfully",
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate flashcards',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, string $uuid): JsonResponse
    {
        $flashcard = $request->user()
            ->flashcards()
            ->where('uuid', $uuid)
            ->first();

        if (!$flashcard) {
            return response()->json(['error' => 'Flashcard not found'], 404);
        }

        return response()->json(['flashcard' => $flashcard]);
    }

    public function review(Request $request, string $uuid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'correct' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $flashcard = $request->user()
            ->flashcards()
            ->where('uuid', $uuid)
            ->first();

        if (!$flashcard) {
            return response()->json(['error' => 'Flashcard not found'], 404);
        }

        // Use atomic increment to prevent race conditions
        $flashcard->increment('times_reviewed');
        if ($request->correct) {
            $flashcard->increment('times_correct');
        }
        $flashcard->last_reviewed_at = now();
        $flashcard->save();

        return response()->json([
            'flashcard' => $flashcard->fresh(),
            'message' => 'Review recorded',
        ]);
    }

    public function destroy(Request $request, string $uuid): JsonResponse
    {
        $flashcard = $request->user()
            ->flashcards()
            ->where('uuid', $uuid)
            ->first();

        if (!$flashcard) {
            return response()->json(['error' => 'Flashcard not found'], 404);
        }

        $flashcard->delete();

        return response()->json(['message' => 'Flashcard deleted successfully']);
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

        $flashcards = $document->flashcards()->get();

        return response()->json(['flashcards' => $flashcards]);
    }
}
