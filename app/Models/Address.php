<?php

namespace App\Models;

use App\Services\Shipping\ZonePolygonService;
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
     * Priority (most-specific wins):
     *   0. Custom polygon geometry    (admin-drawn — requires latitude/longitude)
     *   1. town.shipping_zone_id      (ADM3 ward override)
     *   2. sub_county.shipping_zone_id (ADM2 default)
     *   3. county.shipping_zone_id    (ADM1 fallback)
     */
    public function resolveShippingZone(): void
    {
        // 0. Custom polygon — most precise when the customer has pinned a location.
        if ($this->latitude !== null && $this->longitude !== null) {
            $polygonZone = app(ZonePolygonService::class)
                ->resolveByCoordinates((float) $this->latitude, (float) $this->longitude);

            if ($polygonZone) {
                $this->shipping_zone_id = $polygonZone->id;

                return;
            }
        }

        // 1. Town (ADM3) override.
        if ($this->town_id) {
            $townZoneId = Town::where('id', $this->town_id)->value('shipping_zone_id');

            if ($townZoneId) {
                $this->shipping_zone_id = $townZoneId;

                return;
            }
        }

        // 2. Sub-county (ADM2) override.
        if ($this->sub_county_id) {
            $subCountyZoneId = SubCounty::where('id', $this->sub_county_id)->value('shipping_zone_id');

            if ($subCountyZoneId) {
                $this->shipping_zone_id = $subCountyZoneId;

                return;
            }
        }

        // 3. County (ADM1) fallback.
        $this->shipping_zone_id = County::where('id', $this->county_id)->value('shipping_zone_id');
    }
}
