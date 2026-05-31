<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // ── Business info ──────────────────────────────────────────────────
        $this->migrator->add('business.legal_name', config('app.name', 'My Store'));
        $this->migrator->add('business.trading_name', config('app.name', 'My Store'));
        $this->migrator->add('business.registration_number', '');
        $this->migrator->add('business.tax_pin', '');
        $this->migrator->add('business.contact_email', '');
        $this->migrator->add('business.contact_phone', '');
        $this->migrator->add('business.address', '');
        $this->migrator->add('business.business_hours', '');

        // ── Branding ───────────────────────────────────────────────────────
        $this->migrator->add('branding.store_name', config('app.name', 'My Store'));
        $this->migrator->add('branding.tagline', '');
        $this->migrator->add('branding.logo_path', null);
        $this->migrator->add('branding.favicon_path', null);
        $this->migrator->add('branding.brand_color', '#b91c1c');

        // ── Localization ───────────────────────────────────────────────────
        $this->migrator->add('localization.country', 'KE');
        $this->migrator->add('localization.language', 'en');
        $this->migrator->add('localization.currency', 'KES');
        $this->migrator->add('localization.timezone', 'Africa/Nairobi');
        $this->migrator->add('localization.date_format', 'd M Y');
        $this->migrator->add('localization.weight_unit', 'g');
        $this->migrator->add('localization.dimension_unit', 'mm');
    }
};
