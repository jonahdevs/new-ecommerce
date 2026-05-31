<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class IntegrationSettings extends Settings
{
    public bool $google_login_enabled;

    public bool $facebook_login_enabled;

    public string $google_maps_api_key;

    public string $recaptcha_site_key;

    public static function group(): string
    {
        return 'integrations';
    }
}
