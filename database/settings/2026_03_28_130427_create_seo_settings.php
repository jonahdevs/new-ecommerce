<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('seo.meta_title', null);
        $this->migrator->add('seo.meta_description', null);
        $this->migrator->add('seo.meta_keywords', null);
        $this->migrator->add('seo.og_image', null);
        $this->migrator->add('seo.robots_indexing', true);
        $this->migrator->add('seo.sitemap_enabled', true);
        $this->migrator->add('seo.google_site_verification', null);
        $this->migrator->add('seo.canonical_url', null);
    }
};
