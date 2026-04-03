<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('action_type', ['document', 'flashcard', 'chat_message', 'audio_summary']);
            $table->integer('count')->default(1);
            $table->integer('period_month');
            $table->integer('period_year');
            $table->timestamps();

            $table->index(['user_id', 'action_type', 'period_month', 'period_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_logs');
    }
};
