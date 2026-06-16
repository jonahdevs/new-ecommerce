<?php

namespace App\Livewire\Concerns;

use App\Models\Order;
use App\Services\PaymentCredentials;
use App\Services\Paystack\PaystackPaymentService;
use App\Support\StorefrontSession;
use Flux\Flux;

/**
 * Shared Paystack inline-popup flow for Livewire pages that take payment
 * (checkout, the payment page, and quote approval). The popup keeps the
 * customer on the page; callers fall back to the payment page when Paystack
 * is unavailable.
 */
trait InteractsWithPaystack
{
    /**
     * Whether the inline popup can run — the gateway is enabled and its secret
     * key is configured. Used both server-side and to gate the pay button.
     */
    public function paystackEnabled(): bool
    {
        return app(PaymentCredentials::class)->paystackEnabled();
    }

    /**
     * Initialize a Paystack transaction for the order and tell the browser to
     * open the inline popup. Returns false when Paystack is unavailable or the
     * initialization fails, so the caller can fall back to the payment page.
     */
    protected function openPaystack(Order $order): bool
    {
        if (! $this->paystackEnabled()) {
            return false;
        }

        try {
            $payment = app(PaystackPaymentService::class)->initialize($order);
        } catch (\Throwable $e) {
            report($e);

            return false;
        }

        $order->update(['payment_method' => 'paystack']);
        $this->dispatch('paystack-open', accessCode: $payment->paystack_access_code);

        return true;
    }

    /**
     * Called from JS once the Paystack popup reports success. Verifies the
     * transaction server-side (the authoritative check) before advancing.
     */
    public function verifyPayment(string $reference): void
    {
        $payment = app(PaystackPaymentService::class)->verify($reference);

        if (! $payment) {
            $this->addError('payment', 'Payment could not be confirmed. If you were charged, please contact support.');

            return;
        }

        StorefrontSession::clearCart();
        $this->dispatch('cart-updated');

        Flux::toast(heading: 'Payment confirmed', text: 'Order '.$payment->account_reference.' is being processed.', variant: 'success');

        $this->redirectRoute('account.orders.show', $payment->order_id, navigate: true);
    }
}
