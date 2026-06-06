<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case OUT_FOR_DELIVERY = 'out_for_delivery';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PROCESSING => 'Processing',
            self::OUT_FOR_DELIVERY => 'Out for delivery',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::PENDING => 'amber',
            self::PROCESSING => 'blue',
            self::OUT_FOR_DELIVERY => 'orange',
            self::COMPLETED => 'green',
            self::CANCELLED => 'red',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'text-amber-600',
            self::PROCESSING => 'text-blue-600',
            self::OUT_FOR_DELIVERY => 'text-brand-500',
            self::COMPLETED => 'text-emerald-600',
            self::CANCELLED => 'text-red-500',
        };
    }
}
