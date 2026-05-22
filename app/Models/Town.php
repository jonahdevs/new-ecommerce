<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Town extends Model
{
    protected $fillable = [
        'name',
        'sub_county_id',
        'county_id',
        'shipping_zone_id',
        'shape_id',
        'lat_center',
        'lng_center',
    ];

    public function subCounty(): BelongsTo
    {
        return $this->belongsTo(SubCounty::class);
    }

    public function county(): BelongsTo
    {
        return $this->belongsTo(County::class);
    }

    /**
     * Zone override — NULL means inherit from sub-county then county.
     */
    public function shippingZone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class);
    }

    public function boundary(): HasOne
    {
        return $this->hasOne(TownBoundary::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function effectiveShippingZone(): ?ShippingZone
    {
        return $this->shippingZone
            ?? $this->subCounty?->shippingZone
            ?? $this->county?->shippingZone;
    }

    public function hasZoneOverride(): bool
    {
        return $this->shipping_zone_id !== null;
    }
}
