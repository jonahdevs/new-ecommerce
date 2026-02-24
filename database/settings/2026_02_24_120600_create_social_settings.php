<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('social.facebook', 'https://www.facebook.com/SheffieldAfricaFacilitySolutions');
        $this->migrator->add('social.instagram', 'https://www.instagram.com/sheffieldafrica/');
        $this->migrator->add('social.twitter', 'https://x.com/sheffield_afric/');
        $this->migrator->add('social.tiktok', 'https://www.tiktok.com/@sheffieldafrica');
        $this->migrator->add('social.youtube', 'https://www.youtube.com/channel/UCK-oWPdQazenIHndl4zABew');
        $this->migrator->add('social.whatsapp', null);
        $this->migrator->add('social.linkedin', 'https://www.linkedin.com/company/sheffield-steel-systems-ltd/');
    }
};
