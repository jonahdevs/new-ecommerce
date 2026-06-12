<?php

namespace App\Services\Stripe;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentCredentials;
use Illuminate\Support\Facades\Log;
use Stripe\Charge;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Stripe;
use Stripe\Webhook;

/**
 * Orchestrates Stripe Payment Intent payments: creating the intent for the
 * client-side card form, confirming server-side after JS completes, and
 * handling the async webhook event.
 */
class StripePaymentService
{
    public function __construct(private PaymentCredentials $credentials)
    {
        Stripe::setApiKey($this->credentials->stripeSecret());
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
            $intent = PaymentIntent::retrieve($existing->stripe_payment_intent_id, [
                'expand' => ['latest_charge'],
            ]);

            // The intent may have succeeded server-side but finalization crashed
            // (e.g. the charge-expansion bug). Recover silently so the next page
            // load redirects to the order rather than showing a broken form.
            if ($intent->status === 'succeeded') {
                $this->finalize($existing, $intent);

                return $existing->fresh();
            }

            return $existing->withStripeClientSecret($intent->client_secret);
        }

        $intent = PaymentIntent::create([
            'amount' => $order->total_cents,
            'currency' => 'kes',
            'payment_method_types' => ['card'],
            'metadata' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ],
        ]);

        $payment = $order->payments()->create([
            'provider' => 'stripe',
            'status' => PaymentStatus::PENDING,
            'amount_cents' => $order->total_cents,
            'currency' => 'KES',
            'account_reference' => $order->order_number,
            'stripe_payment_intent_id' => $intent->id,
        ]);

        return $payment->withStripeClientSecret($intent->client_secret);
    }

    /**
     * Refund (fully or partially) a settled card payment through Stripe. Throws
     * a Stripe exception on failure so the caller can abort without recording a
     * refund that never happened.
     */
    public function refund(Payment $payment, int $amountCents): void
    {
        Refund::create([
            'payment_intent' => $payment->stripe_payment_intent_id,
            'amount' => $amountCents,
        ]);
    }

    /**
     * Verify and finalize a PaymentIntent after client-side confirmation.
     * Called from the Livewire component once Stripe.js reports success.
     */
    public function confirmPaymentIntent(string $paymentIntentId): ?Payment
    {
        $intent = PaymentIntent::retrieve($paymentIntentId, [
            'expand' => ['latest_charge'],
        ]);

        if ($intent->status !== 'succeeded') {
            return null;
        }

        $payment = Payment::where('stripe_payment_intent_id', $paymentIntentId)->first();

        if (! $payment) {
            return null;
        }

        if (! $payment->status->isFinal()) {
            $this->finalize($payment, $intent);
        }

        $payment = $payment->fresh();

        // Only treat the payment as confirmed when it actually succeeded — a
        // rejected (amount/currency mismatch) finalize leaves it FAILED, and the
        // caller must not advance the order in that case.
        return $payment->status === PaymentStatus::SUCCESS ? $payment : null;
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
            $this->credentials->stripeWebhookSecret(),
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

        // Re-fetch with charge expansion — webhook payloads don't expand nested objects.
        $expanded = PaymentIntent::retrieve($intent->id, [
            'expand' => ['latest_charge'],
        ]);

        $this->finalize($payment, $expanded);
    }

    private function finalize(Payment $payment, PaymentIntent $intent): void
    {
        // The PaymentIntent is authoritative — confirm it charged the exact amount
        // and currency we created the intent with before advancing the order. This
        // guards against a tampered/replayed confirmation or a mismatched intent.
        if ($intent->amount !== $payment->amount_cents
            || strtolower((string) $intent->currency) !== strtolower($payment->currency)) {
            Log::critical('Stripe amount/currency mismatch — payment rejected.', [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'expected_cents' => $payment->amount_cents,
                'expected_currency' => $payment->currency,
                'intent_amount' => $intent->amount,
                'intent_currency' => $intent->currency,
            ]);

            $payment->update([
                'status' => PaymentStatus::FAILED,
                'result_desc' => 'Amount or currency mismatch on Stripe confirmation.',
            ]);

            return;
        }

        $charge = $intent->latest_charge;

        // Stripe returns the charge ID as a plain string when the expand didn't
        // reach it (e.g. webhook payloads, or SDK version differences). Fetch it.
        if (is_string($charge) && $charge !== '') {
            $charge = Charge::retrieve($charge);
        }

        $card = $charge?->payment_method_details?->card;

        $payment->update([
            'status' => PaymentStatus::SUCCESS,
            'stripe_charge_id' => $charge?->id,
            'card_brand' => $card?->brand,
            'card_last4' => $card?->last4,
            'paid_at' => now(),
        ]);

        $payment->order->markConfirmed();
    }
}
