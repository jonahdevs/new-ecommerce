<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    // Identity
    public string $company_name = '';
    public string $email_address = '';
    public string $phone_number = '';

    // Images
    public ?string $logo_light = null;
    public ?string $logo_dark = null;
    public ?string $logo_icon = null;
    public ?string $favicon = null;

    // Address
    public string $address = '';
    public string $country = '';
    public string $town = '';
    public string $postal_code = '';

    // Localization
    public string $currency = 'KES';
    public string $currency_symbol = 'KSh';
    public string $timezone = 'Africa/Nairobi';

    // Business
    public ?string $vat_number = null;
    public ?string $registration_number = null;

    public static function group(): string
    {
        return 'general';
    }
}
