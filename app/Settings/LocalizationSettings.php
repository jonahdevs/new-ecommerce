<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class LocalizationSettings extends Settings
{
    public string $country;

    public string $language;

    public string $currency;

    public string $timezone;

    public string $date_format;

    public string $weight_unit;

    public string $dimension_unit;

    public static function group(): string
    {
        return 'localization';
    }
}
