<?php

namespace App\Enums;

use App\Logistics\Drivers\AramexDriver;
use App\Logistics\Drivers\CossimDriver;
use App\Logistics\Drivers\DhlDriver;
use App\Logistics\Drivers\FargoDriver;
use App\Logistics\Drivers\GlovoDriver;
use App\Logistics\Drivers\SelfManagedDriver;

enum CarrierDriver: string
{
    case SELF_MANAGED = 'self_managed';
    case FARGO = 'fargo';
    case COSSIM = 'cossim';
    case DHL = 'dhl';
    case ARAMEX = 'aramex';
    case GLOVO = 'glovo';

    public function label(): string
    {
        return match ($this) {
            self::SELF_MANAGED => 'Self-managed (own fleet)',
            self::FARGO => 'Fargo Courier',
            self::COSSIM => 'Cossim Logistics',
            self::DHL => 'DHL',
            self::ARAMEX => 'Aramex',
            self::GLOVO => 'Glovo',
        };
    }

    public function driverClass(): string
    {
        return match ($this) {
            self::SELF_MANAGED => SelfManagedDriver::class,
            self::FARGO => FargoDriver::class,
            self::COSSIM => CossimDriver::class,
            self::DHL => DhlDriver::class,
            self::ARAMEX => AramexDriver::class,
            self::GLOVO => GlovoDriver::class,
        };
    }
}
