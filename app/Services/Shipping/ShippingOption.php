<?php

namespace App\Services\Shipping;

use App\Models\PickupStation;
use Illuminate\Support\Collection;

/**
 * Immutable value object representing a single available shipping option.
 *
 * Returned by the calculator and rendered as a selectable card at checkout.
 * When the customer confirms, cost_breakdown is stored on the DeliveryOrder.
 */
class ShippingOption
{
    public function __construct(
        public readonly int $methodId,
        public readonly string $methodName,
        public readonly string $methodCode,
        public readonly string $methodType,       // flat | pus
        public readonly float $cost,
        public readonly string $weightLabel,
        public readonly int $estimatedDaysMin,
        public readonly int $estimatedDaysMax,
        public readonly array $costBreakdown,    // stored on DeliveryOrder
        public readonly ?int $shippingRateId = null,
        public readonly ?int $shippingZoneId = null,
        public readonly ?Collection $pickupStations = null, // PUS only
        public readonly bool $isVirtualQuote = false,
    ) {}

    //  Display helpers

    /**
     * Human-readable delivery window.
     * e.g. "1–2 days" or "Same day (8 hrs)"
     */
    public function deliveryWindow(): string
    {
        if ($this->estimatedDaysMin === $this->estimatedDaysMax) {
            return "{$this->estimatedDaysMin} day" . ($this->estimatedDaysMin > 1 ? 's' : '');
        }

        return "{$this->estimatedDaysMin}–{$this->estimatedDaysMax} days";
    }

    /**
     * Formatted cost string.
     */
    public function formattedCost(): string
    {
        if ($this->isQuoteRequest()) {
            return 'TBD'; // not free — to be determined
        }

        return $this->cost === 0.0
            ? 'Free'
            : 'KES ' . number_format($this->cost, 0);
    }

    public function isFree(): bool
    {
        if ($this->isQuoteRequest()) {
            return false; // quote is never free
        }

        return $this->cost === 0.0;
    }

    public function isPus(): bool
    {
        return $this->methodType === 'pus';
    }

    public function isQuoteRequest(): bool
    {
        return $this->methodType === 'quote';
    }

    /**
     * Serialise for Livewire wire:model or session storage.
     */
    public function toArray(): array
    {
        return [
            'method_id' => $this->methodId,
            'method_name' => $this->methodName,
            'method_code' => $this->methodCode,
            'method_type' => $this->methodType,
            'cost' => $this->cost,
            'weight_label' => $this->weightLabel,
            'estimated_days_min' => $this->estimatedDaysMin,
            'estimated_days_max' => $this->estimatedDaysMax,
            'cost_breakdown' => $this->costBreakdown,
            'shipping_rate_id' => $this->shippingRateId,
            'shipping_zone_id' => $this->shippingZoneId,
        ];
    }
}
