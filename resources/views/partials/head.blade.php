<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

{{-- Warm the font connections early so the webfont CSS/files don't wait on a
     cold TLS handshake. Loaded in parallel with app.css (not chained via @import). --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

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
        $__isAdminPage = request()->is('admin/*') || request()->is('admin');
        $__formattedTitle = (! $__isAdminPage && filled($seo->meta_title_pattern))
            ? str_replace(['{page}', '{site}'], [$title, $branding->store_name], $seo->meta_title_pattern)
            : $title;
        \Artesaos\SEOTools\Facades\SEOMeta::setTitle($__formattedTitle, false);
        \Artesaos\SEOTools\Facades\OpenGraph::setTitle($__formattedTitle);
        \Artesaos\SEOTools\Facades\TwitterCard::setTitle($__formattedTitle);
        \Artesaos\SEOTools\Facades\JsonLdMulti::setTitle($__formattedTitle);
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

<link rel="stylesheet"
    href="https://fonts.googleapis.com/css2?family=Newsreader:ital,opsz,wght@0,6..72,300..600;1,6..72,300..600&family=Public+Sans:ital,wght@0,300..700;1,300..700&family=JetBrains+Mono:wght@400;500&display=swap">

@vite(['resources/css/app.css', 'resources/js/app.js'])

{{-- Dark mode is staff-only. @fluxAppearance (which applies the `.dark` class from
     localStorage) is intentionally NOT included here so it can't leak onto the
     storefront, customer, or auth pages. The staff layout adds it on its own. --}}
