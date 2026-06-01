<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

{{-- Analytics (GA4 / GTM / Meta Pixel) — loaded as high in <head> as possible. --}}
@include('partials.storefront.analytics')

{{-- Bridge per-request data into SEOTools so a single page-level title flows
     through to <title>, OpenGraph, Twitter and JSON-LD; also resolves the current
     absolute URL and default OG image, and applies store-wide SEO settings. --}}
@php
    $seo = app(\App\Settings\SeoSettings::class);
    $social = app(\App\Settings\SocialSettings::class);
    $branding = app(\App\Settings\BrandingSettings::class);

    if (filled($title ?? null)) {
        // Second arg `false` stops SEOMeta from appending the config default
        // title — pages already include the " — Sheffield" suffix themselves.
        \Artesaos\SEOTools\Facades\SEOMeta::setTitle($title, false);
        \Artesaos\SEOTools\Facades\OpenGraph::setTitle($title);
        \Artesaos\SEOTools\Facades\TwitterCard::setTitle($title);
        \Artesaos\SEOTools\Facades\JsonLdMulti::setTitle($title);
    }

    $__seoCurrentUrl = url()->current();
    \Artesaos\SEOTools\Facades\SEOMeta::setCanonical($__seoCurrentUrl);
    \Artesaos\SEOTools\Facades\OpenGraph::setUrl($__seoCurrentUrl);
    \Artesaos\SEOTools\Facades\JsonLdMulti::setUrl($__seoCurrentUrl);

    // Site-wide noindex when search-engine indexing is turned off in settings.
    if (! $seo->index_site) {
        \Artesaos\SEOTools\Facades\SEOMeta::setRobots('noindex, nofollow');
    }

    // Twitter card site handle.
    if (filled($social->twitter_handle)) {
        \Artesaos\SEOTools\Facades\TwitterCard::setSite('@'.ltrim($social->twitter_handle, '@'));
    }

    // Always add an absolute fallback OG image — the admin-set default, else the
    // bundled asset. Pages that set their own (in mount()) are listed first.
    $__ogImage = $social->og_image_path
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($social->og_image_path)
        : url('/images/og-default.jpg');
    \Artesaos\SEOTools\Facades\OpenGraph::addImage($__ogImage, ['width' => 1200, 'height' => 630]);
@endphp

{{-- Render: title, description, canonical, robots, OpenGraph, Twitter, JSON-LD.
     Pages enrich defaults via SEOMeta/OpenGraph/JsonLdMulti in mount() or rendering();
     fallbacks come from config/seotools.php. --}}
{!! SEO::generate() !!}

@if ($branding->favicon_path)
    <link rel="icon" href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($branding->favicon_path) }}">
@else
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.png" type="image/png">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
@endif

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
