<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // ── SEO ────────────────────────────────────────────────────────────
        $this->migrator->add('seo.meta_title_pattern', '{page} | {site}');
        $this->migrator->add('seo.default_meta_description', '');
        $this->migrator->add('seo.index_site', true);
        $this->migrator->add('seo.generate_sitemap', true);

        // ── Social & sharing ───────────────────────────────────────────────
        $this->migrator->add('social.og_image_path', null);
        $this->migrator->add('social.twitter_handle', '');
        $this->migrator->add('social.facebook_url', '');
        $this->migrator->add('social.instagram_url', '');
        $this->migrator->add('social.x_url', '');
        $this->migrator->add('social.linkedin_url', '');
        $this->migrator->add('social.youtube_url', '');
        $this->migrator->add('social.whatsapp_number', '');

        // ── Analytics ──────────────────────────────────────────────────────
        $this->migrator->add('analytics.ga4_id', '');
        $this->migrator->add('analytics.gtm_id', '');
        $this->migrator->add('analytics.meta_pixel_id', '');

        // ── Legal ──────────────────────────────────────────────────────────
        // Policy *content* lives in CMS Pages (App\Models\Page); this is just the
        // cookie-banner behaviour toggle.
        $this->migrator->add('legal.cookie_consent_enabled', false);
    }
};
