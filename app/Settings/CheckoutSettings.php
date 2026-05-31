<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class CheckoutSettings extends Settings
{
    public int $min_order_value;

    public string $order_prefix;

    public int $order_next_number;

    public static function group(): string
    {
        return 'checkout';
    }
}
