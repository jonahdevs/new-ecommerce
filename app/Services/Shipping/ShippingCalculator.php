<?php

namespace App\Services\Shipping;

use App\Models\County;
use App\Models\PickupStation;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use App\Models\SubCounty;
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
    ) {}

    //  Main calculation

    /**
     * Resolve all available shipping options for a given location + cart.
     *
     * @param  int  $countyId  Required — top-level geographic fallback
     * @param  int|null  $subCountyId  Optional — resolved from lat/lng pin, may override zone
     * @param  float  $weightKg  Total cart weight in kilograms
     * @param  float  $orderAmount  Cart subtotal — used for free shipping rules
     * @return Collection<ShippingOption> Sorted by cost ascending
     */
    public function calculate(
        int $countyId,
        ?int $subCountyId = null,
        float $weightKg = 0,
        float $orderAmount = 0,
    ): Collection {
        $zone = $this->resolveZone($countyId, $subCountyId);

        if (! $zone) {
            return collect();
        }

        $methods = ShippingMethod::where('status', 'active')
            ->whereIn('type', ['flat', 'pus'])
            ->orderBy('sort_order')
            ->get();

        $options = collect();

        foreach ($methods as $method) {
            if (! $zone->is_delivery_available && $method->type === 'flat') {
                continue;
            }

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
     * Priority:
     *   1. Sub-county zone override (if sub-county has one explicitly set)
     *   2. County's zone (the default)
     *
     * Returns null only if the county doesn't exist or has no zone assigned.
     */
    public function resolveZone(int $countyId, ?int $subCountyId = null): ?ShippingZone
    {
        if ($subCountyId) {
            $subCounty = SubCounty::with(['shippingZone', 'county.shippingZone'])->find($subCountyId);

            if ($subCounty) {
                return $subCounty->shippingZone ?? $subCounty->county?->shippingZone;
            }
        }

        return County::with('shippingZone')->find($countyId)?->shippingZone;
    }

    //  Convenience helpers

    public function isDeliverable(int $countyId, ?int $subCountyId = null): bool
    {
        return $this->resolveZone($countyId, $subCountyId) !== null;
    }

    public function getZoneName(int $countyId, ?int $subCountyId = null): ?string
    {
        return $this->resolveZone($countyId, $subCountyId)?->name;
    }

    public function cheapestOption(
        int $countyId,
        ?int $subCountyId = null,
        float $weightKg = 0,
        float $orderAmount = 0,
    ): ?ShippingOption {
        return $this->calculate($countyId, $subCountyId, $weightKg, $orderAmount)->first();
    }
}
