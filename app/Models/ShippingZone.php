<?php

namespace App\Models;

use App\Enums\ShippingZoneStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingZone extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'status',
        'is_delivery_available'
    ];

    protected $casts = [
        'status' => ShippingZoneStatus::class,
    ];

    // ===============================================
    // RELATIONSHIPS
    // ===============================================

    public function counties(): HasMany
    {
        return $this->hasMany(County::class);
    }

    /**
     * Areas that have explicitly overridden to this zone.
     * Does not include areas inheriting via their county.
     */
    public function areas(): HasMany
    {
        return $this->hasMany(Area::class);
    }

    public function shippingRates(): HasMany
    {
        return $this->hasMany(ShippingRate::class);
    }

    public function freeShippingRules(): HasMany
    {
        return $this->hasMany(FreeShippingRule::class);
    }

    public function deliveryOrders(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class);
    }

    // ===============================================
    // Scope
    // ===============================================

    public function scopeActive($query)
    {
        return $query->where('status', ShippingZoneStatus::ACTIVE->value);
    }

    // ===============================================
    // HELPERS
    // ===============================================

    public function isActive(): bool
    {
        return $this->status === ShippingZoneStatus::ACTIVE;
    }
}
