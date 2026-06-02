<?php

namespace App\Services;

use App\Models\DeliveryZone;
use App\Models\ShippingCarrier;

/**
 * Immutable outcome of pricing a delivery for a given pin + method + cart subtotal.
 */
final readonly class DeliveryQuoteResult
{
    public function __construct(
        public bool $serviceable,
        public int $feeCents,
        public bool $isFree,
        public ?DeliveryZone $zone = null,
        public ?ShippingCarrier $carrier = null,
        public ?string $promotionName = null,
        public ?string $etaLabel = null,
    ) {}

    public static function unserviceable(): self
    {
        return new self(serviceable: false, feeCents: 0, isFree: false);
    }
}
