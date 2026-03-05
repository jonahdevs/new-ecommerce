<?php

namespace App\Services\Payment\Gateways;

use App\Models\Order;
use App\Models\Payment;
use App\Services\CheckoutSession;
use App\Services\Payment\Contracts\PaymentGateway;
use App\Services\Payment\ValueObjects\{PaymentResponse, PaymentStatus};
use Illuminate\Http\Request;

/**
 * CustomGateway
 *
 * Composite gateway that delegates to either MpesaGateway or StripeGateway
 * based on the customer's chosen payment method stored in session.
 *
 * Session key: checkout.payment_method = 'mpesa' | 'card'
 *
 * The customer selects this on the checkout summary page before placing order.
 */
class CustomGateway implements PaymentGateway
{
    public function __construct(
        private readonly MpesaGateway  $mpesa,
        private readonly StripeGateway $stripe,
    ) {}

    public function initiate(Order $order, Payment $payment): PaymentResponse
    {
        $method = app(CheckoutSession::class)->getPaymentMethod();

        $payment->update([
            'meta' => array_merge($payment->meta ?? [], [
                'payment_method' => $method,
            ]),
        ]);

        if ($method === 'card') {
            return $this->stripe->initiate($order, $payment);
        }

        return PaymentResponse::redirect(
            route('checkout.pay', ['order' => $order->reference])
        );
    }

    public function verify(string $reference): PaymentStatus
    {
        // Read gateway from payment record — session may already be cleared
        $payment = Payment::where('transaction_id', $reference)
            ->orWhere('gateway_order_id', $reference)
            ->first();

        $method = $payment?->meta['payment_method'] ?? 'mpesa';

        return match ($method) {
            'card'  => $this->stripe->verify($reference),
            'mpesa' => $this->mpesa->verify($reference),
            default => $this->mpesa->verify($reference),
        };
    }

    public function handleWebhook(Request $request): void
    {
        // Webhooks are handled by the individual gateway controllers directly,
        // not through CustomGateway. This method intentionally does nothing.
    }
}
