<?php

namespace App\Enums;

enum FreeShippingRuleStatus: string
{
    // Created but start date not yet reached.
    case SCHEDULED = 'scheduled';

    // Currently being applied at checkout.
    case ACTIVE = 'active';

    // End date has passed — kept for reporting.
    case EXPIRED = 'expired';

    // Manually disabled regardless of dates.
    case INACTIVE = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::SCHEDULED => 'Scheduled',
            self::ACTIVE => 'Active',
            self::EXPIRED => 'Expired',
            self::INACTIVE => 'Inactive',
        };
    }

    public function isApplicable(): bool
    {
        return $this === self::ACTIVE;
    }

    public function color(): string
    {
        return match ($this) {
            self::SCHEDULED => 'blue',
            self::ACTIVE => 'green',
            self::EXPIRED => 'zinc',
            self::INACTIVE => 'zinc',
        };
    }
}
