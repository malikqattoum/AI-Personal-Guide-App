<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flashcards', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('front_text');
            $table->text('back_text');
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $table->integer('times_reviewed')->default(0);
            $table->integer('times_correct')->default(0);
            $table->timestamp('last_reviewed_at')->nullable();
            $table->timestamps();

            $table->index('document_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flashcards');
    }
};
