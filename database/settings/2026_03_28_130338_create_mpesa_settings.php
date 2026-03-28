<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

class CreateMpesaSettings extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('mpesa.enabled', false);
        $this->migrator->add('mpesa.environment', 'sandbox');
        $this->migrator->add('mpesa.shortcode', null);
        $this->migrator->add('mpesa.shortcode_type', 'paybill');
        $this->migrator->add('mpesa.initiator_name', null);
        $this->migrator->add('mpesa.callback_url', null);

        // Encrypted at rest using APP_KEY
        $this->migrator->addEncrypted('mpesa.consumer_key', null);
        $this->migrator->addEncrypted('mpesa.consumer_secret', null);
        $this->migrator->addEncrypted('mpesa.passkey', null);
        $this->migrator->addEncrypted('mpesa.initiator_password', null);
    }
}
