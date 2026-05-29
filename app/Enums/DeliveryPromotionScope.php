<?php

namespace App\Enums;

enum DeliveryPromotionScope: string
{
    case GLOBAL = 'global';
    case ZONE = 'zone';

    public function label(): string
    {
        return match ($this) {
            self::GLOBAL => 'All zones',
            self::ZONE => 'Specific zone',
        };
    }
}
