<?php

namespace App\Services\Payment\Gateways;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\Contracts\PaymentGateway;
use App\Services\Payment\ValueObjects\PaymentResponse;
use App\Services\Payment\ValueObjects\PaymentStatus;
use App\Settings\PaymentSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeGateway implements PaymentGateway
{
    private StripeClient $stripe;
    private string $webhookSecret;

    public function __construct(PaymentSettings $settings)
    {
        $secretKey = $settings->stripe_secret_key ?? '';

        $this->stripe = new StripeClient($secretKey);
        $this->webhookSecret = $settings->stripe_webhook_secret ?? '';
    }

    //  Interface implementation

    public function initiate(Order $order, Payment $payment): PaymentResponse
    {
        try {
            $intent = $this->stripe->paymentIntents->create([
                'amount' => $order->total_cents, // already in cents
                'currency' => strtolower($order->currency ?? 'kes'),
                'metadata' => [
                    'order_id' => $order->id,
                    'order_reference' => $order->reference,
                ],
                'description' => "Order #{$order->reference}",
                'receipt_email' => $order->user?->email,
            ]);

            $payment->update([
                'gateway_order_id' => $intent->id,
                'status' => 'processing',
                'meta' => [
                    'payment_intent_id' => $intent->id,
                    'initiated_at' => now()->toISOString(),
                ],
            ]);

            Log::info('Stripe Payment Intent created', [
                'order_id' => $order->id,
                'intent_id' => $intent->id,
            ]);

            // Return client secret — Stripe Elements uses this to confirm payment
            return PaymentResponse::inline($intent->client_secret);
        } catch (\Throwable $e) {
            Log::error('Stripe initiation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return PaymentResponse::failed($e->getMessage());
        }
    }

    public function verify(string $reference): PaymentStatus
    {
        try {
            $intent = $this->stripe->paymentIntents->retrieve($reference);

            return match ($intent->status) {
                'succeeded' => PaymentStatus::paid($intent->id, $intent->status),
                'processing' => PaymentStatus::processing(),
                'requires_payment_method',
                'requires_confirmation',
                'requires_action' => PaymentStatus::pending(),
                'canceled' => PaymentStatus::cancelled(),
                default => PaymentStatus::failed($intent->status),
            };
        } catch (\Throwable $e) {
            return PaymentStatus::failed($e->getMessage());
        }
    }

    public function handleWebhook(Request $request): void
    {
        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                $request->header('Stripe-Signature'),
                $this->webhookSecret,
            );
        } catch (\Throwable $e) {
            Log::warning('Stripe webhook signature verification failed', ['error' => $e->getMessage()]);
            abort(400);
        }

        match ($event->type) {
            'payment_intent.succeeded' => $this->handleSucceeded($event->data->object),
            'payment_intent.payment_failed' => $this->handleFailed($event->data->object),
            'payment_intent.canceled' => $this->handleCancelled($event->data->object),
            default => null,
        };
    }

    // Webhook event handlers

    private function handleSucceeded(object $intent): void
    {
        $payment = Payment::where('gateway_order_id', $intent->id)->first();
        if (!$payment)
            return;

        $payment->update([
            'status' => 'paid',
            'transaction_id' => $intent->id,
            'card_brand' => $intent->payment_method_types[0] ?? null,
            'paid_at' => now(),
            'meta' => array_merge($payment->meta ?? [], (array) $intent),
        ]);

        $payment->order?->update([
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
    }

    private function handleFailed(object $intent): void
    {
        $payment = Payment::where('gateway_order_id', $intent->id)->first();
        $payment?->update(['status' => 'failed']);
        $payment?->order?->update(['payment_status' => 'failed']);
    }

    private function handleCancelled(object $intent): void
    {
        $payment = Payment::where('gateway_order_id', $intent->id)->first();
        $payment?->update(['status' => 'cancelled']);
        $payment?->order?->update(['status' => 'cancelled', 'payment_status' => 'cancelled']);
    }
}
