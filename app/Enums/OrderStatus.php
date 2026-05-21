<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
    case RETURNED = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PROCESSING => 'Processing',
            self::SHIPPED => 'Shipped',
            self::DELIVERED => 'Delivered',
            self::CANCELLED => 'Cancelled',
            self::RETURNED => 'Returned',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'amber',
            self::PROCESSING => 'purple',
            self::SHIPPED => 'indigo',
            self::DELIVERED => 'emerald',
            self::CANCELLED => 'rose',
            self::RETURNED => 'orange',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PENDING => 'clock',
            self::PROCESSING => 'loader-circle',
            self::SHIPPED => 'truck',
            self::DELIVERED => 'package-check',
            self::CANCELLED => 'x-circle',
            self::RETURNED => 'rotate-ccw',
        };
    }

    public function canTransitionTo(self $new): bool
    {
        return in_array($new, $this->allowedTransitions());
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::PROCESSING, self::CANCELLED],
            self::PROCESSING => [self::SHIPPED, self::CANCELLED],
            self::SHIPPED => [self::DELIVERED, self::RETURNED],
            self::DELIVERED => [self::RETURNED],
            self::CANCELLED => [],
            self::RETURNED => [],
        };
    }

    public function isTerminal(): bool
    {
        return empty($this->allowedTransitions());
    }

    public function isActive(): bool
    {
        return in_array($this, [
            self::PENDING,
            self::PROCESSING,
            self::SHIPPED,
        ]);
    }

    public function isComplete(): bool
    {
        return $this === self::DELIVERED;
    }
}
