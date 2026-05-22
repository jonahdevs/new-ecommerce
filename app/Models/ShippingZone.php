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
        'is_delivery_available',
    ];

    protected function casts(): array
    {
        return [
            'status' => ShippingZoneStatus::class,
            'is_delivery_available' => 'boolean',
        ];
    }

    // ===============================================
    // RELATIONSHIPS
    // ===============================================

    public function counties(): HasMany
    {
        return $this->hasMany(County::class);
    }

    /**
     * Sub-counties that have explicitly overridden to this zone.
     */
    public function subCounties(): HasMany
    {
        return $this->hasMany(SubCounty::class);
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
