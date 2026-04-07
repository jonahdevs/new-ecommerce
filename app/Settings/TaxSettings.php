<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class TaxSettings extends Settings
{
    public bool $tax_enabled;
    public string $tax_name;               // VAT | GST | Sales Tax
    public ?int $default_tax_class_id;    // fallback tax class when product has none assigned
    public string $tax_type;               // inclusive | exclusive
    public ?string $tax_registration_number;
    public bool $taxable_shipping;        // apply tax to shipping cost

    public static function group(): string
    {
        return 'tax';
    }
}
