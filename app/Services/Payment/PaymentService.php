<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\Contracts\PaymentGateway;
use App\Services\Payment\Gateways\{CustomGateway, MpesaGateway, PesawiseGateway, StripeGateway};
use App\Services\Payment\ValueObjects\{PaymentResponse, PaymentStatus};
use App\Settings\PaymentSettings;

/**
 * PaymentService
 *
 * Single entry point for all payment operations.
 * Reads gateway_mode and active_aggregator from PaymentSettings and delegates
 * to the correct gateway. CheckoutService only ever calls this — never a
 * gateway directly.
 *
 * Gateway modes:
 *   - 'individual': Uses 'custom' gateway (M-Pesa/Card selection by customer)
 *   - 'aggregator': Uses the active_aggregator (pesapal or pesawise)
 *
 * Usage:
 *   $response = app(PaymentService::class)->initiate($order, $payment);
 *   $status   = app(PaymentService::class)->verify($reference);
 */
class PaymentService
{
    public function __construct(
        private readonly PaymentSettings $settings,
    ) {}

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

    /**
     * Resolve the active gateway based on settings.
     * 
     * @param string|null $name Override gateway name (for webhook handling)
     */
    public function gateway(?string $name = null): PaymentGateway
    {
        $active = $name ?? $this->activeGateway();

        return match ($active) {
            'pesawise' => app(PesawiseGateway::class),
            'pesapal' => app(PesawiseGateway::class), // TODO: Add PesapalGateway when implemented
            'mpesa' => app(MpesaGateway::class),
            'stripe' => app(StripeGateway::class),
            'custom' => app(CustomGateway::class),
            default => throw new \InvalidArgumentException("Unknown payment gateway: {$active}"),
        };
    }

    /**
     * Get the active gateway name based on gateway_mode setting.
     * 
     * - 'individual' mode: returns 'custom' (customer chooses M-Pesa/Card)
     * - 'aggregator' mode: returns the active_aggregator (pesapal/pesawise)
     */
    public function activeGateway(): string
    {
        if ($this->settings->gateway_mode === 'aggregator') {
            return $this->settings->active_aggregator;
        }

        // Individual mode uses custom gateway
        return 'custom';
    }

    /**
     * Check if using custom gateway (individual mode).
     */
    public function isCustom(): bool
    {
        return $this->activeGateway() === 'custom';
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
