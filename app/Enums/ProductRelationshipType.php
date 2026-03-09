<?php

namespace App\Enums;

enum ProductRelationshipType: string
{
    case UP_SELLS = 'up_sells';
    case CROSS_SELL = 'cross_sell';
    case GROUPED = 'grouped';


    public function label()
    {
        return match ($this) {
            self::UP_SELLS => 'Up Sell',
            self::CROSS_SELL => 'Cross Sell',
            self::GROUPED => 'Grouped',
        };
    }
}
