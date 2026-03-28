<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    // Store Identity
    public string $store_name;
    public ?string $store_tagline;
    public ?string $store_logo;
    public ?string $store_favicon;

    // Contact
    public ?string $store_email;
    public ?string $store_phone;
    public ?string $store_address;
    public ?string $store_address_line_2;
    public ?string $store_city;
    public ?string $store_state;
    public ?string $store_postal_code;
    public ?string $store_country;

    public static function group(): string
    {
        return 'general';
    }
}
