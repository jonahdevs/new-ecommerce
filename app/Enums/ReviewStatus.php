<?php

namespace App\Enums;

enum ReviewStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::PENDING => 'amber',
            self::APPROVED => 'green',
            self::REJECTED => 'red',
        };
    }
}
