<?php

namespace App\Enums;

enum AddonType: string
{
    case PUS           = 'pus';
    case FUEL_SURCHARGE = 'fuel_surcharge';
    case REMOTE_AREA    = 'remote_area';

    public function label(): string
    {
        return match ($this) {
            self::PUS           => 'Pickup Station Surcharge',
            self::FUEL_SURCHARGE => 'Fuel Surcharge',
            self::REMOTE_AREA    => 'Remote Area Fee',
        };
    }
}
