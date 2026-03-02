<?php

namespace App\Services\Payment\Contracts;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\ValueObjects\PaymentResponse;
use App\Services\Payment\ValueObjects\PaymentStatus;
use Illuminate\Http\Request;

interface PaymentGateway
{
    /**
     * Initiate a payment for an order.
     * Returns a PaymentResponse telling the UI what to do next.
     */
    public function initiate(Order $order, Payment $payment): PaymentResponse;

    /**
     * Verify the status of a payment by its gateway reference.
     * Used by webhook handlers and manual verification.
     */
    public function verify(string $reference): PaymentStatus;

    /**
     * Handle an incoming webhook from the gateway.
     * Responsible for updating Payment + Order status.
     */
    public function handleWebhook(Request $request): void;
}
