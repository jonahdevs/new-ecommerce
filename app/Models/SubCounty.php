<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SubCounty extends Model
{
    protected $fillable = [
        'name',
        'county_id',
        'shipping_zone_id',
        'shape_id',
        'lat_center',
        'lng_center',
    ];

    // ===============================================
    // RELATIONSHIPS
    // ===============================================

    public function county(): BelongsTo
    {
        return $this->belongsTo(County::class);
    }

    /**
     * The zone override for this sub-county.
     * NULL means inherit from the parent county.
     */
    public function shippingZone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class);
    }

    public function boundary(): HasOne
    {
        return $this->hasOne(SubCountyBoundary::class);
    }

    public function towns(): HasMany
    {
        return $this->hasMany(Town::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    // ===============================================
    // HELPER METHODS
    // ===============================================

    public function effectiveShippingZone(): ?ShippingZone
    {
        return $this->shippingZone ?? $this->county?->shippingZone;
    }

    public function hasZoneOverride(): bool
    {
        return $this->shipping_zone_id !== null;
    }
}
