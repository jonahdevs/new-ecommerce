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
     *
     * Priority: town zone → sub-county zone → county zone. Each tier is read
     * independently — we do NOT walk town→subCounty parent relationships
     * because geoBoundaries ADM2/ADM3 centroid-based parenting can disagree
     * with live point-in-polygon. The town/sub_county/county_id fields were
     * each set by live resolvers; trust them at face value.
     */
    public function resolveShippingZone(): void
    {
        if ($this->town_id) {
            $townZoneId = Town::where('id', $this->town_id)->value('shipping_zone_id');

            if ($townZoneId) {
                $this->shipping_zone_id = $townZoneId;

                return;
            }
        }

        if ($this->sub_county_id) {
            $subCountyZoneId = SubCounty::where('id', $this->sub_county_id)->value('shipping_zone_id');

            if ($subCountyZoneId) {
                $this->shipping_zone_id = $subCountyZoneId;

                return;
            }
        }

        $this->shipping_zone_id = County::where('id', $this->county_id)->value('shipping_zone_id');
    }
}
