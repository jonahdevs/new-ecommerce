<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class LegalSettings extends Settings
{
    public bool $cookie_consent_enabled;

    public static function group(): string
    {
        return 'legal';
    }
}
