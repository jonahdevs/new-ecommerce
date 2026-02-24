<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.site_name', 'Sheffield Africa');
        $this->migrator->add('general.site_tagline', '');
        $this->migrator->add('general.logo', null);
        $this->migrator->add('general.favicon', null);
        $this->migrator->add('general.contact_email', 'hello@sheffield.com');
        $this->migrator->add('general.support_phone', '');
        $this->migrator->add('general.physical_address', '');
        $this->migrator->add('general.currency', 'KES');
        $this->migrator->add('general.currency_symbol', 'KSh');
        $this->migrator->add('general.timezone', 'Africa/Nairobi');
        $this->migrator->add('general.vat_number', null);
    }
};
