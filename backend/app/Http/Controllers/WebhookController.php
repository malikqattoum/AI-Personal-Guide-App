<?php

namespace App\Http\Controllers;

use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;

class WebhookController extends Controller
{
    protected StripeService $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    public function handleStripe(Request $request): \Illuminate\Http\Response
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $signature,
                config('services.stripe.webhook_secret')
            );

            $this->stripeService->handleWebhookEvent($event);

            Log::info('Stripe webhook handled', [
                'type' => $event->type,
                'id' => $event->id,
            ]);

            return response('OK', 200);
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature verification failed', [
                'error_id' => 'WH_' . uniqid(),
                'error' => $e->getMessage(),
            ]);

            return response('Invalid signature', 400);
        } catch (\Exception $e) {
            Log::error('Stripe webhook failed', [
                'error_id' => 'WH_' . uniqid(),
                'error' => $e->getMessage(),
            ]);

            return response('Webhook failed', 400);
        }
    }
}
