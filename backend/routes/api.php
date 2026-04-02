<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AudioSummaryController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\FlashcardController;
use App\Http\Controllers\StudySessionController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Documents
    Route::get('/documents', [DocumentController::class, 'index']);
    Route::post('/documents/upload', [DocumentController::class, 'upload']);
    Route::post('/documents/youtube', [DocumentController::class, 'addYouTube']);
    Route::get('/documents/{uuid}', [DocumentController::class, 'show']);
    Route::get('/documents/{uuid}/content', [DocumentController::class, 'content']);
    Route::delete('/documents/{uuid}', [DocumentController::class, 'destroy']);

    // Flashcards
    Route::get('/flashcards', [FlashcardController::class, 'index']);
    Route::post('/flashcards/generate', [FlashcardController::class, 'generate']);
    Route::get('/flashcards/{uuid}', [FlashcardController::class, 'show']);
    Route::put('/flashcards/{uuid}/review', [FlashcardController::class, 'review']);
    Route::delete('/flashcards/{uuid}', [FlashcardController::class, 'destroy']);
    Route::get('/documents/{documentUuid}/flashcards', [FlashcardController::class, 'byDocument']);

    // Audio Summaries
    Route::post('/audio/generate', [AudioSummaryController::class, 'generate']);
    Route::get('/audio/{uuid}', [AudioSummaryController::class, 'show']);
    Route::get('/audio/{uuid}/stream', [AudioSummaryController::class, 'stream']);
    Route::delete('/audio/{uuid}', [AudioSummaryController::class, 'destroy']);
    Route::get('/documents/{documentUuid}/audio', [AudioSummaryController::class, 'byDocument']);

    // Chat
    Route::get('/chat/messages', [ChatController::class, 'messages']);
    Route::post('/chat/message', [ChatController::class, 'send']);
    Route::delete('/chat/messages', [ChatController::class, 'clear']);

    // Study Sessions
    Route::get('/sessions', [StudySessionController::class, 'index']);
    Route::post('/sessions/start', [StudySessionController::class, 'start']);
    Route::put('/sessions/{uuid}/end', [StudySessionController::class, 'end']);
    Route::get('/sessions/{uuid}', [StudySessionController::class, 'show']);
    Route::put('/sessions/{uuid}/stats', [StudySessionController::class, 'updateStats']);
    Route::get('/sessions/stats', [StudySessionController::class, 'stats']);
});
