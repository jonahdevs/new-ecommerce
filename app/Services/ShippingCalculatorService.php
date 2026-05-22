<?php

namespace App\Services;

use App\Enums\ShippingMethodStatus;
use App\Enums\ShippingRateStatus;
use App\Models\Cart;
use App\Models\County;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\SubCounty;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Class ShippingCalculatorService.
 */
class ShippingCalculatorService
{
    /**
     * Calculate shipping cost for a cart
     * Uses user's default address and preferred shipping method
     *
     * @return float
     *
     * @throws \Exception
     */
    public function calculate(Cart $cart)
    {
        // 1. Calculate total weight
        $totalWeight = $this->calculateTotalWeight($cart);
        Log::info('Total weight calculated', [
            'total_weight' => $totalWeight,
        ]);

        // Apply minimum weight if needed
        $minWeight = config('shipping.min_order_weight_kg', 0.1);
        $totalWeight = max($totalWeight, $minWeight);

        // 2. Get shipping zone from user's default address
        $shippingZoneId = $this->getShippingZoneFromUser($cart->user);

        // 3. Determine which shipping method to use
        $shippingMethodId = $this->getPreferredShippingMethodId($cart->user);

        Log::info("Calculating shipping: Zone ID $shippingZoneId, Method ID $shippingMethodId, Total Weight $totalWeight kg");

        // 4. Get the specific rate
        $rate = $this->getShippingRate($shippingZoneId, $shippingMethodId, $totalWeight);

        return (float) $rate;
    }

    /**
     * Get Shipping zone from user's default address
     *
     * @param  User|null  $user
     *
     * @throws \Exception
     */
    protected function getShippingZoneFromUser($user): int
    {
        if (! $user) {
            throw new \Exception('User must be authenticated to calculate shipping.');
        }

        // Try checkout address first
        $addressId = session('checkout_address_id');

        if ($addressId) {
            $address = $user->addresses()->find($addressId);
            if ($address?->shipping_zone_id) {
                return $address->shipping_zone_id;
            }
        }

        // Fall back to default address
        $defaultAddress = $user->defaultAddress;
        if ($defaultAddress?->shipping_zone_id) {
            return $defaultAddress->shipping_zone_id;
        }

        // No zone could be resolved from the address — throw so the caller
        // can prompt the user to select an address with a valid shipping zone.
        throw new \Exception('No shipping zone could be resolved for your address. Please update your delivery address.');
    }

    /**
     * Get user's preferred shipping method ID or fallback to standard
     *
     * @param  User|null  $user
     * @return int
     */
    protected function getPreferredShippingMethodId($user)
    {
        // Try to get user's preferred method
        if ($user && $user->preferredShippingMethod) {
            return $user->preferredShippingMethod->id;
        }

        // Fallback to standard method
        $standardMethod = ShippingMethod::where('code', config('shipping.default_method_code', 'standard'))
            ->where('status', ShippingMethodStatus::ACTIVE)
            ->first();

        if (! $standardMethod) {
            // throw new \Exception('Default shipping method not found.');
        }

        return $standardMethod->id;
    }

    /**
     * Get shipping rate for specific zone, method, and weight
     */
    protected function getShippingRate(int $shippingZoneId, int $shippingMethodId, float $totalWeightKg): float
    {
        // Try to find exact match within weight range
        $rate = ShippingRate::query()
            ->where('shipping_zone_id', $shippingZoneId)
            ->where('shipping_method_id', $shippingMethodId)
            ->where('status', ShippingRateStatus::ACTIVE)
            ->where('min_weight', '<=', $totalWeightKg)
            ->where('max_weight', '>=', $totalWeightKg)
            ->first();

        if ($rate) {
            return (float) $rate->price;
        }

        // No exact match found - check if weight is below or above all brackets
        $allRates = ShippingRate::query()
            ->where('shipping_zone_id', $shippingZoneId)
            ->where('shipping_method_id', $shippingMethodId)
            ->where('status', ShippingRateStatus::ACTIVE)
            ->orderBy('max_weight', 'desc')
            ->get();

        if ($allRates->isEmpty()) {
            return 0; // No rates defined
        }

        $lowestMinWeight = $allRates->min('min_weight');
        $highestMaxWeight = $allRates->max('max_weight');

        // Weight is less than minimum (e.g., 0.5kg when minimum is 5kg)
        if ($totalWeightKg < $lowestMinWeight) {
            return (float) $allRates->min('price'); // Return cheapest
        }

        // Weight exceeds all brackets (e.g., 70kg when max is 50kg)
        if ($totalWeightKg > $highestMaxWeight) {
            // Return the rate of the highest weight bracket (20-50kg in your case)
            return (float) $allRates->first()->price; // Already ordered by max_weight desc
        }

        return 0; // Fallback
    }

    protected function calculateTotalWeight(Cart $cart): float
    {
        $totalWeightKg = $cart->items->reduce(function ($carry, $item) {
            $product = $item->product;

            if (! $product) {
                return $carry;
            }

            $weightKg = 0;

            if ($item->variant_id && $item->variant) {
                $weightKg = $item->variant->weight ?? $product->weight ?? 0;
            } else {
                $weightKg = $product->weight ?? 0;
            }

            return $carry + ($weightKg * $item->quantity);
        }, 0.0);

        return round($totalWeightKg, 2);
    }

    /**
     * Calculate estimated shipping cost for a single product
     *
     * @return float
     */
    public function calculateForProduct(Product $product, int $quantity = 1, ?User $user = null, ?int $countyId = null, ?int $areaId = null, ?int $variantId = null)
    {
        // Resolve weight - prefer variant weight if provided
        $weight = $product->weight ?? 0;

        if ($variantId) {
            $variant = $product->variants->find($variantId)
                ?? $product->variants()->find($variantId);
            $weight = $variant?->weight ?? $weight;
        }

        $totalWeight = max($weight * $quantity, config('shipping.min_order_weight_kg', 0.1));

        // Get Shipping zone
        $shippingZoneId = $this->resolveShippingZone($user, $countyId, $areaId);

        // Get shipping method
        try {
            $shippingMethodId = $this->getPreferredShippingMethodId($user);
        } catch (\Throwable $th) {
            $fallback = ShippingMethod::where('status', ShippingMethodStatus::ACTIVE)->first();
            if (! $fallback) {
                throw new \Exception('No active shipping method found.');
            }
            $shippingMethodId = $fallback->id;
        }

        logger()->info('Test');

        return $this->getShippingRate($shippingZoneId, $shippingMethodId, $totalWeight);
    }

    /**
     * Resolve shipping zone from area, county, or user address
     *
     * @param  User|null  $user
     * @param  int|null  $countyId
     * @param  int|null  $subCountyId
     */
    protected function resolveShippingZone($user, $countyId = null, $subCountyId = null): int
    {
        // Priority 1: If sub-county is provided, get zone from sub-county
        if ($subCountyId) {
            $subCounty = SubCounty::find($subCountyId);
            if ($subCounty && $subCounty->shipping_zone_id) {
                return $subCounty->shipping_zone_id;
            }
        }

        // Priority 2: If county is provided, get zone from county
        if ($countyId) {
            $county = County::find($countyId);
            if ($county && $county->shipping_zone_id) {
                return $county->shipping_zone_id;
            }
        }

        // Priority 3: Try to get from user's default address
        if ($user) {
            try {
                return $this->getShippingZoneFromUser($user);
            } catch (\Exception $e) {
                // Fall through to default
            }
        }

        // No zone resolved — return 0 so the caller can treat shipping as unavailable.
        return 0;
    }
}
