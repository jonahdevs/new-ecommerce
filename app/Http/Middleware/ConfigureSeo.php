<?php

namespace App\Http\Middleware;

use App\Settings\SeoSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Apply store-wide {@see SeoSettings} to the SEOTools config before any
 * controller or Livewire component constructs the SEOMeta singleton, so the
 * default meta description acts as the fallback when a page sets none.
 */
class ConfigureSeo
{
    public function __construct(private SeoSettings $seo) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (filled($this->seo->default_meta_description)) {
            config([
                'seotools.meta.defaults.description' => $this->seo->default_meta_description,
                'seotools.opengraph.defaults.description' => $this->seo->default_meta_description,
            ]);
        }

        return $next($request);
    }
}
