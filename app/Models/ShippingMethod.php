<?php

namespace App\Models;

use App\Enums\ShippingMethodStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingMethod extends Model
{
    protected $fillable = [
        'logistics_provider_id',
        'name',
        'code',
        'type',
        'supports_returns',
        'delivery_time_unit',
        'description',
        'icon',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'supports_returns' => 'boolean',
        'status' => ShippingMethodStatus::class,
    ];

    // ===============================================
    // RELATIONSHIPS
    // ===============================================

    public function logisticsProvider(): BelongsTo
    {
        return $this->belongsTo(LogisticsProvider::class);
    }

    public function shippingRates(): HasMany
    {
        return $this->hasMany(ShippingRate::class);
    }

    public function vehicleRates(): HasMany
    {
        return $this->hasMany(VehicleRate::class);
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
    #[Scope()]

    protected function active($query)
    {
        $query->where('status', ShippingMethodStatus::ACTIVE->value);
    }

    #[Scope]
    protected function flat($query)
    {
        $query->where('type', 'flat');
    }

    #[Scope]
    protected function distance($query)
    {
        return $query->where('type', 'distance');
    }

    protected function pus($query)
    {
        return $query->where('type', 'pus');
    }

    // ===============================================
    // HELPERS
    // ===============================================

    public function isFlat(): bool
    {
        return $this->type === 'flat';
    }
    public function isDistance(): bool
    {
        return $this->type === 'distance';
    }
    public function isPus(): bool
    {
        return $this->type === 'pus';
    }
    public function isActive(): bool
    {
        return $this->status === ShippingMethodStatus::ACTIVE;
    }

    /**
     * Active flat rates sorted by min_weight for this method.
     * Useful for the checkout rate-lookup service.
     */
    public function activeRates(): HasMany
    {
        return $this->shippingRates()
            ->where('status', \App\Enums\ShippingRateStatus::ACTIVE->value)
            ->orderBy('min_weight');
    }

    /**
     * Active vehicle rates sorted by vehicle type.
     */
    public function activeVehicleRates(): HasMany
    {
        return $this->vehicleRates()
            ->where('status', \App\Enums\VehicleRateStatus::ACTIVE->value);
    }
}
