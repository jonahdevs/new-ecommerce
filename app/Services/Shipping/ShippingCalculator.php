<?php

namespace App\Services\Shipping;

use App\Models\Area;
use App\Models\County;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
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
 *       countyId:    $countyId,
 *       areaId:      $areaId,      // nullable
 *       weightKg:    $weightKg,
 *       orderAmount: $orderTotal,  // for free shipping rules
 *   );
 *
 *   // Returns Collection<ShippingOption> — one per available method.
 *   // Empty collection = no shipping available to this location.
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
     * @param  int        $countyId     Required — determines the shipping zone
     * @param  int|null   $areaId       Optional — may override the zone
     * @param  float      $weightKg     Total cart weight in kilograms
     * @param  float      $orderAmount  Cart subtotal — used for free shipping rules
     *
     * @return Collection<ShippingOption>  Sorted by cost ascending
     */
    public function calculate(
        int $countyId,
        ?int $areaId = null,
        float $weightKg = 0,
        float $orderAmount = 0,
    ): Collection {

        // 1. Resolve the shipping zone for this location
        $zone = $this->resolveZone($countyId, $areaId);

        if (!$zone) {
            return collect();
        }

        // 2. Get all active non-distance methods
        $methods = ShippingMethod::where('status', 'active')
            ->whereIn('type', ['flat', 'pus'])
            ->orderBy('sort_order')
            ->get();

        // 3. Run each method through the appropriate engine
        $options = collect();

        foreach ($methods as $method) {
            if (!$zone->is_delivery_available && $method->type === 'flat') {
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

        // Inject virtual quote option for zones where delivery is not available
        if (!$zone->is_delivery_available) {
            $options->push($this->buildQuoteOption($zone));
        }

        // 4. Sort: free first, then by cost, then by speed
        return $options->sortBy([
            fn($a, $b) => $a->isFree() <=> $b->isFree() ? -1 : 0,
            fn($a, $b) => $a->cost <=> $b->cost,
            fn($a, $b) => $a->estimatedDaysMax <=> $b->estimatedDaysMax,
        ])->values();
    }

    /**
     * Recalculate a PUS option after the customer picks a specific station.
     * Call this whenever the station selector changes.
     *
     * @return ShippingOption  Updated option with station-specific surcharge
     */
    public function recalculateForStation(
        ShippingOption $option,
        int $stationId,
        float $weightKg,
    ): ShippingOption {

        if (!$option->isPus()) {
            return $option; // Only PUS options have station-specific pricing
        }

        $method = ShippingMethod::find($option->methodId);
        $zone = ShippingZone::find($option->shippingZoneId);
        $station = \App\Models\PickupStation::find($stationId);

        if (!$method || !$zone || !$station) {
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
     * Resolve the effective shipping zone for a county + optional area.
     *
     * Priority:
     *   1. Area zone override (if area has one explicitly set)
     *   2. County's zone (the default)
     *
     * Returns null only if the county doesn't exist or has no zone assigned.
     */
    public function resolveZone(int $countyId, ?int $areaId = null): ?ShippingZone
    {
        // Check area override first
        if ($areaId) {
            $area = Area::with(['shippingZone', 'county.shippingZone'])
                ->find($areaId);

            if ($area) {
                // Area has its own zone override
                if ($area->shipping_zone_id && $area->shippingZone) {
                    return $area->shippingZone;
                }

                // Fall through to county zone
                return $area->county?->shippingZone;
            }
        }

        // Default to county zone
        $county = County::with('shippingZone')->find($countyId);

        return $county?->shippingZone;
    }

    //  Convenience helpers ─

    /**
     * Check whether any shipping is available to a location at all.
     * Useful for showing "delivery not available" before the full calc runs.
     */
    public function isDeliverable(int $countyId, ?int $areaId = null): bool
    {
        return $this->resolveZone($countyId, $areaId) !== null;
    }

    /**
     * Get just the zone name for a location — useful for displaying
     * "Ships via Nairobi rates" on the product page before checkout.
     */
    public function getZoneName(int $countyId, ?int $areaId = null): ?string
    {
        return $this->resolveZone($countyId, $areaId)?->name;
    }

    /**
     * Find the cheapest available option — useful for "from KES X" labels.
     */
    public function cheapestOption(
        int $countyId,
        ?int $areaId = null,
        float $weightKg = 0,
        float $orderAmount = 0,
    ): ?ShippingOption {
        return $this->calculate($countyId, $areaId, $weightKg, $orderAmount)->first();
    }

    private function buildQuoteOption(ShippingZone $zone): ShippingOption
    {
        return new ShippingOption(
            methodId: 0,
            methodName: 'Request a Delivery Quote',
            methodCode: 'quote',
            methodType: 'quote',
            cost: 0.0,
            weightLabel: '',
            estimatedDaysMin: 0,
            estimatedDaysMax: 0,
            costBreakdown: [
                'model' => 'quote',
                'zone'  => $zone->name,
                'note'  => 'Delivery cost to be confirmed by our team',
            ],
            shippingZoneId: $zone->id,
            isVirtualQuote: true,
        );
    }
}
