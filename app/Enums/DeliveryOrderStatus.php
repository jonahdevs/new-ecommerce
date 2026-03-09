<?php

namespace App\Enums;

enum DeliveryOrderStatus: string
{
    case PENDING         = 'pending';
    case PICKED_UP        = 'picked_up';
    case IN_TRANSIT       = 'in_transit';
    case OUT_FOR_DELIVERY  = 'out_for_delivery';
    case DELIVERED       = 'delivered';
    case FAILED          = 'failed';
    case AT_STATION       = 'at_station';       // PUS: arrived, awaiting collection
    case COLLECTED       = 'collected';        // PUS: customer collected
    case RETURNING       = 'returning';        // Failed — being returned to sender
    case RETURNED        = 'returned';         // Back with sender
    case CANCELLED       = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING        => 'Pending',
            self::PICKED_UP       => 'Picked Up',
            self::IN_TRANSIT      => 'In Transit',
            self::OUT_FOR_DELIVERY => 'Out for Delivery',
            self::DELIVERED      => 'Delivered',
            self::FAILED         => 'Failed',
            self::AT_STATION      => 'At Station',
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
            self::PICKED_UP       => 'blue',
            self::IN_TRANSIT      => 'blue',
            self::OUT_FOR_DELIVERY => 'purple',
            self::DELIVERED      => 'green',
            self::FAILED         => 'red',
            self::AT_STATION      => 'orange',
            self::COLLECTED      => 'green',
            self::RETURNING      => 'yellow',
            self::RETURNED       => 'zinc',
            self::CANCELLED      => 'red',
        };
    }
}
