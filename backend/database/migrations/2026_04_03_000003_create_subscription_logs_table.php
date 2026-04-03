<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('event');
            $table->string('stripe_event_id')->nullable();
            $table->string('old_tier')->nullable();
            $table->string('new_tier');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('stripe_event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_logs');
    }
};
