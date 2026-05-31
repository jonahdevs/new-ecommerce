<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class BusinessSettings extends Settings
{
    public string $legal_name;

    public string $trading_name;

    public string $registration_number;

    public string $tax_pin;

    public string $contact_email;

    public string $contact_phone;

    public string $address;

    public string $business_hours;

    public static function group(): string
    {
        return 'business';
    }
}
