<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class InventorySettings extends Settings
{
    public bool $inventory_tracking_enabled;

    public int $low_stock_threshold;

    public static function group(): string
    {
        return 'inventory';
    }
}
