<?php

namespace App\Settings;

use Spatie\LaravelSettings\Attributes\ShouldBeEncrypted;
use Spatie\LaravelSettings\Settings;

class IntegrationSettings extends Settings
{
    public bool $google_login_enabled;

    public ?string $google_client_id;

    #[ShouldBeEncrypted]
    public ?string $google_client_secret;

    public ?string $google_redirect_url;

    #[ShouldBeEncrypted]
    public string $google_maps_api_key;

    public string $map_provider; // 'leaflet' or 'google'

    public bool $recaptcha_enabled;

    public string $recaptcha_site_key;

    public bool $sap_enabled;

    public bool $sap_auto_sync_orders;

    public bool $sap_sync_price;

    public bool $sap_sync_quantity;

    public ?string $sap_base_url;

    #[ShouldBeEncrypted]
    public ?string $sap_api_key;

    #[ShouldBeEncrypted]
    public ?string $sap_webhook_secret;

    #[ShouldBeEncrypted]
    public ?string $kra_business_pin;

    public static function group(): string
    {
        return 'integrations';
    }
}
