<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // ==================================================
        // EMAIL API CREDENTIALS
        // ==================================================
        $this->migrator->add('email_api.smtp_host', null);
        $this->migrator->add('email_api.smtp_port', null);
        $this->migrator->add('email_api.smtp_encryption', null);
        $this->migrator->add('email_api.smtp_username', null);
        $this->migrator->addEncrypted('email_api.smtp_password', null);
        $this->migrator->add('email_api.mailgun_domain', null);
        $this->migrator->addEncrypted('email_api.mailgun_secret', null);
        $this->migrator->add('email_api.ses_key', null);
        $this->migrator->addEncrypted('email_api.ses_secret', null);
        $this->migrator->add('email_api.ses_region', null);
        $this->migrator->addEncrypted('email_api.postmark_token', null);
        $this->migrator->addEncrypted('email_api.resend_key', null);

        // ==================================================
        // PAYMENT API CREDENTIALS
        // ==================================================
        $this->migrator->add('payment_api.mpesa_env', null);
        $this->migrator->addEncrypted('payment_api.mpesa_consumer_key', null);
        $this->migrator->addEncrypted('payment_api.mpesa_consumer_secret', null);
        $this->migrator->addEncrypted('payment_api.mpesa_passkey', null);
        $this->migrator->add('payment_api.mpesa_callback_url', null);
        $this->migrator->add('payment_api.stripe_key', null);
        $this->migrator->addEncrypted('payment_api.stripe_secret', null);
        $this->migrator->addEncrypted('payment_api.stripe_webhook_secret', null);
        $this->migrator->add('payment_api.paystack_public_key', null);
        $this->migrator->addEncrypted('payment_api.paystack_secret_key', null);
    }
};
