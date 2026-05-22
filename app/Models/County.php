<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class County extends Model
{
    protected $fillable = [
        'name',
        'code',
        'shipping_zone_id',
    ];

    // ===============================================
    // RELATIONSHIPS
    // ===============================================

    public function shippingZone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class);
    }

    public function subCounties(): HasMany
    {
        return $this->hasMany(SubCounty::class);
    }

    public function towns(): HasMany
    {
        return $this->hasMany(Town::class);
    }

    public function pickupStations(): HasMany
    {
        return $this->hasMany(PickupStation::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function boundary(): HasOne
    {
        return $this->hasOne(CountyBoundary::class);
    }
}
