<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class InvoiceSettings extends Settings
{
    public string $invoice_prefix;

    public int $invoice_next_number;

    public string $invoice_footer;

    public bool $show_tax_pin;

    public static function group(): string
    {
        return 'invoicing';
    }
}
