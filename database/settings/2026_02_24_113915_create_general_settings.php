<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        // Identity
        $this->migrator->add('general.company_name', '');
        $this->migrator->add('general.email_address', '');
        $this->migrator->add('general.phone_number', '');

        // Images
        $this->migrator->add('general.logo_light', null);
        $this->migrator->add('general.logo_dark', null);
        $this->migrator->add('general.logo_icon', null);
        $this->migrator->add('general.favicon', null);

        // Address
        $this->migrator->add('general.address', '');
        $this->migrator->add('general.country', '');
        $this->migrator->add('general.town', '');
        $this->migrator->add('general.postal_code', '');

        // Localization
        $this->migrator->add('general.currency', 'KES');
        $this->migrator->add('general.currency_symbol', 'KSh');
        $this->migrator->add('general.timezone', 'Africa/Nairobi');

        // Business
        $this->migrator->add('general.vat_number', null);
        $this->migrator->add('general.registration_number', null);
    }
};
