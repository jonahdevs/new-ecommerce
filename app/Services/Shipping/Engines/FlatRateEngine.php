<?php

namespace App\Services\Shipping\Engines;

use App\Models\FreeShippingRule;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\Shipping\ShippingOption;
use Illuminate\Support\Collection;

/**
 * Flat Rate Engine
 *
 * Resolves pricing for Standard and Express delivery methods.
 * Logic:
 *   1. Find the active ShippingRate matching zone + method + weight bracket
 *   2. Check for any active FreeShippingRules that waive the cost
 *   3. Return a ShippingOption
 */
class FlatRateEngine
{
    /**
     * Calculate a shipping option for a given method, zone, and weight.
     *
     * Returns null if no matching rate bracket exists (e.g. method not
     * available in this zone, or no rate covers the given weight).
     */
    public function calculate(
        ShippingMethod $method,
        ShippingZone $zone,
        float $weightKg,
        float $orderAmount = 0,
    ): ?ShippingOption {

        // Find the rate bracket that covers this weight
        $rate = $this->resolveRate($method, $zone, $weightKg);

        if (!$rate) {
            return null;
        }

        // Check if a free shipping rule applies
        $isFree = $this->isFreeShipping($zone, $method, $orderAmount, $weightKg);

        $cost = $isFree ? 0.0 : (float) $rate->price;

        $breakdown = $this->buildBreakdown($zone, $rate, $weightKg, $cost, $isFree);

        return new ShippingOption(
            methodId: $method->id,
            methodName: $method->name,
            methodCode: $method->code,
            methodType: 'flat',
            cost: $cost,
            weightLabel: $rate->weight_label ?? $this->deriveLabel($rate),
            estimatedDaysMin: $rate->estimated_days_min ?? 1,
            estimatedDaysMax: $rate->estimated_days_max ?? 5,
            costBreakdown: $breakdown,
            shippingRateId: $rate->id,
            shippingZoneId: $zone->id,
        );
    }

    //  Private helpers

    private function resolveRate(ShippingMethod $method, ShippingZone $zone, float $weightKg): ?ShippingRate
    {
        return ShippingRate::where('shipping_method_id', $method->id)
            ->where('shipping_zone_id', $zone->id)
            ->where('status', 'active')
            ->where('min_weight', '<=', $weightKg)
            ->where(
                fn($q) =>
                $q->whereNull('max_weight')          // XL tier — no upper limit
                    ->orWhere('max_weight', '>=', $weightKg)
            )
            ->orderBy('min_weight')
            ->first();
    }

    private function isFreeShipping(
        ShippingZone $zone,
        ShippingMethod $method,
        float $orderAmount,
        float $weightKg,
    ): bool {
        return FreeShippingRule::applicable($zone->id, $method->id)
            ->get()
            ->contains(fn($rule) => $rule->qualifies($orderAmount, $weightKg));
    }

    private function buildBreakdown(
        ShippingZone $zone,
        ShippingRate $rate,
        float $weightKg,
        float $cost,
        bool $isFree,
    ): array {
        $breakdown = [
            'model' => 'flat',
            'weight_kg' => $weightKg,
            'weight_tier' => $rate->weight_label ?? $this->deriveLabel($rate),
            'zone' => $zone->name,
            'line_haul' => (float) $rate->price,
            'total' => $cost,
        ];

        if ($isFree) {
            $breakdown['free_shipping'] = true;
            $breakdown['discount'] = (float) $rate->price;
        }

        return $breakdown;
    }

    private function deriveLabel(ShippingRate $rate): string
    {
        $min = number_format($rate->min_weight, 0);
        $max = $rate->max_weight ? number_format($rate->max_weight, 0) : '+';

        return "{$min}–{$max} Kg";
    }
}
