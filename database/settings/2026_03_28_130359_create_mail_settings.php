<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

class CreateMailSettings extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('mail.mailer', 'smtp');
        $this->migrator->add('mail.host', null);
        $this->migrator->add('mail.port', 587);
        $this->migrator->add('mail.username', null);
        $this->migrator->add('mail.encryption', 'tls');
        $this->migrator->add('mail.from_address', null);
        $this->migrator->add('mail.from_name', 'Sheffield Africa');
        $this->migrator->add('mail.reply_to_address', null);

        // Encrypted at rest using APP_KEY
        $this->migrator->addEncrypted('mail.password', null);
    }
}
