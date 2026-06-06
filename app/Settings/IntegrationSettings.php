<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class IntegrationSettings extends Settings
{
    public bool $google_login_enabled;

    public string $google_maps_api_key;

    public string $map_provider; // 'leaflet' or 'google'

    public string $recaptcha_site_key;

    public bool $sap_enabled;

    public bool $sap_auto_sync_orders;

    public bool $sap_sync_price;

    public bool $sap_sync_quantity;

    public ?string $sap_base_url;

    public ?string $sap_api_key;

    public ?string $sap_webhook_secret;

    public ?string $kra_business_pin;

    public static function group(): string
    {
        return 'integrations';
    }
}
