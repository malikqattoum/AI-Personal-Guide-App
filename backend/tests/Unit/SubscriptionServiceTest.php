<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\UsageLog;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_user_can_use_within_limits(): void
    {
        $user = User::factory()->create(['subscription_tier' => 'free']);

        $this->assertTrue($user->canUse('document'));
        $this->assertTrue($user->canUse('flashcard'));
        $this->assertTrue($user->canUse('chat_message'));
        $this->assertTrue($user->canUse('audio_summary'));
    }

    public function test_free_user_cannot_exceed_document_limit(): void
    {
        $user = User::factory()->create(['subscription_tier' => 'free']);

        for ($i = 0; $i < 5; $i++) {
            UsageLog::create([
                'user_id' => $user->id,
                'action_type' => 'document',
                'count' => 1,
                'period_month' => now()->month,
                'period_year' => now()->year,
            ]);
        }

        $this->assertFalse($user->canUse('document'));
        $this->assertTrue($user->canUse('flashcard'));
    }

    public function test_free_user_cannot_exceed_flashcard_limit(): void
    {
        $user = User::factory()->create(['subscription_tier' => 'free']);

        for ($i = 0; $i < 20; $i++) {
            UsageLog::create([
                'user_id' => $user->id,
                'action_type' => 'flashcard',
                'count' => 1,
                'period_month' => now()->month,
                'period_year' => now()->year,
            ]);
        }

        $this->assertFalse($user->canUse('flashcard'));
        $this->assertTrue($user->canUse('document'));
    }

    public function test_free_user_cannot_exceed_audio_summary_limit(): void
    {
        $user = User::factory()->create(['subscription_tier' => 'free']);

        for ($i = 0; $i < 2; $i++) {
            UsageLog::create([
                'user_id' => $user->id,
                'action_type' => 'audio_summary',
                'count' => 1,
                'period_month' => now()->month,
                'period_year' => now()->year,
            ]);
        }

        $this->assertFalse($user->canUse('audio_summary'));
    }

    public function test_pro_user_has_unlimited_access(): void
    {
        $user = User::factory()->create(['subscription_tier' => 'pro']);

        for ($i = 0; $i < 100; $i++) {
            UsageLog::create([
                'user_id' => $user->id,
                'action_type' => 'document',
                'count' => 1,
                'period_month' => now()->month,
                'period_year' => now()->year,
            ]);
        }

        $this->assertTrue($user->canUse('document'));
        $this->assertTrue($user->canUse('flashcard'));
        $this->assertTrue($user->canUse('chat_message'));
        $this->assertTrue($user->canUse('audio_summary'));
    }

    public function test_usage_is_monthly(): void
    {
        $user = User::factory()->create(['subscription_tier' => 'free']);

        for ($i = 0; $i < 5; $i++) {
            UsageLog::create([
                'user_id' => $user->id,
                'action_type' => 'document',
                'count' => 1,
                'period_month' => now()->month,
                'period_year' => now()->year,
            ]);
        }

        UsageLog::create([
            'user_id' => $user->id,
            'action_type' => 'document',
            'count' => 10,
            'period_month' => now()->subMonth()->month,
            'period_year' => now()->subMonth()->year,
        ]);

        $this->assertFalse($user->canUse('document'));
    }

    public function test_get_usage_returns_correct_format(): void
    {
        $user = User::factory()->create(['subscription_tier' => 'free']);

        UsageLog::create([
            'user_id' => $user->id,
            'action_type' => 'document',
            'count' => 3,
            'period_month' => now()->month,
            'period_year' => now()->year,
        ]);

        $usage = $user->getUsage('document');

        $this->assertEquals(3, $usage['used']);
        $this->assertEquals(5, $usage['limit']);
        $this->assertFalse($usage['unlimited']);
    }

    public function test_enterprise_user_has_unlimited(): void
    {
        $user = User::factory()->create(['subscription_tier' => 'enterprise']);

        $this->assertTrue($user->canUse('document'));
        $this->assertTrue($user->canUse('flashcard'));
        $this->assertTrue($user->canUse('chat_message'));
        $this->assertTrue($user->canUse('audio_summary'));

        $usage = $user->getUsage('document');
        $this->assertTrue($usage['unlimited']);
    }

    public function test_unknown_action_type_returns_zero_limit(): void
    {
        $user = User::factory()->create(['subscription_tier' => 'free']);

        $usage = $user->getUsage('unknown_type');

        $this->assertEquals(0, $usage['used']);
        $this->assertEquals(0, $usage['limit']);
        $this->assertFalse($usage['unlimited']);
    }

    public function test_subscription_model_is_active(): void
    {
        $user = User::factory()->create();
        $subscription = Subscription::create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'tier' => 'pro',
            'status' => 'active',
        ]);

        $this->assertTrue($subscription->isActive());
        $this->assertTrue($subscription->isPaid());
    }

    public function test_subscription_model_is_not_paid_when_free(): void
    {
        $user = User::factory()->create();
        $subscription = Subscription::create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'tier' => 'free',
            'status' => 'active',
        ]);

        $this->assertFalse($subscription->isPaid());
    }

    public function test_subscription_model_is_canceled(): void
    {
        $user = User::factory()->create();
        $subscription = Subscription::create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'tier' => 'pro',
            'status' => 'canceled',
        ]);

        $this->assertFalse($subscription->isActive());
        $this->assertFalse($subscription->isPaid());
    }
}
