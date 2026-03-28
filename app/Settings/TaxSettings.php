<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class TaxSettings extends Settings
{
    public bool $tax_enabled;
    public string $tax_name;               // VAT | GST | Sales Tax
    public float $tax_rate;               // e.g. 16.00 for 16%
    public string $tax_type;               // inclusive | exclusive
    public ?string $tax_registration_number;
    public bool $taxable_shipping;       // apply tax to shipping cost

    public static function group(): string
    {
        return 'tax';
    }
}
