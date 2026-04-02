<?php

namespace App\Http\Controllers;

use App\Models\StudySession;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class StudySessionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $sessions = $request->user()
            ->studySessions()
            ->with('document:id,title,uuid')
            ->orderBy('started_at', 'desc')
            ->take(50)
            ->get();

        return response()->json(['sessions' => $sessions]);
    }

    public function start(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'document_uuid' => 'nullable|uuid',
            'session_type' => 'nullable|in:free_study,flashcard_review,audio_review,chat_session',
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

        $session = StudySession::create([
            'uuid' => Str::uuid(),
            'user_id' => $request->user()->id,
            'document_id' => $document?->id,
            'started_at' => now(),
            'session_type' => $request->session_type ?? 'free_study',
        ]);

        return response()->json([
            'session' => $session,
            'message' => 'Study session started',
        ], 201);
    }

    public function end(Request $request, string $uuid): JsonResponse
    {
        $session = $request->user()
            ->studySessions()
            ->where('uuid', $uuid)
            ->first();

        if (!$session) {
            return response()->json(['error' => 'Session not found'], 404);
        }

        if ($session->ended_at) {
            return response()->json(['error' => 'Session already ended'], 400);
        }

        $session->ended_at = now();
        $session->duration_minutes = now()->diffInMinutes($session->started_at);
        $session->save();

        // Update user stats
        $request->user()->increment('total_study_minutes', $session->duration_minutes);

        return response()->json([
            'session' => $session,
            'message' => 'Study session ended',
        ]);
    }

    public function show(Request $request, string $uuid): JsonResponse
    {
        $session = $request->user()
            ->studySessions()
            ->with('document:id,title,uuid')
            ->where('uuid', $uuid)
            ->first();

        if (!$session) {
            return response()->json(['error' => 'Session not found'], 404);
        }

        return response()->json(['session' => $session]);
    }

    public function updateStats(Request $request, string $uuid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'flashcards_reviewed' => 'nullable|integer|min:0',
            'flashcards_correct' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $session = $request->user()
            ->studySessions()
            ->where('uuid', $uuid)
            ->first();

        if (!$session) {
            return response()->json(['error' => 'Session not found'], 404);
        }

        if ($request->has('flashcards_reviewed')) {
            $session->flashcards_reviewed = $request->flashcards_reviewed;
        }
        if ($request->has('flashcards_correct')) {
            $session->flashcards_correct = $request->flashcards_correct;
        }
        $session->save();

        return response()->json([
            'session' => $session,
            'message' => 'Session stats updated',
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        $totalSessions = $user->studySessions()->count();
        $totalMinutes = $user->total_study_minutes;
        $totalFlashcards = $user->flashcards()->count();
        $totalDocuments = $user->documents()->count();

        // Calculate streak
        $lastSession = $user->studySessions()
            ->whereNotNull('ended_at')
            ->orderBy('ended_at', 'desc')
            ->first();

        $streak = 0;
        if ($lastSession) {
            $checkDate = now()->toDateString();
            $sessions = $user->studySessions()
                ->whereNotNull('ended_at')
                ->orderBy('ended_at', 'desc')
                ->get();

            foreach ($sessions as $session) {
                $sessionDate = $session->ended_at->toDateString();
                if ($sessionDate === $checkDate || $sessionDate === now()->subDay()->toDateString()) {
                    $streak++;
                    $checkDate = $session->ended_at->subDay()->toDateString();
                } else {
                    break;
                }
            }
        }

        return response()->json([
            'stats' => [
                'total_sessions' => $totalSessions,
                'total_study_minutes' => $totalMinutes,
                'total_flashcards' => $totalFlashcards,
                'total_documents' => $totalDocuments,
                'study_streak' => $streak,
            ],
        ]);
    }
}
