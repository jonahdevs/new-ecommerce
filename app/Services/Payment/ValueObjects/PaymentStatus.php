<?php

namespace App\Services\Payment\ValueObjects;

/**
 * PaymentStatus
 *
 * Returned by gateway->verify().
 * Normalises each gateway's status into a standard shape.
 */
class PaymentStatus
{
    private function __construct(
        public readonly string  $status,       // pending | processing | paid | failed | cancelled | refunded
        public readonly bool    $isPaid,
        public readonly ?string $transactionId = null,
        public readonly ?string $gatewayStatus = null, // raw status from gateway
        public readonly ?array  $meta          = null,
    ) {}

    public static function pending(): self
    {
        return new self(status: 'pending', isPaid: false);
    }

    public static function processing(): self
    {
        return new self(status: 'processing', isPaid: false);
    }

    public static function paid(string $transactionId, ?string $gatewayStatus = null, ?array $meta = null): self
    {
        return new self(
            status: 'paid',
            isPaid: true,
            transactionId: $transactionId,
            gatewayStatus: $gatewayStatus,
            meta: $meta,
        );
    }

    public static function failed(?string $gatewayStatus = null): self
    {
        return new self(status: 'failed', isPaid: false, gatewayStatus: $gatewayStatus);
    }

    public static function cancelled(): self
    {
        return new self(status: 'cancelled', isPaid: false);
    }
}
