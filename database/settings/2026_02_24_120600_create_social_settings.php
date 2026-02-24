<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('social.facebook', null);
        $this->migrator->add('social.instagram', null);
        $this->migrator->add('social.twitter', null);
        $this->migrator->add('social.tiktok', null);
        $this->migrator->add('social.youtube', null);
        $this->migrator->add('social.whatsapp', null);
        $this->migrator->add('social.linkedin', null);
    }
};
