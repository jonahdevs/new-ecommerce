<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        // Meta Tags
        $this->migrator->add('seo.meta_title', '');
        $this->migrator->add('seo.meta_description', '');
        $this->migrator->add('seo.meta_keywords', null);
        $this->migrator->add('seo.canonical_url', null);

        // Open Graph
        $this->migrator->add('seo.og_title', null);
        $this->migrator->add('seo.og_description', null);
        $this->migrator->add('seo.og_image', null);

        // Analytics & Verification
        $this->migrator->add('seo.google_analytics_id', null);
        $this->migrator->add('seo.google_tag_manager_id', null);
        $this->migrator->add('seo.google_site_verification', null);

        // Indexing
        $this->migrator->add('seo.indexing_enabled', true);
    }
};
