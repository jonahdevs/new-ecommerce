<?php

namespace App\Enums;

enum ProductRelationshipType: string
{
    case UP_SELLS = 'up_sells';
    case CROSS_SELL = 'cross_sell';
    case GROUPED = 'grouped';
    case ACCESSORY = 'accessory';

    public function label()
    {
        return match ($this) {
            self::UP_SELLS => 'Up Sell',
            self::CROSS_SELL => 'Cross Sell',
            self::GROUPED => 'Grouped',
            self::ACCESSORY => 'Accessory',
        };
    }
}
