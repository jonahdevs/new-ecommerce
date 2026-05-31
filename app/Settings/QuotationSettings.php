<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class QuotationSettings extends Settings
{
    public bool $quotes_enabled;

    public int $default_validity_days;

    public string $quote_prefix;

    public string $quote_terms;

    public static function group(): string
    {
        return 'quotations';
    }
}
