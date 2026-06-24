<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // ==================================================
        // BUSINESS INFO
        // ==================================================
        $this->migrator->add('business.legal_name', config('app.name', 'My Store'));
        $this->migrator->add('business.registration_number', '');
        $this->migrator->addEncrypted('business.tax_pin', '');
        $this->migrator->add('business.contact_email', '');
        $this->migrator->add('business.contact_phone', '');
        $this->migrator->add('business.address', '');
        $this->migrator->add('business.business_hours', '');

        // ==================================================
        // BRANDING
        // ==================================================
        $this->migrator->add('branding.store_name', config('app.name', 'My Store'));
        $this->migrator->add('branding.tagline', '');
        $this->migrator->add('branding.logo_path', null);
        $this->migrator->add('branding.favicon_path', null);

        // ==================================================
        // LOCALIZATION
        // ==================================================
        $this->migrator->add('localization.currency', 'KES');
        $this->migrator->add('localization.weight_unit', 'g');
        $this->migrator->add('localization.dimension_unit', 'mm');
        $this->migrator->add('localization.timezone', 'Africa/Nairobi');
    }
};
