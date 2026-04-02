<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('youtube_videos', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('document_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('youtube_video_id', 20);
            $table->string('title', 500);
            $table->string('thumbnail_url', 500)->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->longText('transcript_text')->nullable();
            $table->enum('transcript_status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->timestamps();

            $table->index('user_id');
            $table->index('youtube_video_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('youtube_videos');
    }
};
