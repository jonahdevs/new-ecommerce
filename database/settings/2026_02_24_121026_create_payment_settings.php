<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('payment.active_gateway', 'custom');
        $this->migrator->add('payment.mode', 'sandbox');

        // Pesawise
        $this->migrator->add('payment.pesawise_api_key', null);
        $this->migrator->add('payment.pesawise_api_secret', null);
        $this->migrator->add('payment.pesawise_account_number', null);
        $this->migrator->add('payment.pesawise_webhook_secret', null);

        // Paystack
        $this->migrator->add('payment.paystack_public_key', null);
        $this->migrator->add('payment.paystack_secret_key', null);
        $this->migrator->add('payment.paystack_webhook_secret', null);

        // Stripe
        $this->migrator->add('payment.stripe_public_key', null);
        $this->migrator->add('payment.stripe_secret_key', null);
        $this->migrator->add('payment.stripe_webhook_secret', null);

        // PayPal
        $this->migrator->add('payment.paypal_client_id', null);
        $this->migrator->add('payment.paypal_client_secret', null);
        $this->migrator->add('payment.paypal_webhook_id', null);

        // Custom
        $this->migrator->add('payment.custom_name', 'Bank Transfer');
        $this->migrator->add('payment.custom_instructions', null);
    }
};
