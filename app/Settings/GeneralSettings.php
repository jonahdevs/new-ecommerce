<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{

    public string  $site_name;
    public string  $site_tagline;
    public ?string $logo;
    public ?string $favicon;
    public string  $contact_email;
    public string  $support_phone;
    public string  $physical_address;
    public string  $currency;
    public string  $currency_symbol;
    public string  $timezone;
    public ?string $vat_number;

    public static function group(): string
    {
        return 'general';
    }
}
