<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class MaintenanceSettings extends Settings
{
    public bool    $enabled;
    public string  $message;
    public ?string $scheduled_end;
    public ?string $contact_email;


    public static function group(): string
    {
        return 'maintenance';
    }
}
