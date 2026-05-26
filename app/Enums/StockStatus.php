<?php

namespace App\Enums;

enum StockStatus: string
{
    case IN_STOCK = 'in_stock';
    case OUT_OF_STOCK = 'out_of_stock';
    case BACKORDER = 'backorder';

    public function label(): string
    {
        return match ($this) {
            self::IN_STOCK => 'In Stock',
            self::OUT_OF_STOCK => 'Out of Stock',
            self::BACKORDER => 'Backorder',
        };
    }
}
