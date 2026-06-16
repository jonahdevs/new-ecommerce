<?php

namespace App\Models;

use Database\Factories\DeliveryZoneFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['name', 'county', 'is_active', 'sort_order', 'priority', 'polygon'])]
class DeliveryZone extends Model
{
    /** @use HasFactory<DeliveryZoneFactory> */
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'county', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('delivery_zone');
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'priority' => 'integer',
            'polygon' => 'array', // [{lat: float, lng: float}, …]
        ];
    }

    // ==================================================
    // RELATIONSHIPS
    // ==================================================

    public function promotions(): HasMany
    {
        return $this->hasMany(DeliveryPromotion::class, 'zone_id');
    }

    public function carrierZones(): HasMany
    {
        return $this->hasMany(CarrierZone::class);
    }

    public function carrierRates(): HasMany
    {
        return $this->hasMany(CarrierRate::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    // ==================================================
    // SCOPES
    // ==================================================

    /**
     * @param  Builder<DeliveryZone>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    // ==================================================
    // HELPERS
    // ==================================================

    /**
     * Whether the given coordinates fall inside this polygon zone.
     * Uses the ray-casting (even-odd) algorithm for point-in-polygon detection.
     * Works correctly for convex and concave polygons.
     *
     * @param  float  $latitude  The point's latitude
     * @param  float  $longitude  The point's longitude
     */
    public function containsPoint(float $latitude, float $longitude): bool
    {
        $polygon = $this->polygon;

        if (empty($polygon) || count($polygon) < 3) {
            return false;
        }

        $n = count($polygon);
        $inside = false;
        $j = $n - 1;

        for ($i = 0; $i < $n; $i++) {
            $xi = (float) $polygon[$i]['lng'];
            $yi = (float) $polygon[$i]['lat'];
            $xj = (float) $polygon[$j]['lng'];
            $yj = (float) $polygon[$j]['lat'];

            // Cast a ray eastward from the point and count intersections.
            if ((($yi > $latitude) !== ($yj > $latitude)) &&
                ($longitude < ($xj - $xi) * ($latitude - $yi) / ($yj - $yi) + $xi)) {
                $inside = ! $inside;
            }

            $j = $i;
        }

        return $inside;
    }
}
