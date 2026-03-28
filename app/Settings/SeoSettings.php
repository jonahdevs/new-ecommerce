<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SeoSettings extends Settings
{
    public ?string $meta_title;           // default site-wide meta title
    public ?string $meta_description;     // default site-wide meta description
    public ?string $meta_keywords;
    public ?string $og_image;             // default Open Graph image
    public bool $robots_indexing;      // true = allow indexing
    public bool $sitemap_enabled;
    public ?string $google_site_verification;
    public ?string $canonical_url;        // base canonical URL

    public static function group(): string
    {
        return 'seo';
    }
}
