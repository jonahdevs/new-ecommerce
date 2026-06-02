<?php

namespace App\Enums;

enum ShipmentStatus: string
{
    case PENDING = 'pending';
    case BOOKED = 'booked';
    case PICKED_UP = 'picked_up';
    case IN_TRANSIT = 'in_transit';
    case OUT_FOR_DELIVERY = 'out_for_delivery';
    case DELIVERED = 'delivered';
    case FAILED = 'failed';
    case RETURNED = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::BOOKED => 'Booked',
            self::PICKED_UP => 'Picked up',
            self::IN_TRANSIT => 'In transit',
            self::OUT_FOR_DELIVERY => 'Out for delivery',
            self::DELIVERED => 'Delivered',
            self::FAILED => 'Failed',
            self::RETURNED => 'Returned',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'zinc',
            self::BOOKED => 'blue',
            self::PICKED_UP => 'purple',
            self::IN_TRANSIT => 'yellow',
            self::OUT_FOR_DELIVERY => 'orange',
            self::DELIVERED => 'green',
            self::FAILED => 'red',
            self::RETURNED => 'red',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::DELIVERED, self::FAILED, self::RETURNED]);
    }
}
