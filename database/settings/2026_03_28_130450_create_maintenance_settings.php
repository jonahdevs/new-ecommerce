<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('maintenance.maintenance_mode', false);
        $this->migrator->add('maintenance.maintenance_message', 'We are currently down for maintenance. Please check back soon.');
        $this->migrator->add('maintenance.maintenance_allowed_ips', null); // comma-separated e.g. "127.0.0.1,192.168.1.1"
        $this->migrator->add('maintenance.maintenance_secret', null);      // bypass token e.g. "my-secret-bypass"
    }
};
