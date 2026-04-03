<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends Model
{
    protected $fillable = [
        'name',
        'county_id',
        'shipping_zone_id',
    ];

    // ===============================================
    // RELATIONSHIPS
    // ===============================================
    public function county(): BelongsTo
    {
        return $this->belongsTo(County::class);
    }

    /**
     * The zone override for this area specifically.
     * NULL means the area inherits from its county.
     */
    public function shippingZone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class);
    }

    public function pickupStations(): HasMany
    {
        return $this->hasMany(PickupStation::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    // ===============================================
    // HELPER METHODS
    // ===============================================

    /**
     * Returns the effective shipping zone — the area's own override if set,
     * otherwise falls back to the county's zone. Always eager-load
     * county.shippingZone when using this to avoid N+1 queries.
     */
    public function effectiveShippingZone(): ?ShippingZone
    {
        return $this->shippingZone ?? $this->county?->shippingZone;
    }

    public function hasZoneOverride(): bool
    {
        return $this->shipping_zone_id !== null;
    }
}
