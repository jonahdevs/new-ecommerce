<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SeoSettings extends Settings
{
    // Meta Tags
    public string $meta_title;
    public string $meta_description;
    public ?string $meta_keywords;
    public ?string $canonical_url;

    // Open Graph
    public ?string $og_title;
    public ?string $og_description;
    public ?string $og_image;

    // Analytics & Verification
    public ?string $google_analytics_id;
    public ?string $google_tag_manager_id;
    public ?string $google_site_verification;

    // Indexing
    public bool $indexing_enabled;

    public static function group(): string
    {
        return 'seo';
    }
}
