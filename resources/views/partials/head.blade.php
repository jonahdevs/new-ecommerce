<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

{{-- SEO Meta Tags --}}
{!! SEO::generate() !!}

{{-- Fallback title if SEO not set --}}
@if (!View::hasSection('seo'))
    <title>{{ isset($title) ? $title . ' | ' : '' }}{{ config('app.name') }}</title>
@endif

{{-- <link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png"> --}}

<link rel="icon" type="image/png" href="/favicon.png">
{{-- Canonical is set per-page via SEOMeta::setCanonical() and output by SEO::generate() above --}}
<link rel="preconnect" href="https://fonts.bunny.net">

<meta name="color-scheme" content="light only">

{{-- <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" /> --}}

{{-- date picker --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

{{-- Easy MDE --}}
<link rel="stylesheet" href="https://unpkg.com/easymde/dist/easymde.min.css">
<script src="https://unpkg.com/easymde/dist/easymde.min.js"></script>

<script src="https://cdn.tiny.cloud/1/c2ybu5wz30jxny8183sd8d7ne9vuxr5obaqz65xd06ohkqj8/tinymce/6/tinymce.min.js"
    referrerpolicy="origin"></script>

{{-- Stripe JS --}}
<script src="https://js.stripe.com/v3/"></script>

{{-- Swiper js --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.css" />
<script src="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.js"></script>

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
