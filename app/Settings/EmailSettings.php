<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class EmailSettings extends Settings
{
    public string $from_name;

    public string $from_address;

    public string $mail_driver;

    public string $sms_provider;

    public string $sms_sender_id;

    public static function group(): string
    {
        return 'email';
    }
}
