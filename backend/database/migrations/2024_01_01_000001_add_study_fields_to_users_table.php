<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('uuid')->unique()->after('id');
            $table->boolean('is_premium')->default(false)->after('password');
            $table->integer('study_streak')->default(0)->after('is_premium');
            $table->integer('total_study_minutes')->default(0)->after('study_streak');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['uuid', 'is_premium', 'study_streak', 'total_study_minutes']);
        });
    }
};
