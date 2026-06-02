<?php

namespace App\Enums;

enum ShippingMethodType: string
{
    case DELIVERY = 'delivery';
    case PICKUP = 'pickup';

    public function label(): string
    {
        return match ($this) {
            self::DELIVERY => 'Home Delivery',
            self::PICKUP => 'Pickup',
        };
    }
}
