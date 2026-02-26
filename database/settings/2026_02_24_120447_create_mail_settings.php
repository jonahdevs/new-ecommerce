<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('mail.active_driver', 'smtp');

        // SMTP
        $this->migrator->add('mail.smtp_host', '');
        $this->migrator->add('mail.smtp_port', 587);
        $this->migrator->add('mail.smtp_username', '');
        $this->migrator->addEncrypted('mail.smtp_password', '');
        $this->migrator->add('mail.smtp_encryption', 'tls');

        // Mailgun
        $this->migrator->add('mail.mailgun_domain', '');
        $this->migrator->addEncrypted('mail.mailgun_secret', '');
        $this->migrator->add('mail.mailgun_endpoint', 'api.mailgun.net');

        // Amazon SES
        $this->migrator->addEncrypted('mail.ses_key', '');
        $this->migrator->addEncrypted('mail.ses_secret', '');
        $this->migrator->add('mail.ses_region', 'us-east-1');

        // Postmark
        $this->migrator->addEncrypted('mail.postmark_token', '');

        // Sender
        $this->migrator->add('mail.from_address', '');
        $this->migrator->add('mail.from_name', '');
    }
};
