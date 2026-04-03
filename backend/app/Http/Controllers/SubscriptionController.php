<?php

namespace App\Http\Controllers;

use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\InvalidRequestException;
use Stripe\Exception\AuthenticationException;

class SubscriptionController extends Controller
{
    protected StripeService $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $subscription = $user->subscription;

        $usage = [
            'documents' => $user->getUsage('document'),
            'flashcards' => $user->getUsage('flashcard'),
            'chat_messages' => $user->getUsage('chat_message'),
            'audio_summaries' => $user->getUsage('audio_summary'),
        ];

        return response()->json([
            'subscription' => [
                'tier' => $user->subscription_tier,
                'status' => $subscription?->status,
                'current_period_end' => $subscription?->current_period_end,
                'canceled_at' => $subscription?->canceled_at,
            ],
            'usage' => $usage,
        ]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $request->validate([
            'tier' => 'required|in:pro,enterprise',
        ]);

        $user = $request->user();

        if ($user->subscription_tier === $request->tier) {
            return response()->json(['error' => 'Already subscribed to this tier'], 400);
        }

        try {
            $session = $this->stripeService->createCheckoutSession($user, $request->tier);

            return response()->json([
                'checkout_url' => $session['url'],
            ]);
        } catch (InvalidRequestException $e) {
            Log::error('Stripe invalid request during checkout', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid subscription request'], 400);
        } catch (AuthenticationException $e) {
            Log::critical('Stripe API authentication failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Payment service configuration error'], 500);
        } catch (\Exception $e) {
            Log::error('Checkout session failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to create checkout session'], 500);
        }
    }

    public function portal(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->subscription || !$user->subscription->stripe_customer_id) {
            return response()->json(['error' => 'No subscription found'], 404);
        }

        try {
            $session = $this->stripeService->createCustomerPortalSession($user);

            return response()->json([
                'portal_url' => $session['url'],
            ]);
        } catch (InvalidRequestException $e) {
            Log::error('Stripe invalid request during portal', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid portal request'], 400);
        } catch (AuthenticationException $e) {
            Log::critical('Stripe API authentication failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Payment service configuration error'], 500);
        } catch (\Exception $e) {
            Log::error('Portal session failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to create portal session'], 500);
        }
    }

    public function cancel(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->subscription_tier === 'free') {
            return response()->json(['error' => 'No active subscription'], 400);
        }

        try {
            $this->stripeService->cancelSubscription($user);

            return response()->json([
                'message' => 'Subscription will cancel at period end',
                'canceled_at' => $user->subscription->canceled_at,
            ]);
        } catch (InvalidRequestException $e) {
            Log::error('Stripe invalid request during cancel', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid cancellation request'], 400);
        } catch (AuthenticationException $e) {
            Log::critical('Stripe API authentication failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Payment service configuration error'], 500);
        } catch (\Exception $e) {
            Log::error('Cancel subscription failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to cancel subscription'], 500);
        }
    }

    public function usage(Request $request): JsonResponse
    {
        $user = $request->user();

        $usage = [
            'documents' => $user->getUsage('document'),
            'flashcards' => $user->getUsage('flashcard'),
            'chat_messages' => $user->getUsage('chat_message'),
            'audio_summaries' => $user->getUsage('audio_summary'),
        ];

        return response()->json(['usage' => $usage]);
    }
}
