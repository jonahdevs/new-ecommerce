<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // ==================================================
        // SEO
        // ==================================================
        $this->migrator->add('seo.meta_title_pattern', '{page} | {site}');
        $this->migrator->add('seo.default_meta_description', 'Sheffield Africa — East Africa\'s leading supplier of commercial kitchen, cold room, laundry and healthcare equipment since 2003. Sales, installation, service and spares across Kenya, Uganda and Rwanda.');
        $this->migrator->add('seo.index_site', true);
        $this->migrator->add('seo.generate_sitemap', true);

        // ==================================================
        // SOCIAL & SHARING
        // ==================================================
        $this->migrator->add('social.og_image_path', null);
        $this->migrator->add('social.twitter_handle', '');
        $this->migrator->add('social.facebook_url', '');
        $this->migrator->add('social.instagram_url', '');
        $this->migrator->add('social.x_url', '');
        $this->migrator->add('social.linkedin_url', '');
        $this->migrator->add('social.youtube_url', '');
        $this->migrator->add('social.whatsapp_number', '+254114838130');
        $this->migrator->add('social.whatsapp_order_enabled', false);

        // ==================================================
        // ANALYTICS
        // ==================================================
        $this->migrator->add('analytics.ga4_id', '');
        $this->migrator->add('analytics.gtm_id', '');
        $this->migrator->add('analytics.meta_pixel_id', '');

        // ==================================================
        // LEGAL
        // ==================================================
        // Policy *content* lives in CMS Pages (App\Models\Page); this is just the
        // cookie-banner behaviour toggle.
        $this->migrator->add('legal.cookie_consent_enabled', false);
    }
};
