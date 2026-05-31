<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class LegalSettings extends Settings
{
    public string $terms_conditions;

    public string $privacy_policy;

    public string $returns_policy;

    public string $shipping_policy;

    public bool $cookie_consent_enabled;

    public static function group(): string
    {
        return 'legal';
    }
}
