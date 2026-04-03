<?php

namespace App\Models;

use App\Enums\AddonType;
use App\Enums\ShippingRateAddonStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingRateAddon extends Model
{
    protected $fillable = [
        'shipping_rate_id',
        'addon_type',
        'label',
        'addon_amount',
        'pickup_station_id',
        'status',
    ];

    protected $casts = [
        'addon_amount' => 'decimal:2',
        'addon_type' => AddonType::class,
        'status' => ShippingRateAddonStatus::class,
    ];

    // ===============================================
    // RELATIONSHIP
    // ===============================================

    public function shippingRate(): BelongsTo
    {
        return $this->belongsTo(ShippingRate::class);
    }

    public function pickupStation(): BelongsTo
    {
        return $this->belongsTo(PickupStation::class);
    }

    // ===============================================
    // Scope
    // ===============================================
    #[Scope()]
    protected function scopeActive($query)
    {
        $query->where('status', ShippingRateAddonStatus::ACTIVE->value);
    }

    #[Scope]
    protected function pus($query)
    {
        return $query->where('addon_type', AddonType::Pus->value);
    }

    // ===============================================
    // HELPERS
    // ===============================================

    public function isActive(): bool
    {
        return $this->status === ShippingRateAddonStatus::ACTIVE;
    }

    /**
     * Whether this addon applies globally to all stations or
     * is scoped to a specific one.
     */
    public function isGlobal(): bool
    {
        return $this->pickup_station_id === null;
    }

    public function appliesToStation(int $stationId): bool
    {
        return $this->isGlobal() || $this->pickup_station_id === $stationId;
    }
}
