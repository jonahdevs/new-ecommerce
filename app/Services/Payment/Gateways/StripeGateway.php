<?php

namespace App\Services\Payment\Gateways;

use App\Enums\OrdersStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutSession;
use App\Services\Payment\Contracts\PaymentGateway;
use App\Services\Payment\ValueObjects\PaymentResponse;
use App\Services\Payment\ValueObjects\PaymentStatus as PaymentStatusVO;
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
        $secretKey = $settings->stripe_secret_key ?: config('services.stripe.secret_key');

        $this->stripe        = new StripeClient($secretKey);
        $this->webhookSecret = $settings->stripe_webhook_secret ?? '';
    }

    //  Interface implementation 

    public function initiate(Order $order, Payment $payment): PaymentResponse
    {
        try {
            // reuse existing intent if valid
            if ($payment->gateway_order_id && $payment->payment_url) {
                Log::info('Reusing existing Stripe PaymentIntent', [
                    'order_id'  => $order->id,
                    'intent_id' => $payment->gateway_order_id,
                ]);

                return PaymentResponse::redirect(
                    route('checkout.card-payment', ['order' => $order->reference])
                );
            }

            $intent = $this->stripe->paymentIntents->create([
                'amount'        => $order->total_cents,
                'currency'      => strtolower($order->currency ?? 'kes'),
                'metadata'      => [
                    'order_id'        => $order->id,
                    'order_reference' => $order->reference,
                ],
                'description'   => "Order #{$order->reference}",
                'receipt_email' => $order->user?->email,
            ]);

            // Store client_secret in payment_url — card payment page reads it
            $payment->update([
                'gateway_order_id' => $intent->id,
                'payment_url'      => $intent->client_secret,
                'status'           => PaymentStatus::PROCESSING->value,
                'meta'             => [
                    'payment_intent_id' => $intent->id,
                    'initiated_at'      => now()->toISOString(),
                ],
            ]);

            Log::info('Stripe PaymentIntent created', [
                'order_id'  => $order->id,
                'intent_id' => $intent->id,
            ]);

            return PaymentResponse::redirect(
                route('checkout.card-payment', ['order' => $order->reference])
            );
        } catch (\Throwable $e) {
            Log::error('Stripe initiation failed', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);

            return PaymentResponse::failed($e->getMessage());
        }
    }

    public function verify(string $reference): PaymentStatusVO
    {
        try {
            $intent = $this->stripe->paymentIntents->retrieve($reference);

            return match ($intent->status) {
                'succeeded'                => PaymentStatusVO::paid($intent->id, $intent->status),
                'processing'               => PaymentStatusVO::processing(),
                'requires_payment_method',
                'requires_confirmation',
                'requires_action'          => PaymentStatusVO::pending(),
                'canceled'                 => PaymentStatusVO::cancelled(),
                default                    => PaymentStatusVO::failed($intent->status),
            };
        } catch (\Throwable $e) {
            Log::error('Stripe verify failed', ['reference' => $reference, 'error' => $e->getMessage()]);
            return PaymentStatusVO::failed($e->getMessage());
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
            Log::warning('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);
            abort(400);
        }

        match ($event->type) {
            'payment_intent.succeeded'      => $this->handleSucceeded($event->data->object),
            'payment_intent.payment_failed' => $this->handleFailed($event->data->object),
            'payment_intent.canceled'       => $this->handleCancelled($event->data->object),
            default                         => null,
        };
    }

    //  Webhook event handlers 

    private function handleSucceeded(object $intent): void
    {
        $payment = Payment::where('gateway_order_id', $intent->id)->first();

        if (!$payment) return;

        $order = $payment->order;

        // 1. Update payment record
        $payment->update([
            'status'         => PaymentStatus::PAID->value,
            'transaction_id' => $intent->id,
            'card_brand'     => $intent->payment_method_types[0] ?? null,
            'paid_at'        => now(),
            'meta'           => array_merge($payment->meta ?? [], (array) $intent),
        ]);

        if (!$order) return;

        // 2. Transition order status — records history automatically
        $order->transitionTo(
            OrdersStatus::CONFIRMED,
            notes: 'Payment confirmed via Stripe webhook',
            changedByType: 'system'
        );
        $order->update(['payment_status' => PaymentStatus::PAID->value]);

        // 3. Clear cart — payment is confirmed
        app(CartService::class)->clear(
            User::find($order->user_id)
        );

        // 4. Clear checkout session
        app(CheckoutSession::class)->clear();

        Log::info('Stripe payment confirmed', [
            'order_id'  => $order->id,
            'intent_id' => $intent->id,
        ]);
    }

    private function handleFailed(object $intent): void
    {
        $payment = Payment::where('gateway_order_id', $intent->id)->first();

        if (!$payment) return;

        $order = $payment->order;

        // 1. Update payment record
        $payment->update([
            'status' => PaymentStatus::FAILED->value,
            'meta'   => array_merge($payment->meta ?? [], (array) $intent),
        ]);

        if (!$order) return;

        // 2. Transition order status
        $order->transitionTo(
            OrdersStatus::CANCELLED,
            notes: 'Payment failed via Stripe webhook',
            changedByType: 'system'
        );
        $order->update(['payment_status' => PaymentStatus::FAILED->value]);

        // 3. Restore stock
        $this->restoreStock($order);

        Log::info('Stripe payment failed', [
            'order_id'  => $order->id,
            'intent_id' => $intent->id,
        ]);
    }

    private function handleCancelled(object $intent): void
    {
        $payment = Payment::where('gateway_order_id', $intent->id)->first();

        if (!$payment) return;

        $order = $payment->order;

        // 1. Update payment record
        $payment->update([
            'status' => PaymentStatus::CANCELLED->value,
            'meta'   => array_merge($payment->meta ?? [], (array) $intent),
        ]);

        if (!$order) return;

        // 2. Transition order status
        $order->transitionTo(
            OrdersStatus::CANCELLED,
            notes: 'Payment cancelled via Stripe webhook',
            changedByType: 'system'
        );
        $order->update(['payment_status' => PaymentStatus::CANCELLED->value]);

        // 3. Restore stock
        $this->restoreStock($order);

        Log::info('Stripe payment cancelled', [
            'order_id'  => $order->id,
            'intent_id' => $intent->id,
        ]);
    }

    //  Private helpers 

    private function restoreStock(Order $order): void
    {
        foreach ($order->items()->with('product')->get() as $item) {
            $item->product?->increment('stock_quantity', $item->quantity);
        }
    }
}
