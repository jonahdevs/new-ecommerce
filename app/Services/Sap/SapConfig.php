<?php

namespace App\Services\Sap;

use App\Settings\IntegrationSettings;

/**
 * Resolves SAP configuration by preferring values stored in IntegrationSettings
 * (admin-editable at runtime) and falling back to the .env-backed config file.
 * This means a deployed .env provides sensible defaults, but an admin can
 * override any value via the UI without SSH access.
 */
class SapConfig
{
    public function __construct(private readonly IntegrationSettings $settings) {}

    public function baseUrl(): string
    {
        return rtrim($this->settings->sap_base_url ?: (string) config('sap.base_url', ''), '/');
    }

    public function apiKey(): string
    {
        return $this->settings->sap_api_key ?: (string) config('sap.api_key', '');
    }

    public function webhookSecret(): string
    {
        return $this->settings->sap_webhook_secret ?: (string) config('sap.webhook_secret', '');
    }

    public function businessPin(): string
    {
        return $this->settings->kra_business_pin ?: (string) config('sap.business_pin', '');
    }

    public function verifySsl(): bool
    {
        return (bool) config('sap.verify_ssl', true);
    }

    public function recoveryDelayMinutes(): int
    {
        return (int) config('sap.recovery_delay_minutes', 30);
    }

    public function isEnabled(): bool
    {
        return $this->settings->sap_enabled;
    }

    public function autoSyncOrders(): bool
    {
        return $this->settings->sap_auto_sync_orders;
    }
}
