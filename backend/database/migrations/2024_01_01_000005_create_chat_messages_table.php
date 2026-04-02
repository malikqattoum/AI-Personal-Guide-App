<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('study_session_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('role', ['user', 'assistant', 'system']);
            $table->text('message_text');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('document_id');
            $table->index('study_session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
