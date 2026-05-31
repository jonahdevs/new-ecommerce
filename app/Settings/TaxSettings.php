<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class TaxSettings extends Settings
{
    public bool $tax_enabled;

    public ?int $default_tax_class_id;

    public bool $prices_include_tax;

    public string $price_display;

    public static function group(): string
    {
        return 'tax';
    }
}
