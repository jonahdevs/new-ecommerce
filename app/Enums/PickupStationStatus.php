<?php

namespace App\Enums;

enum PickupStationStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

        // Short-term closure (holiday, renovation, flooding).
        // Parcels are not routed here until status returns to active.
    case TEMPORARILY_CLOSED = 'temporarily_closed';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::TEMPORARILY_CLOSED => 'Temporarily Closed',
        };
    }

    public function isAcceptingParcels(): bool
    {
        return $this === self::ACTIVE;
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'green',
            self::INACTIVE => 'zinc',
            self::TEMPORARILY_CLOSED => 'orange',
        };
    }
}
