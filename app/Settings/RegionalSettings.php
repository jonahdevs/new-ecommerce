<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class RegionalSettings extends Settings
{
    // Weight
    public string $weight_unit;      // kg | lb | g | oz

    // Dimensions
    public string $dimension_unit;   // cm | m | inch | ft

    public static function group(): string
    {
        return 'regional';
    }
}
