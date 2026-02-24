<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('maintenance.enabled', false);
        $this->migrator->add('maintenance.message', 'We are currently performing scheduled maintenance. We will be back shortly.');
        $this->migrator->add('maintenance.scheduled_end', null);
        $this->migrator->add('maintenance.contact_email', null);
    }
};
