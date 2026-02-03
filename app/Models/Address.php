<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'phone_number',
        'alternative_phone_number',
        'county_id',
        'area_id',
        'address',
        'additional_information',
        'shipping_zone_id',
        'is_default'
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    // ===============================================
    // RELATIONSHIPS
    // ===============================================
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function county(): BelongsTo
    {
        return $this->belongsTo(County::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function shippingZone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class);
    }

    public function selectedShippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class, 'selected_shipping_method_id');
    }

    public function selectedShippingRate(): BelongsTo
    {
        return $this->belongsTo(ShippingRate::class, 'selected_shipping_rate_id');
    }

    // ===============================================
    // ACCESSORS
    // ===============================================

    /**
     * Get the full name
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn() => "{$this->first_name} {$this->last_name}",
        );
    }

    /**
     * Get the full address string
     */
    protected function fullAddress(): Attribute
    {
        return Attribute::make(
            get: function () {
                $parts = [
                    $this->address,
                    $this->area?->name,
                    $this->county?->name,
                ];

                return implode(', ', array_filter($parts));
            }
        );
    }

    // ===============================================
    // HELPER METHODS
    // ===============================================

    /**
     * Check if shipping method is selected
     */
    public function hasSelectedShippingMethod(): bool
    {
        return !is_null($this->selected_shipping_method_id);
    }

    /**
     * Get available shipping methods for this address's zone
     */
    public function availableShippingMethods()
    {
        if (!$this->shippingZone) {
            return collect();
        }

        return $this->shippingZone->availableShippingMethods();
    }

    /**
     * Get shipping rate for a specific method and weight
     */
    public function getShippingRateForMethod($methodId, $weight)
    {
        if (!$this->shippingZone) {
            return null;
        }

        return $this->shippingZone->getRateForMethod($methodId, $weight);
    }
}
