<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_subscription(): void
    {
        $user = User::factory()->create(['subscription_tier' => 'free']);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/subscription');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'subscription' => ['tier', 'status', 'current_period_end'],
                'usage' => [
                    'documents' => ['used', 'limit', 'unlimited'],
                    'flashcards' => ['used', 'limit', 'unlimited'],
                    'chat_messages' => ['used', 'limit', 'unlimited'],
                    'audio_summaries' => ['used', 'limit', 'unlimited'],
                ],
            ]);
    }

    public function test_user_can_view_usage(): void
    {
        $user = User::factory()->create(['subscription_tier' => 'free']);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/usage');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'usage' => [
                    'documents',
                    'flashcards',
                    'chat_messages',
                    'audio_summaries',
                ],
            ]);
    }

    public function test_protected_routes_require_auth(): void
    {
        $this->getJson('/api/subscription')->assertStatus(401);
        $this->getJson('/api/usage')->assertStatus(401);
    }
}
