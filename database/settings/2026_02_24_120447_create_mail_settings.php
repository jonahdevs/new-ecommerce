<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('mail.driver', 'smtp');
        $this->migrator->add('mail.host', '');
        $this->migrator->add('mail.port', 587);
        $this->migrator->add('mail.username', '');
        $this->migrator->add('mail.password', '');
        $this->migrator->add('mail.encryption', 'tls');
        $this->migrator->add('mail.from_address', '');
        $this->migrator->add('mail.from_name', '');
    }
};
