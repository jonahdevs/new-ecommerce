<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class InventorySettings extends Settings
{
    public bool $inventory_tracking_enabled;
    public int $low_stock_threshold;          // notify when stock falls to this
    public string $out_of_stock_behaviour;       // hide | show | show_with_notice
    public bool $backorders_allowed;
    public string $backorders_message;           // e.g. "Available on backorder"
    public bool $notify_admin_low_stock;
    public bool $notify_admin_out_of_stock;

    public static function group(): string
    {
        return 'inventory';
    }
}
