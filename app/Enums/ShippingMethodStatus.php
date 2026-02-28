<?php

namespace App\Enums;

enum ShippingMethodStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

    // No longer selectable at checkout, but kept because old orders
    // reference it. Never delete a deprecated method.
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

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'green',
            self::INACTIVE => 'zinc',
            self::DEPRECATED => 'red',
        };
    }
}
