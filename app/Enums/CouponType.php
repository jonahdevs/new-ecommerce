<?php

namespace App\Enums;

enum CouponType: string
{
    case FIXED = 'fixed';
    case PERCENT = 'percent';

    public function label(): string
    {
        return match ($this) {
            self::FIXED => 'Fixed amount',
            self::PERCENT => 'Percentage',
        };
    }
}
