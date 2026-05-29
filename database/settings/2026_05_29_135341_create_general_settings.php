<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.site_name', config('app.name', 'My Store'));
        $this->migrator->add('general.site_tagline', '');
        $this->migrator->add('general.contact_email', '');
        $this->migrator->add('general.contact_phone', '');
        $this->migrator->add('general.address', '');
        $this->migrator->add('general.currency', 'KES');
        $this->migrator->add('general.timezone', 'Africa/Nairobi');
        $this->migrator->add('general.maintenance_mode', false);
    }
};
