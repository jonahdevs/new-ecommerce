<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class LocalizationSettings extends Settings
{
    // Currency
    public string $currency;           // e.g. KES, USD, GBP
    public string $currency_symbol;    // e.g. Ksh, $, £
    public string $currency_position;  // before | after | before_space | after_space

    // Number Formatting
    public string $decimal_separator;  // . or ,
    public string $thousands_separator; // , or . or space
    public int $decimal_places;     // 0, 2

    // Locale
    public string $timezone;           // e.g. Africa/Nairobi
    public string $date_format;        // e.g. d/m/Y, m/d/Y, Y-m-d
    public string $time_format;        // 12 | 24
    public string $language;           // e.g. en, sw

    public static function group(): string
    {
        return 'localization';
    }
}
