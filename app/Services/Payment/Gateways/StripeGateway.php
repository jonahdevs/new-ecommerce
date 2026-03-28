<?php

namespace App\Services\Payment\Gateways;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\PaymentConfirmed;
use App\Jobs\SyncOrderToSapJob;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutSession;
use App\Services\DocumentService;
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

        $this->stripe = new StripeClient($secretKey);

        $this->webhookSecret = $settings->stripe_webhook_secret
            ?: config('services.stripe.webhook_secret')
            ?: '';
    }

    public function initiate(Order $order, Payment $payment): PaymentResponse
    {
        try {
            if ($payment->gateway_order_id && $payment->payment_url) {
                if ($payment->status === PaymentStatus::FAILED->value) {
                    Log::info('Previous Stripe PaymentIntent failed, creating new one', [
                        'order_id'   => $order->id,
                        'old_intent' => $payment->gateway_order_id,
                    ]);
                } else {
                    Log::info('Reusing existing Stripe PaymentIntent', [
                        'order_id'  => $order->id,
                        'intent_id' => $payment->gateway_order_id,
                    ]);

                    return PaymentResponse::redirect(
                        route('checkout.pay', ['order' => $order->reference])
                    );
                }
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

            $payment->update([
                'gateway_order_id' => $intent->id,
                'payment_url'      => $intent->client_secret,
                'status'           => PaymentStatus::PROCESSING->value,
                'meta'             => array_merge($payment->meta ?? [], [
                    'payment_intent_id' => $intent->id,
                    'initiated_at'      => now()->toISOString(),
                    'retry_count'       => ($payment->meta['retry_count'] ?? 0) + 1,
                    'previous_intent'   => $payment->gateway_order_id,
                ]),
            ]);

            $order->update(['payment_status' => PaymentStatus::PROCESSING->value]);

            Log::info('Stripe PaymentIntent created', [
                'order_id'  => $order->id,
                'intent_id' => $intent->id,
            ]);

            return PaymentResponse::redirect(
                route('checkout.pay', ['order' => $order->reference])
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
                'succeeded'                                                       => PaymentStatusVO::paid($intent->id, $intent->status),
                'processing'                                                      => PaymentStatusVO::processing(),
                'requires_payment_method', 'requires_confirmation', 'requires_action' => PaymentStatusVO::pending(),
                'canceled'                                                        => PaymentStatusVO::cancelled(),
                default                                                           => PaymentStatusVO::failed($intent->status),
            };
        } catch (\Throwable $e) {
            Log::error('Stripe verify failed', [
                'reference' => $reference,
                'error'     => $e->getMessage(),
            ]);
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
            'payment_intent.succeeded'       => $this->handleSucceeded($event->data->object),
            'payment_intent.payment_failed'  => $this->handleFailed($event->data->object),
            'payment_intent.canceled'        => $this->handleCancelled($event->data->object),
            'payment_intent.requires_action' => $this->handleRequiresAction($event->data->object),
            default                          => null,
        };
    }

    private function handleSucceeded(object $intent): void
    {
        $payment = Payment::where('gateway_order_id', $intent->id)->first();
        if (!$payment) return;

        // Idempotency guard — webhook may be delivered more than once
        if ($payment->status === PaymentStatus::PAID->value) {
            Log::info('Stripe webhook already processed, skipping', [
                'intent_id' => $intent->id,
            ]);
            return;
        }

        $order = $payment->order;
        $originalMethod = $payment->meta['payment_method'] ?? null;

        $mergedMeta = array_merge($payment->meta ?? [], $intent->toArray());
        if ($originalMethod) {
            $mergedMeta['payment_method'] = $originalMethod;
        }

        $payment->update([
            'status'         => PaymentStatus::PAID->value,
            'transaction_id' => $intent->id,
            'card_brand'     => $intent->payment_method_types[0] ?? null,
            'paid_at'        => now(),
            'meta'           => $mergedMeta,
            'gateway'        => 'card',
        ]);

        if (!$order) return;

        // Transition order to confirmed — this is the first state change
        // that means the order is real and should flow downstream
        $order->transitionTo(
            OrderStatus::CONFIRMED,
            notes: 'Payment confirmed via Stripe webhook',
            changedByType: 'system',
        );

        $order->update(['payment_status' => PaymentStatus::PAID->value]);

        // Generate the tax invoice PDF
        app(DocumentService::class)->generateInvoice($order);

        // Clear the cart and session now payment is confirmed
        app(CartService::class)->clear(User::find($order->user_id));
        app(CheckoutSession::class)->clear();

        // -------------------------------------------------------
        // Dispatch SAP sync — fires HERE, after payment is
        // confirmed and order is CONFIRMED. This is the correct
        // trigger point: the order is real, payment is settled,
        // and all three SAP documents (Sales Order, A/R Invoice,
        // Incoming Payment) reflect an actual confirmed sale.
        //
        // Moved from CheckoutService::initiateCheckout() where it
        // fired too early (before the customer had paid).
        // -------------------------------------------------------
        SyncOrderToSapJob::dispatch($order->fresh());

        // Broadcast payment confirmed event for any listeners
        PaymentConfirmed::dispatch($order->fresh(['payment']));

        Log::info('Stripe payment confirmed, SAP sync dispatched', [
            'order_id'  => $order->id,
            'intent_id' => $intent->id,
        ]);
    }

    private function handleFailed(object $intent): void
    {
        $payment = Payment::where('gateway_order_id', $intent->id)->first();
        if (!$payment) return;

        if ($payment->status === PaymentStatus::FAILED->value) {
            Log::info('Stripe failure webhook already processed, skipping', [
                'intent_id' => $intent->id,
            ]);
            return;
        }

        $order = $payment->order;
        $originalMethod = $payment->meta['payment_method'] ?? null;

        $mergedMeta = array_merge($payment->meta ?? [], $intent->toArray());
        if ($originalMethod) {
            $mergedMeta['payment_method'] = $originalMethod;
        }

        $payment->update([
            'status' => PaymentStatus::FAILED->value,
            'meta'   => $mergedMeta,
        ]);

        if (!$order) return;

        $order->transitionTo(
            OrderStatus::CANCELLED,
            notes: 'Payment failed via Stripe webhook',
            changedByType: 'system',
        );
        $order->update(['payment_status' => PaymentStatus::FAILED->value]);

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

        if ($payment->status === PaymentStatus::CANCELLED->value) {
            Log::info('Stripe cancellation webhook already processed, skipping', [
                'intent_id' => $intent->id,
            ]);
            return;
        }

        $order = $payment->order;

        $payment->update([
            'status' => PaymentStatus::CANCELLED->value,
            'meta'   => array_merge($payment->meta ?? [], $intent->toArray()),
        ]);

        if (!$order) return;

        $order->transitionTo(
            OrderStatus::CANCELLED,
            notes: 'Payment cancelled via Stripe webhook',
            changedByType: 'system',
        );
        $order->update(['payment_status' => PaymentStatus::CANCELLED->value]);

        $this->restoreStock($order);

        Log::info('Stripe payment cancelled', [
            'order_id'  => $order->id,
            'intent_id' => $intent->id,
        ]);
    }

    private function handleRequiresAction(object $intent): void
    {
        $payment = Payment::where('gateway_order_id', $intent->id)->first();
        if (!$payment) return;

        if (in_array($payment->status, [
            PaymentStatus::PAID->value,
            PaymentStatus::FAILED->value,
            PaymentStatus::CANCELLED->value,
        ])) return;

        $payment->update([
            'status' => PaymentStatus::PROCESSING->value,
            'meta'   => array_merge($payment->meta ?? [], [
                'requires_action' => true,
                'action_type'     => $intent->next_action?->type ?? 'unknown',
                'updated_at'      => now()->toISOString(),
            ]),
        ]);

        Log::info('Stripe payment requires customer action (3DS)', [
            'order_id'   => $payment->order_id,
            'intent_id'  => $intent->id,
            'action_type' => $intent->next_action?->type ?? 'unknown',
        ]);
    }

    private function restoreStock(Order $order): void
    {
        foreach ($order->items()->with('product')->get() as $item) {
            $item->product?->increment('stock_quantity', $item->quantity);
        }
    }
}
