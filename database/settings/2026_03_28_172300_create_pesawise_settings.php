<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

class CreatePesawiseSettings extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('pesawise.enabled', false);
        $this->migrator->add('pesawise.environment', 'sandbox');
        $this->migrator->add('pesawise.account_number', null);
        $this->migrator->add('pesawise.callback_url', null);

        // Encrypted at rest using APP_KEY
        $this->migrator->addEncrypted('pesawise.api_key', null);
        $this->migrator->addEncrypted('pesawise.api_secret', null);
    }
}
