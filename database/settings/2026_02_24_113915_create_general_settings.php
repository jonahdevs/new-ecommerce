<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        // Identity
        $this->migrator->add('general.company_name', 'Sheffield Africa');
        $this->migrator->add('general.email_address', 'info@sheffieldafrica.com');
        $this->migrator->add('general.phone_number', '+254 713 777 111');

        // Images
        $this->migrator->add('general.logo_light', null);
        $this->migrator->add('general.logo_dark', null);
        $this->migrator->add('general.apple_icon', null);
        $this->migrator->add('general.favicon', null);

        // Address
        $this->migrator->add('general.address', 'Off Old Mombasa Road before the Nairobi SGR Terminus');
        $this->migrator->add('general.country', 'Kenya');
        $this->migrator->add('general.town', 'Nairobi');
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
