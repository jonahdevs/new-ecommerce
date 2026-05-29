<?php

namespace App\Enums;

enum DeliveryPromotionEffect: string
{
    case FREE = 'free';
    case FLAT_FEE = 'flat_fee';
    case PERCENT_OFF = 'percent_off';

    public function label(): string
    {
        return match ($this) {
            self::FREE => 'Free delivery',
            self::FLAT_FEE => 'Flat fee',
            self::PERCENT_OFF => 'Percent off',
        };
    }
}
