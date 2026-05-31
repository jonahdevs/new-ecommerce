<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class BrandingSettings extends Settings
{
    public string $store_name;

    public string $tagline;

    public ?string $logo_path;

    public ?string $favicon_path;

    public string $brand_color;

    public static function group(): string
    {
        return 'branding';
    }
}
