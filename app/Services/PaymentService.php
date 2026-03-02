<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\Contracts\PaymentGateway;
use App\Services\Payment\Gateways\CustomGateway;
use App\Services\Payment\Gateways\MpesaGateway;
use App\Services\Payment\Gateways\PesawiseGateway;
use App\Services\Payment\Gateways\StripeGateway;
use App\Services\Payment\ValueObjects\PaymentResponse;
use App\Services\Payment\ValueObjects\PaymentStatus;
use App\Settings\PaymentSettings;
use Illuminate\Support\Facades\Log;

/**
 * PaymentService
 *
 * Single entry point for all payment operations.
 * Reads active_gateway from PaymentSettings and delegates to the
 * correct gateway. CheckoutService only ever calls this — never a
 * gateway directly.
 *
 * Usage:
 *   $response = app(PaymentService::class)->initiate($order, $payment);
 *   $status   = app(PaymentService::class)->verify($reference);
 */
class PaymentService
{
    public function __construct(
        private readonly PaymentSettings $settings,
    ) {
    }

    //  Main operations

    public function initiate(Order $order, Payment $payment): PaymentResponse
    {
        return $this->gateway()->initiate($order, $payment);
    }

    public function verify(string $reference): PaymentStatus
    {
        return $this->gateway()->verify($reference);
    }

    //  Gateway resolution

    public function gateway(?string $name = null): PaymentGateway
    {
        $active = $name ?? $this->settings->active_gateway;

        return match ($active) {
            'pesawise' => app(PesawiseGateway::class),
            'mpesa' => app(MpesaGateway::class),
            'stripe' => app(StripeGateway::class),
            'custom' => app(CustomGateway::class),
            default => throw new \InvalidArgumentException("Unknown payment gateway: {$active}"),
        };
    }

    public function activeGateway(): string
    {
        return $this->settings->active_gateway;
    }

    public function isCustom(): bool
    {
        return $this->settings->active_gateway === 'custom';
    }

    /**
     * Whether the active gateway requires the customer to choose
     * between M-Pesa and card before placing the order.
     */
    public function requiresPaymentMethodSelection(): bool
    {
        return $this->isCustom();
    }
}
