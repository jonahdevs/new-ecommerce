<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Email & SMS
        $this->migrator->add('email.from_name', config('app.name', 'My Store'));
        $this->migrator->add('email.from_address', config('mail.from.address', 'hello@example.com'));
        $this->migrator->add('email.mail_driver', config('mail.default', 'smtp'));
        $this->migrator->add('email.sms_provider', 'none');
        $this->migrator->add('email.sms_sender_id', '');

        // Integrations
        $this->migrator->add('integrations.google_login_enabled', false);
        $this->migrator->add('integrations.google_maps_api_key', '');
        $this->migrator->add('integrations.map_provider', 'leaflet');
        $this->migrator->add('integrations.recaptcha_site_key', '');
        $this->migrator->add('integrations.sap_enabled', false);
        $this->migrator->add('integrations.sap_auto_sync_orders', true);
        $this->migrator->add('integrations.sap_sync_price', true);
        $this->migrator->add('integrations.sap_sync_quantity', true);
        $this->migrator->add('integrations.sap_base_url', null);
        $this->migrator->add('integrations.sap_api_key', null);
        $this->migrator->add('integrations.sap_webhook_secret', null);
        $this->migrator->add('integrations.kra_business_pin', null);

        // Security
        $this->migrator->add('security.min_password_length', 8);
        $this->migrator->add('security.require_two_factor', false);
        $this->migrator->add('security.session_lifetime', (int) config('session.lifetime', 120));

        // Maintenance
        $this->migrator->add('maintenance.maintenance_mode', false);
        $this->migrator->add('maintenance.maintenance_message', 'We are performing scheduled maintenance. Please check back soon.');
    }
};
