<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\Document;
use App\Services\OpenAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    protected OpenAIService $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    public function messages(Request $request): JsonResponse
    {
        $query = $request->user()->chatMessages();

        if ($request->document_uuid) {
            $document = $request->user()
                ->documents()
                ->where('uuid', $request->document_uuid)
                ->first();

            if ($document) {
                $query->where('document_id', $document->id);
            }
        }

        $messages = $query->orderBy('created_at', 'asc')->get();

        return response()->json(['messages' => $messages]);
    }

    public function send(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:5000',
            'document_uuid' => 'nullable|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $document = null;
        if ($request->document_uuid) {
            $document = $request->user()
                ->documents()
                ->where('uuid', $request->document_uuid)
                ->first();
        }

        // Save user message
        $userMessage = ChatMessage::create([
            'uuid' => Str::uuid(),
            'user_id' => $request->user()->id,
            'document_id' => $document?->id,
            'role' => 'user',
            'message_text' => $request->message,
        ]);

        // Get conversation history
        $history = [];
        if ($document) {
            $previousMessages = ChatMessage::where('user_id', $request->user()->id)
                ->where('document_id', $document->id)
                ->orderBy('created_at', 'asc')
                ->take(10)
                ->get();

            foreach ($previousMessages as $msg) {
                $history[] = [
                    'role' => $msg->role,
                    'content' => $msg->message_text,
                ];
            }
        }

        // Build context
        $context = [];
        if ($document && $document->extracted_text) {
            $context['document_text'] = $document->extracted_text;
        }

        // Get AI response
        try {
            $response = $this->openAIService->chat($request->message, $context, $history);

            $assistantMessage = ChatMessage::create([
                'uuid' => Str::uuid(),
                'user_id' => $request->user()->id,
                'document_id' => $document?->id,
                'role' => 'assistant',
                'message_text' => $response,
            ]);

            return response()->json([
                'user_message' => $userMessage,
                'assistant_message' => $assistantMessage,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate response',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function clear(Request $request): JsonResponse
    {
        $query = $request->user()->chatMessages();

        if ($request->document_uuid) {
            $document = $request->user()
                ->documents()
                ->where('uuid', $request->document_uuid)
                ->first();

            if ($document) {
                $query->where('document_id', $document->id);
            }
        }

        $query->delete();

        return response()->json(['message' => 'Chat history cleared']);
    }
}
