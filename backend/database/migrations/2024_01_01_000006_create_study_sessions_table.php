<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration_minutes')->default(0);
            $table->integer('flashcards_reviewed')->default(0);
            $table->integer('flashcards_correct')->default(0);
            $table->enum('session_type', ['free_study', 'flashcard_review', 'audio_review', 'chat_session'])->default('free_study');
            $table->timestamps();

            $table->index('user_id');
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_sessions');
    }
};
