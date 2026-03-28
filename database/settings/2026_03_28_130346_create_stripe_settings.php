<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

class CreateStripeSettings extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('stripe.enabled', false);
        $this->migrator->add('stripe.environment', 'sandbox');
        $this->migrator->add('stripe.public_key', null);  // publishable key — safe to store plain

        // Encrypted at rest using APP_KEY
        $this->migrator->addEncrypted('stripe.secret_key', null);
        $this->migrator->addEncrypted('stripe.webhook_secret', null);
    }
}
