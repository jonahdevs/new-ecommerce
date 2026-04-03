<?php

namespace App\Enums;

enum VehicleRateStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case DEPRECATED = 'deprecated';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::DEPRECATED => 'Deprecated',
        };
    }

    public function isAvailableAtCheckout(): bool
    {
        return $this === self::ACTIVE;
    }
}
