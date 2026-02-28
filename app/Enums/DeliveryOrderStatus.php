<?php

namespace App\Enums;

enum DeliveryOrderStatus: string
{
    case PENDING         = 'pending';
    case PICKEDUP        = 'picked_up';
    case INTRANSIT       = 'in_transit';
    case OUTFORDELIVERY  = 'out_for_delivery';
    case DELIVERED       = 'delivered';
    case FAILED          = 'failed';
    case ATSTATION       = 'at_station';       // PUS: arrived, awaiting collection
    case COLLECTED       = 'collected';        // PUS: customer collected
    case RETURNING       = 'returning';        // Failed — being returned to sender
    case RETURNED        = 'returned';         // Back with sender
    case CANCELLED       = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING        => 'Pending',
            self::PICKEDUP       => 'Picked Up',
            self::INTRANSIT      => 'In Transit',
            self::OUTFORDELIVERY => 'Out for Delivery',
            self::DELIVERED      => 'Delivered',
            self::FAILED         => 'Failed',
            self::ATSTATION      => 'At Station',
            self::COLLECTED      => 'Collected',
            self::RETURNING      => 'Returning',
            self::RETURNED       => 'Returned',
            self::CANCELLED      => 'Cancelled',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::DELIVERED,
            self::COLLECTED,
            self::RETURNED,
            self::CANCELLED,
        ]);
    }

    public function isActive(): bool
    {
        return ! $this->isTerminal();
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING        => 'zinc',
            self::PICKEDUP       => 'blue',
            self::INTRANSIT      => 'blue',
            self::OUTFORDELIVERY => 'purple',
            self::DELIVERED      => 'green',
            self::FAILED         => 'red',
            self::ATSTATION      => 'orange',
            self::COLLECTED      => 'green',
            self::RETURNING      => 'yellow',
            self::RETURNED       => 'zinc',
            self::CANCELLED      => 'red',
        };
    }
}
