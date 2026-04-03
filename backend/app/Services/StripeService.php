<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\User;
use App\Models\SubscriptionLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StripeService
{
    protected string $secretKey;
    protected string $webhookSecret;
    protected string $proPriceId;
    protected string $enterprisePriceId;

    public function __construct()
    {
        $this->secretKey = config('services.stripe.secret');
        $this->webhookSecret = config('services.stripe.webhook_secret');
        $this->proPriceId = config('services.stripe.pro_price_id');
        $this->enterprisePriceId = config('services.stripe.enterprise_price_id');
    }

    public function getPriceIdForTier(string $tier): ?string
    {
        return match($tier) {
            'pro' => $this->proPriceId,
            'enterprise' => $this->enterprisePriceId,
            default => null,
        };
    }

    public function createCheckoutSession(User $user, string $tier): array
    {
        $priceId = $this->getPriceIdForTier($tier);

        if (!$priceId) {
            throw new \Exception('Invalid tier');
        }

        $customerId = $this->getOrCreateCustomer($user);

        $sessionData = [
            'customer' => $customerId,
            'mode' => 'subscription',
            'line_items' => [
                [
                    'price' => $priceId,
                    'quantity' => 1,
                ],
            ],
            'success_url' => config('app.url') . '/subscription/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => config('app.url') . '/subscription/cancel',
            'metadata' => [
                'user_id' => $user->id,
                'tier' => $tier,
            ],
        ];

        $response = Http::withToken($this->secretKey)
            ->post('https://api.stripe.com/v1/checkout/sessions', $sessionData);

        if (!$response->successful()) {
            throw new \Exception('Failed to create checkout session: ' . $response->body());
        }

        return $response->json();
    }

    public function createCustomerPortalSession(User $user): array
    {
        $subscription = $user->subscription;

        if (!$subscription || !$subscription->stripe_customer_id) {
            throw new \Exception('No subscription found');
        }

        $response = Http::withToken($this->secretKey)
            ->post('https://api.stripe.com/v1/billing_portal/sessions', [
                'customer' => $subscription->stripe_customer_id,
                'return_url' => config('app.url'),
            ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to create portal session');
        }

        return $response->json();
    }

    public function cancelSubscription(User $user): bool
    {
        $subscription = $user->subscription;

        if (!$subscription || !$subscription->stripe_subscription_id) {
            throw new \Exception('No active subscription found');
        }

        $response = Http::withToken($this->secretKey)
            ->post("https://api.stripe.com/v1/subscriptions/{$subscription->stripe_subscription_id}", [
                'cancel_at_period_end' => true,
            ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to cancel subscription');
        }

        $subscription->canceled_at = now();
        $subscription->save();

        return true;
    }

    public function getOrCreateCustomer(User $user): string
    {
        if ($user->stripe_customer_id) {
            return $user->stripe_customer_id;
        }

        $response = Http::withToken($this->secretKey)
            ->post('https://api.stripe.com/v1/customers', [
                'email' => $user->email,
                'name' => $user->name,
                'metadata' => [
                    'user_id' => $user->id,
                ],
            ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to create Stripe customer');
        }

        $customerId = $response->json('id');
        $user->stripe_customer_id = $customerId;
        $user->save();

        return $customerId;
    }

    public function handleWebhookEvent(object $event): void
    {
        $data = $event->data->object;

        switch ($event->type) {
            case 'checkout.session.completed':
                $this->handleCheckoutCompleted($data);
                break;

            case 'customer.subscription.created':
            case 'customer.subscription.updated':
                $this->handleSubscriptionUpdated($data);
                break;

            case 'customer.subscription.deleted':
                $this->handleSubscriptionDeleted($data);
                break;

            case 'invoice.payment_failed':
                $this->handlePaymentFailed($data);
                break;

            case 'invoice.payment_succeeded':
                $this->handlePaymentSucceeded($data);
                break;
        }
    }

    protected function handleCheckoutCompleted(object $data): void
    {
        $userId = $data->metadata->user_id ?? null;
        $tier = $data->metadata->tier ?? 'free';

        if (!$userId) {
            Log::warning('Stripe webhook: checkout completed without user_id', [
                'data' => json_decode(json_encode($data), true),
            ]);
            return;
        }

        $user = User::find($userId);
        if (!$user) {
            Log::warning('Stripe webhook: user not found for checkout completed', [
                'user_id' => $userId,
                'stripe_customer_id' => $data->customer ?? null,
            ]);
            return;
        }

        $amount = isset($data->amount) ? $data->amount / 100 : null;
        $this->createOrUpdateSubscription($user, $data->subscription, $data->customer, $amount, $tier);
    }

    protected function handleSubscriptionUpdated(object $data): void
    {
        $customerId = $data->customer;
        $user = User::where('stripe_customer_id', $customerId)->first();

        if (!$user) {
            Log::warning('Stripe webhook: user not found for subscription updated', [
                'stripe_customer_id' => $customerId,
            ]);
            return;
        }

        $tier = $this->determineTierFromPrice($data->items->data[0]->price->id ?? null);
        $status = $data->status;

        $amount = isset($data->items->data[0]->price->unit_amount) ? $data->items->data[0]->price->unit_amount / 100 : null;
        $this->createOrUpdateSubscription($user, $data->id, $customerId, $amount, $tier, $status);

        if ($user->subscription) {
            $user->subscription->update([
                'current_period_start' => Carbon::createFromTimestamp($data->current_period_start),
                'current_period_end' => Carbon::createFromTimestamp($data->current_period_end),
                'canceled_at' => $data->cancel_at ? Carbon::createFromTimestamp($data->cancel_at) : null,
            ]);
        }
    }

    protected function handleSubscriptionDeleted(object $data): void
    {
        $customerId = $data->customer;
        $user = User::where('stripe_customer_id', $customerId)->first();

        if (!$user) {
            Log::warning('Stripe webhook: user not found for subscription deleted', [
                'stripe_customer_id' => $customerId,
            ]);
            return;
        }

        $oldTier = $user->subscription_tier;

        if ($user->subscription) {
            $user->subscription->update([
                'status' => 'canceled',
                'tier' => 'free',
            ]);
        }

        $user->subscription_tier = 'free';
        $user->save();

        SubscriptionLog::create([
            'user_id' => $user->id,
            'event' => 'customer.subscription.deleted',
            'stripe_event_id' => $data->id,
            'old_tier' => $oldTier,
            'new_tier' => 'free',
            'metadata' => json_decode(json_encode($data), true),
        ]);
    }

    protected function handlePaymentFailed(object $data): void
    {
        $customerId = $data->customer;
        $user = User::where('stripe_customer_id', $customerId)->first();

        if (!$user || !$user->subscription) {
            Log::warning('Stripe webhook: user/subscription not found for payment failed', [
                'stripe_customer_id' => $customerId,
            ]);
            return;
        }

        $user->subscription->update(['status' => 'past_due']);
    }

    protected function handlePaymentSucceeded(object $data): void
    {
        $customerId = $data->customer;
        $user = User::where('stripe_customer_id', $customerId)->first();

        if (!$user || !$user->subscription) {
            Log::warning('Stripe webhook: user/subscription not found for payment succeeded', [
                'stripe_customer_id' => $customerId,
            ]);
            return;
        }

        $user->subscription->update(['status' => 'active']);
    }

    protected function createOrUpdateSubscription(User $user, string $subscriptionId, string $customerId, ?float $amount, string $tier, string $status = 'active'): void
    {
        DB::transaction(function () use ($user, $subscriptionId, $customerId, $amount, $tier, $status) {
            $oldTier = $user->subscription_tier;

            $subscription = Subscription::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'stripe_subscription_id' => $subscriptionId,
                    'stripe_customer_id' => $customerId,
                    'tier' => $tier,
                    'status' => $status,
                ]
            );

            $user->subscription_tier = $tier;
            $user->save();

            SubscriptionLog::create([
                'user_id' => $user->id,
                'event' => 'subscription_created',
                'stripe_event_id' => $subscriptionId,
                'old_tier' => $oldTier,
                'new_tier' => $tier,
                'metadata' => ['amount' => $amount],
            ]);
        });
    }

    protected function determineTierFromPrice(?string $priceId): string
    {
        if ($priceId === $this->proPriceId) {
            return 'pro';
        }
        if ($priceId === $this->enterprisePriceId) {
            return 'enterprise';
        }
        return 'free';
    }
}
