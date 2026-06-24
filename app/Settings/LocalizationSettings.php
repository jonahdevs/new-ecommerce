<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class LocalizationSettings extends Settings
{
    public string $currency;

    public string $weight_unit;

    public string $dimension_unit;

    public string $timezone;

    public static function group(): string
    {
        return 'localization';
    }
}
