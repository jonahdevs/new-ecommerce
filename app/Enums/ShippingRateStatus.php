<?php

namespace App\Enums;

enum ShippingRateStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

    // Superseded by a newer rate — kept so historical orders can
    // still resolve their rate. Never delete an expired rate.
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::EXPIRED => 'Expired',
        };
    }

    public function isUsable(): bool
    {
        return $this === self::ACTIVE;
    }
}
