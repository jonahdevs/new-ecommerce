<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class OrderSettings extends Settings
{
    public string $order_id_prefix;          // e.g. ORD-, INV-
    public ?float $minimum_order_amount;     // null = no minimum
    public bool $guest_checkout_enabled;
    public bool $auto_cancel_unpaid;
    public int $auto_cancel_hours;        // hours before auto-cancel kicks in
    public bool $stock_reduce_on_order;    // reduce stock on order or on payment
    public string $default_order_status;     // pending | processing | on-hold

    public static function group(): string
    {
        return 'orders';
    }
}
