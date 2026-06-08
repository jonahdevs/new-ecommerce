<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
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
    }
};
