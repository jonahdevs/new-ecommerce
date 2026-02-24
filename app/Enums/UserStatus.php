<?php

namespace App\Enums;

enum UserStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case BANNED = 'banned';
    case SUSPENDED = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'In Active',
            self::BANNED => 'Banned',
            self::SUSPENDED => 'Suspended',
        };
    }
}
