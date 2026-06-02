<?php

namespace App\Models;

use App\Enums\CarrierRateType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'carrier_id', 'delivery_zone_id', 'shipping_method_id',
    'rate_type', 'base_rate_cents', 'free_over_cents',
    'eta_min_days', 'eta_max_days', 'eta_label',
    'is_active', 'sort_order',
])]
class CarrierRate extends Model
{
    protected function casts(): array
    {
        return [
            'rate_type' => CarrierRateType::class,
            'base_rate_cents' => 'integer',
            'free_over_cents' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * The fee in cents for a given cart subtotal.
     */
    public function calculateFee(int $subtotalCents): int
    {
        if ($this->rate_type === CarrierRateType::FREE) {
            return 0;
        }

        if ($this->free_over_cents !== null && $subtotalCents >= $this->free_over_cents) {
            return 0;
        }

        return $this->base_rate_cents;
    }

    // ==================================================
    // RELATIONSHIPS
    // ==================================================

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(ShippingCarrier::class, 'carrier_id');
    }

    public function deliveryZone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class);
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }
}
