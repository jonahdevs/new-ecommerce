<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class CurrencySettings extends Settings
{
    public string $symbol;

    public string $symbol_position;

    public int $decimals;

    public string $thousand_separator;

    public string $decimal_separator;

    public static function group(): string
    {
        return 'currency';
    }
}
