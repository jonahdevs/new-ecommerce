<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

class CreatePaypalSettings extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('paypal.enabled', false);
        $this->migrator->add('paypal.environment', 'sandbox');
        $this->migrator->add('paypal.client_id', null);  // semi-public — stored plain

        // Encrypted at rest using APP_KEY
        $this->migrator->addEncrypted('paypal.client_secret', null);
    }
}
