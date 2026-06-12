<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::SUCCESS => 'Paid',
            self::FAILED => 'Failed',
            self::CANCELLED => 'Cancelled',
            self::REFUNDED => 'Refunded',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::PENDING => 'amber',
            self::SUCCESS => 'green',
            self::FAILED => 'red',
            self::CANCELLED => 'zinc',
            self::REFUNDED => 'purple',
        };
    }

    public function isFinal(): bool
    {
        return $this !== self::PENDING;
    }
}
