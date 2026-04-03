<?php

namespace App\Enums;

enum LogisticsProviderStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

    // Operational or billing issue — still referenced in historical
    // orders but unavailable at checkout.
    case SUSPENDED = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::SUSPENDED => 'Suspended',
        };
    }

    public function isAvailable(): bool
    {
        return $this === self::ACTIVE;
    }

    public function color()
    {
        return match ($this) {
            self::ACTIVE => 'green',
            self::INACTIVE => 'zinc',
            self::SUSPENDED => 'red',
        };
    }
}
