<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SeoSettings extends Settings
{
    public string $meta_title_pattern;

    public string $default_meta_description;

    public bool $index_site;

    public bool $generate_sitemap;

    public static function group(): string
    {
        return 'seo';
    }
}
