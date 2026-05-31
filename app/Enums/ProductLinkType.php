<?php

namespace App\Enums;

enum ProductLinkType: string
{
    case UPSELL = 'upsell';
    case CROSS_SELL = 'cross_sell';
    case ACCESSORY = 'accessory';
    case SPARE_PART = 'spare_part';

    public function label(): string
    {
        return match ($this) {
            self::UPSELL => 'Upsells',
            self::CROSS_SELL => 'Cross-sells',
            self::ACCESSORY => 'Accessories',
            self::SPARE_PART => 'Spare parts',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::UPSELL => 'arrow-trending-up',
            self::CROSS_SELL => 'shopping-cart',
            self::ACCESSORY => 'puzzle-piece',
            self::SPARE_PART => 'wrench-screwdriver',
        };
    }
}
