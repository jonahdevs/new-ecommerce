<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class InventorySettings extends Settings
{
    public bool $track_stock_by_default;

    public int $low_stock_threshold;

    public string $out_of_stock_behavior;

    public bool $allow_backorders_by_default;

    public static function group(): string
    {
        return 'inventory';
    }
}
