<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('payment.active_gateway', 'custom');

        // Pesawise
        $this->migrator->add('payment.pesawise_mode_production', false);
        $this->migrator->add('payment.pesawise_api_key', null);
        $this->migrator->addEncrypted('payment.pesawise_api_secret', null);
        $this->migrator->add('payment.pesawise_account_number', null);
        $this->migrator->addEncrypted('payment.pesawise_webhook_secret', null);

        // Pesapal
        $this->migrator->add('payment.pesapal_mode_production', false);
        $this->migrator->add('payment.pesapal_consumer_key', null);
        $this->migrator->addEncrypted('payment.pesapal_consumer_secret', null);
        $this->migrator->addEncrypted('payment.pesapal_webhook_secret', null);
        $this->migrator->add('payment.pesapal_ipn_id', null);

        // PayPal
        $this->migrator->add('payment.paypal_mode_production', false);
        $this->migrator->add('payment.paypal_client_id', null);
        $this->migrator->addEncrypted('payment.paypal_client_secret', null);
        $this->migrator->add('payment.paypal_webhook_id', null);

        // Stripe
        $this->migrator->add('payment.stripe_mode_production', false);
        $this->migrator->add('payment.stripe_public_key', null);
        $this->migrator->addEncrypted('payment.stripe_secret_key', null);
        $this->migrator->addEncrypted('payment.stripe_webhook_secret', null);

        // M-Pesa Daraja
        $this->migrator->add('payment.mpesa_mode_production', false);
        $this->migrator->add('payment.mpesa_consumer_key', null);
        $this->migrator->addEncrypted('payment.mpesa_consumer_secret', null);
        $this->migrator->add('payment.mpesa_shortcode', null);
        $this->migrator->addEncrypted('payment.mpesa_passkey', null);
        $this->migrator->add('payment.mpesa_callback_url', null);
    }
};
