<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class ShippingSettings extends Settings
{

    // Free Shipping
    public bool  $free_shipping_enabled;
    public float $free_shipping_threshold;

    // Delivery Estimates
    public int    $estimated_delivery_days_min;
    public int    $estimated_delivery_days_max;
    public string $delivery_estimate_message; // shown at checkout

    // Defaults
    public bool   $allow_pickup;              // allow pickup station option
    public string $default_weight_unit;       // kg or g
    public float  $default_packaging_weight;  // added to product weight

    public static function group(): string
    {
        return 'shipping';
    }
}
