<?php

namespace App\Enums;

enum ShippingRateType: string
{
    case FIXED = 'fixed';
    case FREE = 'free';
    case CALCULATED = 'calculated';

    public function label(): string
    {
        return match ($this) {
            self::FIXED => 'Fixed rate',
            self::FREE => 'Always free',
            self::CALCULATED => 'Carrier calculated',
        };
    }
}
