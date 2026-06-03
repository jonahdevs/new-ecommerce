<?php

namespace App\Http\Middleware;

use App\Settings\BrandingSettings;
use App\Settings\SeoSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Apply store-wide {@see SeoSettings} to the SEOTools config before any
 * controller or Livewire component constructs the SEOMeta singleton, so the
 * default meta description and title pattern act as fallbacks when a page
 * sets none.
 */
class ConfigureSeo
{
    public function __construct(
        private SeoSettings $seo,
        private BrandingSettings $branding,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (filled($this->seo->default_meta_description)) {
            config([
                'seotools.meta.defaults.description' => $this->seo->default_meta_description,
                'seotools.opengraph.defaults.description' => $this->seo->default_meta_description,
            ]);
        }

        if (filled($this->branding->store_name)) {
            config([
                'seotools.meta.defaults.title' => $this->branding->store_name,
                'seotools.opengraph.defaults.title' => $this->branding->store_name,
                'seotools.json-ld.defaults.title' => $this->branding->store_name,
            ]);
        }

        if (filled($this->seo->meta_title_pattern)) {
            // Extract the separator from the pattern (e.g. "{page} | {site}" → " | ").
            $separator = str_replace(['{page}', '{site}'], '', $this->seo->meta_title_pattern);
            config(['seotools.meta.defaults.separator' => $separator]);
        }

        return $next($request);
    }
}
