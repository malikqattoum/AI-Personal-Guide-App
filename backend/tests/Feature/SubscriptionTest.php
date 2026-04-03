<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Subscription;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
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
        $this->postJson('/api/subscription/checkout')->assertStatus(401);
        $this->postJson('/api/subscription/portal')->assertStatus(401);
        $this->postJson('/api/subscription/cancel')->assertStatus(401);
    }

    public function test_checkout_validates_tier_parameter(): void
    {
        $user = User::factory()->create(['subscription_tier' => 'free']);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/subscription/checkout', ['tier' => 'invalid']);

        $response->assertStatus(422);
    }

    public function test_checkout_requires_tier_parameter(): void
    {
        $user = User::factory()->create(['subscription_tier' => 'free']);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/subscription/checkout');

        $response->assertStatus(422);
    }

    public function test_checkout_returns_error_when_already_subscribed(): void
    {
        $user = User::factory()->create(['subscription_tier' => 'pro']);
        Subscription::create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'tier' => 'pro',
            'status' => 'active',
        ]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/subscription/checkout', ['tier' => 'pro']);

        $response->assertStatus(400)
            ->assertJson(['error' => 'Already subscribed to this tier']);
    }

    public function test_checkout_creates_stripe_session(): void
    {
        $user = User::factory()->create(['subscription_tier' => 'free']);
        $token = $user->createToken('test')->plainTextToken;

        $mockSession = Mockery::mock();
        $mockSession->shouldReceive('__get')->with('url')->andReturn('https://checkout.stripe.com/test');

        $mockStripeService = Mockery::mock(StripeService::class);
        $mockStripeService->shouldReceive('createCheckoutSession')
            ->with(Mockery::type(User::class), 'pro')
            ->once()
            ->andReturn(['url' => 'https://checkout.stripe.com/test']);

        $this->app->instance(StripeService::class, $mockStripeService);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/subscription/checkout', ['tier' => 'pro']);

        $response->assertStatus(200)
            ->assertJsonStructure(['checkout_url']);
    }

    public function test_portal_returns_404_when_no_subscription(): void
    {
        $user = User::factory()->create(['subscription_tier' => 'free']);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/subscription/portal');

        $response->assertStatus(404)
            ->assertJson(['error' => 'No subscription found']);
    }

    public function test_portal_creates_portal_session(): void
    {
        $user = User::factory()->create(['subscription_tier' => 'pro']);
        Subscription::create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'tier' => 'pro',
            'status' => 'active',
            'stripe_customer_id' => 'cus_test123',
        ]);
        $token = $user->createToken('test')->plainTextToken;

        $mockStripeService = Mockery::mock(StripeService::class);
        $mockStripeService->shouldReceive('createCustomerPortalSession')
            ->with(Mockery::type(User::class))
            ->once()
            ->andReturn(['url' => 'https://billing.stripe.com/portal']);

        $this->app->instance(StripeService::class, $mockStripeService);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/subscription/portal');

        $response->assertStatus(200)
            ->assertJsonStructure(['portal_url']);
    }

    public function test_cancel_returns_error_for_free_user(): void
    {
        $user = User::factory()->create(['subscription_tier' => 'free']);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/subscription/cancel');

        $response->assertStatus(400)
            ->assertJson(['error' => 'No active subscription']);
    }

    public function test_cancel_cancels_subscription(): void
    {
        $user = User::factory()->create(['subscription_tier' => 'pro']);
        Subscription::create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'tier' => 'pro',
            'status' => 'active',
            'stripe_subscription_id' => 'sub_test123',
        ]);
        $token = $user->createToken('test')->plainTextToken;

        $mockStripeService = Mockery::mock(StripeService::class);
        $mockStripeService->shouldReceive('cancelSubscription')
            ->with(Mockery::type(User::class))
            ->once()
            ->andReturn(true);

        $this->app->instance(StripeService::class, $mockStripeService);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/subscription/cancel');

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'canceled_at']);
    }
}
