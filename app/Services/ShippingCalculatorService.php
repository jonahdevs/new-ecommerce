<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;

/**
 * Class ShippingCalculatorService.
 */
class ShippingCalculatorService
{

    /**
     * Calculate shipping cost for a cart
     * Uses user's default address and preferred shipping method
     *
     * @param Cart $cart
     * @return float
     * @throws \Exception
     */
    public function calculate(Cart $cart)
    {
        // 1. Calculate total weight
        $totalWeight = $this->calculateTotalWeight($cart);

        // Apply minimum weight if needed
        $minWeight = config('shipping.min_order_weight_kg', 0.1);
        $totalWeight = max($totalWeight, $minWeight);

        // 2. Get shipping zone from user's default address
        $shippingZoneId = $this->getShippingZoneFromUser($cart->user);

        // 3. Determine which shipping method to use
        $shippingMethodId = $this->getPreferredShippingMethodId($cart->user);

        \Log::info("Calculating shipping: Zone ID $shippingZoneId, Method ID $shippingMethodId, Total Weight $totalWeight kg");

        // 4. Get the specific rate
        $rate = $this->getShippingRate($shippingZoneId, $shippingMethodId, $totalWeight);

        return (float) $rate;
    }


    /**
     * Get Shipping zone from user's default address
     *
     * @param \App\Models\User|null $user
     * @return int
     * @throws \Exception
     */
    protected function getShippingZoneFromUser($user): int
    {
        if (!$user) {
            throw new \Exception('User must be authenticated to calculate shipping.');
        }

        $defaultAddress = $user->defaultAddress;



        if (!$defaultAddress || !$defaultAddress->shipping_zone_id) {
            return 1;
        }

        return $defaultAddress->shipping_zone_id;
    }

    /**
     * Get user's preferred shipping method ID or fallback to standard
     *
     * @param \App\Models\User|null $user
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
            ->where('is_active', true)
            ->first();

        if (!$standardMethod) {
            throw new \Exception('Default shipping method not found.');
        }

        return $standardMethod->id;
    }

    /**
     * Get shipping rate for specific zone, method, and weight
     *
     * @param int $shippingZoneId
     * @param int $shippingMethodId
     * @param float $totalWeightKg
     * @return float
     */
    protected function getShippingRate(int $shippingZoneId, int $shippingMethodId, float $totalWeightKg): float
    {
        $rate = ShippingRate::query()
            ->where('shipping_zone_id', $shippingZoneId)
            ->where('shipping_method_id', $shippingMethodId)
            ->where('is_active', true)
            ->where('min_weight', '<=', $totalWeightKg)
            ->where('max_weight', '>=', $totalWeightKg)
            ->first();

        // If no rate found, return fallback
        if (!$rate) {
            return 0;
        }

        return (float) $rate->price;
    }

    protected function calculateTotalWeight(Cart $cart)
    {
        $totalWeightGrams = $cart->items->reduce(function ($carry, $item) {
            $product = $item->product;


            if (!$product) {
                return $carry;
            }

            $weightGrams = 0;

            if ($item->variant_id && $item->variant) {
                // Variant has its own weight
                $weightGrams = $item->variant->weight ?? $product->weight ?? 0;
            } else {
                // Use product weight
                $weightGrams = $product->weight ?? 0;
            }

            return $carry + ($weightGrams * $item->quantity);
        }, 0);

        return round($totalWeightGrams / 1000, 2);
    }
}
