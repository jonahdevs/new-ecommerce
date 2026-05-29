<?php

namespace App\Models;

use Database\Factories\DeliveryZoneFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryZone extends Model
{
    /** @use HasFactory<DeliveryZoneFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'county',
        'is_active',
        'sort_order',
        'priority',
        'center_lat',
        'center_lng',
        'radius_meters',
        'base_fee_cents',
        'free_over_cents',
        'eta_label',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'priority' => 'integer',
            'center_lat' => 'float',
            'center_lng' => 'float',
            'radius_meters' => 'integer',
            'base_fee_cents' => 'integer',
            'free_over_cents' => 'integer',
        ];
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(DeliveryPromotion::class, 'zone_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    /**
     * @param  Builder<DeliveryZone>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Whether the given coordinates fall inside this circular zone, using the
     * haversine great-circle distance against the zone radius.
     */
    public function containsPoint(float $latitude, float $longitude): bool
    {
        return $this->distanceMetersTo($latitude, $longitude) <= $this->radius_meters;
    }

    public function distanceMetersTo(float $latitude, float $longitude): float
    {
        $earthRadius = 6_371_000; // metres

        $latFrom = deg2rad($this->center_lat);
        $latTo = deg2rad($latitude);
        $latDelta = deg2rad($latitude - $this->center_lat);
        $lngDelta = deg2rad($longitude - $this->center_lng);

        $a = sin($latDelta / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($lngDelta / 2) ** 2;

        return $earthRadius * 2 * asin(min(1.0, sqrt($a)));
    }
}
