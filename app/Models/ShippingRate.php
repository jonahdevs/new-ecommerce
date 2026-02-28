<?php

namespace App\Models;

use App\Enums\ShippingRateStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingRate extends Model
{
    protected $fillable = [
        'shipping_zone_id',
        'shipping_method_id',
        'min_weight',
        'max_weight',
        'weight_label',
        'price',
        'estimated_days_min',
        'estimated_days_max',
        'status',
    ];

    protected $casts = [
        'min_weight' => 'decimal:2',
        'max_weight' => 'decimal:2',
        'price' => 'decimal:2',
        'estimated_days_min' => 'integer',
        'estimated_days_max' => 'integer',
        'status' => ShippingRateStatus::class,
    ];

    // ===============================================
    // RELATIONSHIPS
    // ===============================================

    public function shippingZone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class);
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function addons(): HasMany
    {
        return $this->hasMany(ShippingRateAddon::class);
    }

    public function deliveryOrders(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class);
    }

    // ===============================================
    // Scope
    // ===============================================
    #[Scope()]

    protected function active($query)
    {
        $query->where('status', ShippingRateStatus::ACTIVE->value);
    }

    #[Scope]
    protected function expired($query)
    {
        $query->where('status', ShippingRateStatus::EXPIRED->value);
    }

    // ===============================================
    // HELPERS
    // ===============================================

    /**
     * Check if a given weight falls within this rate's bracket.
     */
    public function coversWeight(float $weightKg): bool
    {
        if ($weightKg < $this->min_weight) {
            return false;
        }

        if ($this->max_weight === null) {
            return true; // XL tier — no upper limit
        }

        return $weightKg <= $this->max_weight;
    }

    /**
     * Active PUS addons for this rate.
     * Optionally scope to a specific station (or return all-station addons).
     */
    public function activeAddons(?int $stationId = null): HasMany
    {
        return $this->addons()
            ->where('status', \App\Enums\ShippingRateAddonStatus::ACTIVE->value)
            ->where(
                fn($q) => $q->whereNull('pickup_station_id')
                    ->orWhere('pickup_station_id', $stationId)
            );
    }

    public function isActive(): bool
    {
        return $this->status === ShippingRateStatus::ACTIVE;
    }
    public function isExpired(): bool
    {
        return $this->status === ShippingRateStatus::EXPIRED;
    }
}
