<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class OrderSettings extends Settings
{
    public string $order_id_prefix;          // e.g. ORD-, INV-

    public ?float $minimum_order_amount;     // null = no minimum

    public bool $auto_cancel_unpaid;

    public int $auto_cancel_hours;        // hours before auto-cancel kicks in

    public string $default_order_status;     // pending | processing | on-hold

    public ?string $purchase_note;          // printed on all invoices

    public static function group(): string
    {
        return 'orders';
    }
}
