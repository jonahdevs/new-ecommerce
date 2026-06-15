<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Notifications\Orders\RefundProcessed;
use App\Services\Paystack\PaystackPaymentService;
use App\Services\Stripe\StripePaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Refunds a settled payment: reverses it at the gateway where supported, records
 * the refund (full or partial), advances the order, and notifies the customer.
 *
 * Paystack and Stripe refunds are issued live through the gateway API — Paystack
 * reverses every channel (cards, M-Pesa, Airtel Money, bank transfer) for us.
 * Direct M-Pesa (Daraja) has no automated reversal in this integration — the
 * refund is recorded and the customer is notified, while finance reverses the
 * transaction through the Safaricom portal out of band.
 */
class RefundService
{
    /**
     * @throws InvalidArgumentException when the amount is invalid for this payment
     */
    public function refund(Payment $payment, int $amountCents, ?string $reason = null, ?int $byUserId = null): Payment
    {
        if ($payment->status !== PaymentStatus::SUCCESS) {
            throw new InvalidArgumentException('Only a settled payment can be refunded.');
        }

        $alreadyRefunded = (int) $payment->refund_cents;
        $remaining = (int) $payment->amount_cents - $alreadyRefunded;

        if ($amountCents <= 0 || $amountCents > $remaining) {
            throw new InvalidArgumentException("Refund amount must be between 1 and {$remaining} cents.");
        }

        // Reverse at the gateway first — a rejected gateway refund must not leave
        // a recorded refund that never actually happened. Direct M-Pesa reversals
        // are processed manually, so there is no gateway call for them.
        if ($payment->provider === 'paystack') {
            app(PaystackPaymentService::class)->refund($payment, $amountCents);
        } elseif ($payment->provider === 'stripe') {
            app(StripePaymentService::class)->refund($payment, $amountCents);
        }

        return DB::transaction(function () use ($payment, $amountCents, $alreadyRefunded, $reason, $byUserId) {
            $totalRefunded = $alreadyRefunded + $amountCents;
            $fullyRefunded = $totalRefunded >= (int) $payment->amount_cents;

            $payment->update([
                'refund_cents' => $totalRefunded,
                'refunded_at' => now(),
                'status' => $fullyRefunded ? PaymentStatus::REFUNDED : PaymentStatus::SUCCESS,
            ]);

            $order = $payment->order;

            if ($order && $fullyRefunded && $order->status !== OrderStatus::REFUNDED) {
                $from = $order->status;
                $order->update(['status' => OrderStatus::REFUNDED]);
                $order->recordStatusChange($from, OrderStatus::REFUNDED, $reason ? "Refunded: {$reason}" : 'Refunded.', $byUserId);
            }

            if ($order) {
                $order->user?->notify(new RefundProcessed($order, $amountCents, $reason));

                if ($payment->provider === 'mpesa') {
                    Log::warning('M-Pesa refund recorded — reverse the transaction manually via Safaricom.', [
                        'payment_id' => $payment->id,
                        'order_id' => $order->id,
                        'amount_cents' => $amountCents,
                        'mpesa_receipt' => $payment->mpesa_receipt,
                    ]);
                }
            }

            return $payment->fresh();
        });
    }
}
