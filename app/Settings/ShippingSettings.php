<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class ShippingSettings extends Settings
{
    public int $free_shipping_threshold;

    public int $handling_fee;

    public bool $local_pickup_enabled;

    public string $pickup_address;

    public static function group(): string
    {
        return 'shipping';
    }
}
