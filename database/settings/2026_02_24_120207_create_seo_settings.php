<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('seo.meta_title', '');
        $this->migrator->add('seo.meta_description', '');
        $this->migrator->add('seo.meta_keywords', '');
        $this->migrator->add('seo.og_image', null);
        $this->migrator->add('seo.google_analytics_id', null);
        $this->migrator->add('seo.google_tag_manager_id', null);
        $this->migrator->add('seo.google_site_verification', null);
        $this->migrator->add('seo.indexing_enabled', true);
    }
};
