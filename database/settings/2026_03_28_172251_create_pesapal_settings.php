<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

class CreatePesapalSettings extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('pesapal.enabled', false);
        $this->migrator->add('pesapal.environment', 'sandbox');
        $this->migrator->add('pesapal.ipn_id', null);
        $this->migrator->add('pesapal.callback_url', null);

        // Encrypted at rest using APP_KEY
        $this->migrator->addEncrypted('pesapal.consumer_key', null);
        $this->migrator->addEncrypted('pesapal.consumer_secret', null);
    }
}
