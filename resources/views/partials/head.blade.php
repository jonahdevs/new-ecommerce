<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

{{-- Bridge per-request data into SEOTools so a single page-level title flows
     through to <title>, OpenGraph, Twitter and JSON-LD; also resolves the current
     absolute URL and default OG image (config can't call url() helpers). --}}
@php
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

    // Always add an absolute fallback OG image. Pages that set their own
    // (via OpenGraph::addImage() in mount()) get listed first; this just
    // ensures we never ship a page without at least one og:image.
    \Artesaos\SEOTools\Facades\OpenGraph::addImage(url('/images/og-default.jpg'), ['width' => 1200, 'height' => 630]);
@endphp

{{-- Render: title, description, canonical, robots, OpenGraph, Twitter, JSON-LD.
     Pages enrich defaults via SEOMeta/OpenGraph/JsonLdMulti in mount() or rendering();
     fallbacks come from config/seotools.php. --}}
{!! SEO::generate() !!}

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.png" type="image/png">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
