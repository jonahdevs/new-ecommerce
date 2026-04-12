@inject('seoSettings', 'App\Settings\SeoSettings')
@inject('generalSettings', 'App\Settings\GeneralSettings')

<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

{{-- SEO Meta Tags --}}
{!! SEO::generate() !!}

{{-- Fallback title if SEO not set --}}
@if (!View::hasSection('seo'))
    <title>{{ isset($title) ? $title . ' | ' : '' }}{{ $generalSettings->store_name ?: config('app.name') }}</title>
@endif

{{-- Favicon: use settings value if configured, otherwise fall back to /favicon.png --}}
@if ($generalSettings->store_favicon)
    <link rel="icon" type="image/png" href="{{ asset('storage/' . $generalSettings->store_favicon) }}">
@else
    <link rel="icon" type="image/png" href="/favicon.png">
@endif

{{-- Google Search Console site verification --}}
@if ($seoSettings->google_site_verification)
    <meta name="google-site-verification" content="{{ $seoSettings->google_site_verification }}" />
@endif

{{-- Canonical is set per-page via SEOMeta::setCanonical() and output by SEO::generate() above --}}
<link rel="preconnect" href="https://fonts.bunny.net">

<meta name="color-scheme" content="light only">

{{-- Swiper --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.css" />
<script src="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.js" defer></script>

@stack('head-scripts')

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
