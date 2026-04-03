<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'uuid',
        'study_streak',
        'total_study_minutes',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function flashcards(): HasMany
    {
        return $this->hasMany(Flashcard::class);
    }

    public function audioSummaries(): HasMany
    {
        return $this->hasMany(AudioSummary::class);
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function studySessions(): HasMany
    {
        return $this->hasMany(StudySession::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    public function usageLogs(): HasMany
    {
        return $this->hasMany(UsageLog::class);
    }

    public function subscriptionLogs(): HasMany
    {
        return $this->hasMany(SubscriptionLog::class);
    }

    public function getSubscriptionTierAttribute(): string
    {
        return $this->subscription?->tier ?? 'free';
    }

    public function canUse(string $actionType): bool
    {
        $tier = $this->subscription_tier;

        if (in_array($tier, ['pro', 'enterprise'])) {
            return true;
        }

        $limits = [
            'document' => 5,
            'flashcard' => 20,
            'chat_message' => 20,
            'audio_summary' => 2,
        ];

        $limit = $limits[$actionType] ?? 0;
        $used = UsageLog::getMonthlyUsage($this->id, $actionType);

        return $used < $limit;
    }

    public function getUsage(string $actionType): array
    {
        $tier = $this->subscription_tier;

        $limits = [
            'document' => 5,
            'flashcard' => 20,
            'chat_message' => 20,
            'audio_summary' => 2,
        ];

        $limit = $limits[$actionType] ?? 0;
        $used = UsageLog::getMonthlyUsage($this->id, $actionType);

        return [
            'used' => $used,
            'limit' => $limit,
            'unlimited' => in_array($tier, ['pro', 'enterprise']),
        ];
    }

    public function logUsage(string $actionType): void
    {
        UsageLog::create([
            'user_id' => $this->id,
            'action_type' => $actionType,
            'count' => 1,
            'period_month' => now()->month,
            'period_year' => now()->year,
        ]);
    }
}
