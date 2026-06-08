<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('payment_api.mpesa_env', null);
        $this->migrator->addEncrypted('payment_api.mpesa_consumer_key', null);
        $this->migrator->addEncrypted('payment_api.mpesa_consumer_secret', null);
        $this->migrator->addEncrypted('payment_api.mpesa_passkey', null);
        $this->migrator->add('payment_api.mpesa_callback_url', null);
        $this->migrator->add('payment_api.stripe_key', null);
        $this->migrator->addEncrypted('payment_api.stripe_secret', null);
        $this->migrator->addEncrypted('payment_api.stripe_webhook_secret', null);
    }
};
