<?php

namespace App\Services\Shipping;

use App\Models\County;
use App\Models\PickupStation;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use App\Models\SubCounty;
use App\Models\Town;
use App\Services\Shipping\Engines\FlatRateEngine;
use App\Services\Shipping\Engines\PusEngine;
use Illuminate\Support\Collection;

/**
 * ShippingCalculator
 *
 * Single entry point for resolving available shipping options at checkout.
 *
 * Usage:
 *
 *   $calculator = app(ShippingCalculator::class);
 *
 *   $options = $calculator->calculate(
 *       countyId:     $countyId,
 *       subCountyId:  $subCountyId,   // nullable — resolved from lat/lng pin
 *       townId:       $townId,        // nullable — most-specific override
 *       weightKg:     $weightKg,
 *       orderAmount:  $orderTotal,
 *   );
 *
 * When the customer selects PUS and picks a station:
 *
 *   $updated = $calculator->recalculateForStation(
 *       option:    $selectedOption,
 *       stationId: $stationId,
 *       weightKg:  $weightKg,
 *   );
 */
class ShippingCalculator
{
    public function __construct(
        private readonly FlatRateEngine $flatEngine,
        private readonly PusEngine $pusEngine,
        private readonly ZonePolygonService $polygonService,
    ) {}

    //  Main calculation

    /**
     * Resolve all available shipping options for a given location + cart.
     *
     * @param  int  $countyId  Required — coarse fallback
     * @param  int|null  $subCountyId  Optional — primary source of truth (resolved from lat/lng pin)
     * @param  int|null  $townId  Optional — ADM3 override (most-specific)
     * @param  float  $weightKg  Total cart weight in kilograms
     * @param  float  $orderAmount  Cart subtotal — used for free shipping rules
     * @param  float|null  $lat  Customer's pinned latitude — enables custom polygon resolution
     * @param  float|null  $lng  Customer's pinned longitude — enables custom polygon resolution
     * @return Collection<ShippingOption> Sorted by cost ascending
     */
    public function calculate(
        int $countyId,
        ?int $subCountyId = null,
        ?int $townId = null,
        float $weightKg = 0,
        float $orderAmount = 0,
        ?float $lat = null,
        ?float $lng = null,
    ): Collection {
        $zone = $this->resolveZone($countyId, $subCountyId, $townId, $lat, $lng);

        if (! $zone || ! $zone->is_delivery_available) {
            return collect();
        }

        $methods = ShippingMethod::where('status', 'active')
            ->whereIn('type', ['flat', 'pus'])
            ->orderBy('sort_order')
            ->get();

        $options = collect();

        foreach ($methods as $method) {
            $option = match ($method->type) {
                'flat' => $this->flatEngine->calculate(
                    method: $method,
                    zone: $zone,
                    weightKg: $weightKg,
                    orderAmount: $orderAmount,
                ),
                'pus' => $this->pusEngine->calculate(
                    method: $method,
                    zone: $zone,
                    weightKg: $weightKg,
                    countyId: $countyId,
                ),
                default => null,
            };

            if ($option) {
                $options->push($option);
            }
        }

        return $options->sortBy([
            fn ($a, $b) => $b->isFree() <=> $a->isFree(),
            fn ($a, $b) => $a->cost <=> $b->cost,
            fn ($a, $b) => $a->estimatedDaysMax <=> $b->estimatedDaysMax,
        ])->values();
    }

    /**
     * Recalculate a PUS option after the customer picks a specific station.
     */
    public function recalculateForStation(
        ShippingOption $option,
        int $stationId,
        float $weightKg,
    ): ShippingOption {
        if (! $option->isPus()) {
            return $option;
        }

        $method = ShippingMethod::find($option->methodId);
        $zone = ShippingZone::find($option->shippingZoneId);
        $station = PickupStation::find($stationId);

        if (! $method || ! $zone || ! $station) {
            return $option;
        }

        return $this->pusEngine->recalculateForStation(
            option: $option,
            method: $method,
            zone: $zone,
            weightKg: $weightKg,
            station: $station,
        );
    }

    //  Zone resolution

    /**
     * Resolve the effective shipping zone for a location.
     *
     * Priority (most-specific wins):
     *   0. Custom polygon geometry       (admin-drawn, highest precision — requires lat/lng)
     *   1. town.shipping_zone_id         (ADM3 ward override)
     *   2. sub_county.shipping_zone_id   (ADM2 default)
     *   3. county.shipping_zone_id       (ADM1 fallback)
     *
     * Each admin-boundary tier is read independently — we do NOT traverse
     * Town→SubCounty relationships because geoBoundaries ADM2/ADM3 centroids
     * can disagree with live point-in-polygon resolution.
     */
    public function resolveZone(
        int $countyId,
        ?int $subCountyId = null,
        ?int $townId = null,
        ?float $lat = null,
        ?float $lng = null,
    ): ?ShippingZone {
        // 0. Custom polygon — highest precision when the customer has pinned a location.
        if ($lat !== null && $lng !== null) {
            $polygonZone = $this->polygonService->resolveByCoordinates($lat, $lng);

            if ($polygonZone) {
                return $polygonZone;
            }
        }

        // 1. Town (ADM3) ward override.
        if ($townId) {
            $townZoneId = Town::where('id', $townId)->value('shipping_zone_id');

            if ($townZoneId) {
                return ShippingZone::find($townZoneId);
            }
        }

        // 2. Sub-county (ADM2) override.
        if ($subCountyId) {
            $subCountyZoneId = SubCounty::where('id', $subCountyId)->value('shipping_zone_id');

            if ($subCountyZoneId) {
                return ShippingZone::find($subCountyZoneId);
            }
        }

        // 3. County (ADM1) fallback.
        return County::with('shippingZone')->find($countyId)?->shippingZone;
    }

    //  Convenience helpers

    public function isDeliverable(
        int $countyId,
        ?int $subCountyId = null,
        ?int $townId = null,
        ?float $lat = null,
        ?float $lng = null,
    ): bool {
        $zone = $this->resolveZone($countyId, $subCountyId, $townId, $lat, $lng);

        return $zone !== null && $zone->is_delivery_available;
    }

    public function getZoneName(
        int $countyId,
        ?int $subCountyId = null,
        ?int $townId = null,
        ?float $lat = null,
        ?float $lng = null,
    ): ?string {
        return $this->resolveZone($countyId, $subCountyId, $townId, $lat, $lng)?->name;
    }

    public function cheapestOption(
        int $countyId,
        ?int $subCountyId = null,
        ?int $townId = null,
        float $weightKg = 0,
        float $orderAmount = 0,
        ?float $lat = null,
        ?float $lng = null,
    ): ?ShippingOption {
        return $this->calculate($countyId, $subCountyId, $townId, $weightKg, $orderAmount, $lat, $lng)->first();
    }
}
