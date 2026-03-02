<?php

namespace App\Services;

/**
 * CheckoutSession
 *
 * Single source of truth for all checkout state.
 * Every checkout component reads from and writes to this class.
 * Nothing touches session('checkout.*') directly except this class.
 */
class CheckoutSession
{
    private const KEY_SHIPPING = 'checkout.shipping';
    private const KEY_ADDRESS = 'checkout.address_id';

    //  Shipping

    public function setShipping(array $option): void
    {
        session([
            self::KEY_SHIPPING => [
                'method_id' => $option['method_id'],
                'method_name' => $option['method_name'],
                'method_code' => $option['method_code'],
                'method_type' => $option['method_type'],
                'cost' => $option['cost'],
                'zone_id' => $option['zone_id'],
                'rate_id' => $option['rate_id'] ?? null,
                'station_id' => $option['station_id'] ?? null,
                'station_name' => $option['station_name'] ?? null,
                'cost_breakdown' => $option['cost_breakdown'],
                'delivery_window' => $option['delivery_window'],
            ]
        ]);
    }

    public function getShipping(): ?array
    {
        return session(self::KEY_SHIPPING);
    }

    public function hasShipping(): bool
    {
        return !empty(session(self::KEY_SHIPPING));
    }

    public function getShippingCost(): float
    {
        return (float) (session(self::KEY_SHIPPING . '.cost') ?? 0);
    }

    public function getShippingMethodName(): ?string
    {
        return session(self::KEY_SHIPPING . '.method_name');
    }

    public function getShippingMethodId(): ?int
    {
        return session(self::KEY_SHIPPING . '.method_id');
    }

    public function getShippingZoneId(): ?int
    {
        return session(self::KEY_SHIPPING . '.zone_id');
    }

    public function getShippingRateId(): ?int
    {
        return session(self::KEY_SHIPPING . '.rate_id');
    }

    public function getPickupStationId(): ?int
    {
        return session(self::KEY_SHIPPING . '.station_id');
    }

    public function getCostBreakdown(): array
    {
        return session(self::KEY_SHIPPING . '.cost_breakdown', []);
    }

    public function getDeliveryWindow(): ?string
    {
        return session(self::KEY_SHIPPING . '.delivery_window');
    }

    public function isPus(): bool
    {
        return session(self::KEY_SHIPPING . '.method_type') === 'pus';
    }

    public function clearShipping(): void
    {
        session()->forget(self::KEY_SHIPPING);
    }

    //  Address

    public function setAddressId(int $addressId): void
    {
        session([self::KEY_ADDRESS => $addressId]);
    }

    public function getAddressId(): ?int
    {
        return session(self::KEY_ADDRESS);
    }

    public function clearAddressId(): void
    {
        session()->forget(self::KEY_ADDRESS);
    }

    //  Helpers

    public function isComplete(): bool
    {
        return $this->hasShipping();
    }

    public function setPaymentMethod(string $method): void
    {
        session(['checkout.payment_method' => $method]);
    }

    public function getPaymentMethod(): string
    {
        return session('checkout.payment_method', 'mpesa');
    }

    public function hasPaymentMethod(): bool
    {
        return session()->has('checkout.payment_method');
    }

    /**
     * Wipe all checkout state.
     * Call this after order is placed successfully.
     */
    public function clear(): void
    {
        session()->forget([
            'checkout.shipping',
            'checkout.address_id',
            'checkout.payment_method', // ← add this
        ]);
    }
}
