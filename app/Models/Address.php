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
        'sub_county_id',
        'town_id',
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

    public function subCounty(): BelongsTo
    {
        return $this->belongsTo(SubCounty::class);
    }

    public function town(): BelongsTo
    {
        return $this->belongsTo(Town::class);
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
            $this->subCounty?->name,
            $this->county->name,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Resolve and store the shipping zone at save time.
     * Priority: sub-county zone override → county zone.
     * Call this before saving when county_id or sub_county_id changes.
     */
    /**
     * Resolve and store the shipping zone at save time.
     * Priority: town zone → sub-county zone → county zone.
     */
    public function resolveShippingZone(): void
    {
        if ($this->town_id) {
            $town = Town::with('shippingZone', 'subCounty.shippingZone', 'county.shippingZone')->find($this->town_id);
            $this->shipping_zone_id = $town->shipping_zone_id
                ?? $town->subCounty?->shipping_zone_id
                ?? $town->county?->shipping_zone_id;

            return;
        }

        if ($this->sub_county_id) {
            $subCounty = SubCounty::with('shippingZone', 'county.shippingZone')->find($this->sub_county_id);
            $this->shipping_zone_id = $subCounty->shipping_zone_id ?? $subCounty->county->shipping_zone_id;

            return;
        }

        $this->shipping_zone_id = County::find($this->county_id)?->shipping_zone_id;
    }
}
