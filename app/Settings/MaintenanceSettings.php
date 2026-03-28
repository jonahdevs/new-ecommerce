<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class MaintenanceSettings extends Settings
{
    public bool $maintenance_mode;         // toggle site on/off
    public ?string $maintenance_message;      // shown to visitors
    public ?string $maintenance_allowed_ips;  // comma-separated IPs that bypass maintenance
    public ?string $maintenance_secret;       // secret URL token to bypass e.g. /bypass-token

    public static function group(): string
    {
        return 'maintenance';
    }
}
