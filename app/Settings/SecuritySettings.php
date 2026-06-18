<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SecuritySettings extends Settings
{
    public int $min_password_length;

    public bool $require_two_factor;

    public int $session_lifetime;

    public int $max_concurrent_sessions;

    public static function group(): string
    {
        return 'security';
    }
}
