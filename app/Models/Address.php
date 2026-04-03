<?php

namespace App\Models;

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
        'is_default',
        'latitude',
        'longitude',
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

    // ===============================================
    // HELPER METHODS
    // ===============================================

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getDisplayAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->area?->name,
            $this->county->name,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Resolve and store the shipping zone at save time.
     * Call this before saving when county_id or area_id changes.
     */
    public function resolveShippingZone(): void
    {
        $area = $this->area_id ? Area::with('county.shippingZone', 'shippingZone')->find($this->area_id) : null;

        $this->shipping_zone_id = $area
            ? ($area->shipping_zone_id ?? $area->county->shipping_zone_id)
            : County::find($this->county_id)?->shipping_zone_id;
    }
}
