<?php

namespace App\Services\Stripe;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Webhook;

/**
 * Orchestrates Stripe Payment Intent payments: creating the intent for the
 * client-side card form, confirming server-side after JS completes, and
 * handling the async webhook event.
 */
class StripePaymentService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create a Stripe PaymentIntent for the order. Reuses an existing pending
     * intent so refreshing the page doesn't create duplicates. The client
     * secret is never persisted (see the payments migration); it's re-fetched
     * from Stripe and carried transiently on the returned model.
     */
    public function createPaymentIntent(Order $order): Payment
    {
        $existing = $order->payments()
            ->where('provider', 'stripe')
            ->where('status', PaymentStatus::PENDING->value)
            ->whereNotNull('stripe_payment_intent_id')
            ->latest()
            ->first();

        if ($existing) {
            $intent = PaymentIntent::retrieve($existing->stripe_payment_intent_id);

            return $existing->withStripeClientSecret($intent->client_secret);
        }

        $intent = PaymentIntent::create([
            'amount' => intdiv($order->total_cents, 100),
            'currency' => 'kes',
            'payment_method_types' => ['card'],
            'metadata' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ],
        ]);

        return $order->payments()->create([
            'provider' => 'stripe',
            'status' => PaymentStatus::PENDING,
            'amount_cents' => $order->total_cents,
            'account_reference' => $order->order_number,
            'stripe_payment_intent_id' => $intent->id,
        ])->withStripeClientSecret($intent->client_secret);
    }

    /**
     * Verify and finalize a PaymentIntent after client-side confirmation.
     * Called from the Livewire component once Stripe.js reports success.
     */
    public function confirmPaymentIntent(string $paymentIntentId): ?Payment
    {
        $intent = PaymentIntent::retrieve($paymentIntentId);

        if ($intent->status !== 'succeeded') {
            return null;
        }

        $payment = Payment::where('stripe_payment_intent_id', $paymentIntentId)->first();

        if (! $payment) {
            return null;
        }

        if (! $payment->status->isFinal()) {
            $this->finalize($payment, $paymentIntentId);
        }

        return $payment->fresh();
    }

    /**
     * Verify and process an incoming Stripe webhook event.
     *
     * @throws SignatureVerificationException
     */
    public function handleWebhook(string $rawPayload, string $signature): void
    {
        $event = Webhook::constructEvent(
            $rawPayload,
            $signature,
            config('services.stripe.webhook_secret'),
        );

        if ($event->type === 'payment_intent.succeeded') {
            $this->handlePaymentIntentSucceeded($event->data->object);
        }
    }

    private function handlePaymentIntentSucceeded(PaymentIntent $intent): void
    {
        $payment = Payment::where('stripe_payment_intent_id', $intent->id)->first();

        if (! $payment || $payment->status->isFinal()) {
            return;
        }

        $this->finalize($payment, $intent->id);
    }

    private function finalize(Payment $payment, string $paymentIntentId): void
    {
        $payment->update([
            'status' => PaymentStatus::SUCCESS,
            'stripe_payment_intent_id' => $paymentIntentId,
            'paid_at' => now(),
        ]);

        $payment->order->markConfirmed();
    }
}
