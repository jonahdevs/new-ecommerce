<?php

namespace App\Services\Shipping\Engines;

use App\Models\PickupStation;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\ShippingRateAddon;
use App\Models\ShippingZone;
use App\Services\Shipping\ShippingOption;
use Illuminate\Support\Collection;

/**
 * PUS (Pickup Station) Engine
 *
 * Resolves pricing for the Pickup Station method.
 * Formula:
 *   Total = line_haul (ShippingRate.price) + surcharge (ShippingRateAddon.addon_amount)
 *
 * Also fetches the list of available pickup stations so the
 * customer can choose which one to collect from.
 *
 * Returns null if:
 *   - No PUS rates exist for this zone
 *   - No active pickup stations are available
 */
class PusEngine
{
    /**
     * Calculate PUS shipping option for a given zone and weight.
     *
     * @param  int|null  $countyId  Used to surface nearby stations first
     */
    public function calculate(
        ShippingMethod $method,
        ShippingZone $zone,
        float $weightKg,
        ?int $countyId = null,
    ): ?ShippingOption {

        // 1. Resolve the flat line-haul rate for this weight + zone
        $rate = $this->resolveRate($method, $zone, $weightKg);

        if (! $rate) {
            return null;
        }

        // 2. Find active pickup stations
        $stations = $this->resolveStations($countyId);

        if ($stations->isEmpty()) {
            return null; // No stations available — don't offer PUS at checkout
        }

        // 3. Get the PUS surcharge for this rate
        // We use the first available station to check for station-specific addons.
        // If the customer later picks a specific station, the surcharge is
        // recalculated in recalculateForStation() below.
        $firstStation = $stations->first();
        $surcharge = $this->resolveSurcharge($rate, $firstStation->id);

        $lineHaul = (float) $rate->price;
        $total = $lineHaul + $surcharge;

        $breakdown = $this->buildBreakdown($zone, $rate, $firstStation, $weightKg, $lineHaul, $surcharge);

        return new ShippingOption(
            methodId: $method->id,
            methodName: $method->name,
            methodCode: $method->code,
            methodType: 'pus',
            cost: $total,
            weightLabel: $rate->weight_label ?? $this->deriveLabel($rate),
            estimatedDaysMin: ($rate->estimated_days_min ?? 1),
            estimatedDaysMax: ($rate->estimated_days_max ?? 3),
            costBreakdown: $breakdown,
            shippingRateId: $rate->id,
            shippingZoneId: $zone->id,
            pickupStations: $stations,
        );
    }

    /**
     * Recalculate the PUS cost for a specific chosen station.
     * Called when the customer selects a station from the dropdown.
     * Returns an updated ShippingOption with the station-specific surcharge.
     */
    public function recalculateForStation(
        ShippingOption $option,
        ShippingMethod $method,
        ShippingZone $zone,
        float $weightKg,
        PickupStation $station,
    ): ShippingOption {

        $rate = ShippingRate::find($option->shippingRateId);

        if (! $rate) {
            return $option;
        }

        $surcharge = $this->resolveSurcharge($rate, $station->id);
        $lineHaul = (float) $rate->price;
        $total = $lineHaul + $surcharge;

        $breakdown = $this->buildBreakdown($zone, $rate, $station, $weightKg, $lineHaul, $surcharge);

        return new ShippingOption(
            methodId: $option->methodId,
            methodName: $option->methodName,
            methodCode: $option->methodCode,
            methodType: 'pus',
            cost: $total,
            weightLabel: $option->weightLabel,
            estimatedDaysMin: $option->estimatedDaysMin,
            estimatedDaysMax: $option->estimatedDaysMax,
            costBreakdown: $breakdown,
            shippingRateId: $option->shippingRateId,
            shippingZoneId: $option->shippingZoneId,
            pickupStations: $option->pickupStations,
        );
    }

    //  Private helpers

    private function resolveRate(ShippingMethod $method, ShippingZone $zone, float $weightKg): ?ShippingRate
    {
        // For PUS, always resolve the rate from the primary station's zone
        // The customer's zone determines flat/doorstep pricing, but PUS is a line-haul model - the rate is fixed regardless of where the customer lives.

        $pusZone = $this->resolvePusZone($zone);

        return ShippingRate::where('shipping_method_id', $method->id)
            ->where('shipping_zone_id', $pusZone->id)
            ->where('status', 'active')
            ->where('min_weight', '<=', $weightKg)
            ->where(
                fn ($q) => $q->whereNull('max_weight')
                    ->orWhere('max_weight', '>=', $weightKg)
            )
            ->orderBy('min_weight')
            ->first();
    }

    private function resolvePusZone(ShippingZone $customerZone): ShippingZone
    {
        // PUS pricing is anchored at the primary station, not the customer.
        // Resolve the station's zone via sub-county → county (same precedence
        // as an address) so sub-county zone takes precedence over county zone.
        $primaryStation = PickupStation::where('is_primary', true)
            ->where('status', 'active')
            ->with(['subCounty.shippingZone', 'county.shippingZone'])
            ->first();

        if (! $primaryStation) {
            return $customerZone;
        }

        return $primaryStation->subCounty?->shippingZone
            ?? $primaryStation->county?->shippingZone
            ?? $customerZone;
    }

    private function resolveStations(?int $countyId): Collection
    {
        return PickupStation::with(['county', 'subCounty'])
            ->where('status', 'active')
            ->where(function ($query) use ($countyId) {
                $query->where('is_primary', true);

                if ($countyId) {
                    $query->orWhere('county_id', $countyId);
                }
            })
            ->orderByRaw(
                $countyId
                ? 'CASE WHEN county_id = ? THEN 0 ELSE 1 END, name ASC'
                : 'name ASC',
                $countyId ? [$countyId] : []
            )
            ->get();
    }

    /**
     * Get the total surcharge for a specific rate + station combination.
     * Global addons (pickup_station_id = null) always apply.
     * Station-specific addons stack on top.
     */
    private function resolveSurcharge(ShippingRate $rate, int $stationId): float
    {
        return (float) ShippingRateAddon::where('shipping_rate_id', $rate->id)
            ->where('status', 'active')
            ->where('addon_type', 'pus')
            ->where(
                fn ($q) => $q->whereNull('pickup_station_id')
                    ->orWhere('pickup_station_id', $stationId)
            )
            ->sum('addon_amount');
    }

    private function buildBreakdown(
        ShippingZone $zone,
        ShippingRate $rate,
        PickupStation $station,
        float $weightKg,
        float $lineHaul,
        float $surcharge,
    ): array {
        return [
            'model' => 'pus',
            'weight_kg' => $weightKg,
            'weight_tier' => $rate->weight_label ?? $this->deriveLabel($rate),
            'zone' => $zone->name,
            'line_haul' => $lineHaul,
            'pus_surcharge' => $surcharge,
            'station' => $station->name,
            'total' => $lineHaul + $surcharge,
        ];
    }

    private function deriveLabel(ShippingRate $rate): string
    {
        $min = number_format($rate->min_weight, 0);
        $max = $rate->max_weight ? number_format($rate->max_weight, 0) : '+';

        return "{$min}–{$max} Kg";
    }
}
