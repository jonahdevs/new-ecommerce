<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case ABANDONED = 'abandoned';
    case FAILED = 'failed';
    case ONGOING = 'ongoing';
    case PAID       = 'paid';
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case QUEUED = 'queued';
    case REVERSED = 'reversed';
    case SUCCESS = 'success';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::PAID, self::SUCCESS => 'emerald',
            self::FAILED              => 'red',
            self::ABANDONED           => 'zinc',
            self::PENDING,
            self::PROCESSING          => 'amber',
            self::ONGOING             => 'blue',
            self::QUEUED              => 'purple',
            self::REVERSED            => 'orange',
            self::CANCELLED           => 'rose',
        };
    }
}
