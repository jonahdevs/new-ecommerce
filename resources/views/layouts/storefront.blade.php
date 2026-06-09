@php
    $branding = app(\App\Settings\BrandingSettings::class);
    $analytics = app(\App\Settings\AnalyticsSettings::class);
    $legal = app(\App\Settings\LegalSettings::class);
    $storeName = $branding->store_name ?: config('app.name', 'Sheffield');
    $headerLogo = $branding->logo_path
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($branding->logo_path)
        : '/logo.png';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white text-ink antialiased">
    @if (filled($analytics->gtm_id))
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $analytics->gtm_id }}"
                height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    @endif

    {{-- Rows 1 & 2 pin together as a single sticky block --}}
    <div class="sticky top-0 z-40 bg-white">
        {{-- Row 1 — Promo banner --}}
        @include('partials.storefront.promo-banner')

        {{-- Row 2 — Logo + search + nav + actions --}}
        <header style="background-image: url('/images/navbar-bg.webp'); background-size: cover; background-position: center;">
            <div class="shell flex h-18 items-center gap-6">
                <a href="{{ route('home') }}" class="flex shrink-0 items-center" wire:navigate aria-label="{{ $storeName }} — Home">
                    <img src="{{ $headerLogo }}" alt="{{ $storeName }}" class="h-10 w-auto" />
                </a>

                <livewire:storefront.search-dropdown />

                <nav class="hidden items-center gap-6 text-sm font-semibold text-zinc-900 lg:flex">
                    @foreach ([
                        ['label' => 'Shop',          'route' => 'catalog',        'match' => 'catalog*'],
                        ['label' => 'Request quote', 'route' => 'quote.request',  'match' => 'quote.*'],
                        ['label' => 'Contact',       'route' => 'contact',        'match' => 'contact*'],
                    ] as $link)
                        <a href="{{ route($link['route']) }}" wire:navigate
                           @class([
                               'transition-colors',
                               'text-brand-500'                    => request()->routeIs($link['match']),
                               'text-zinc-900 hover:text-brand-500' => ! request()->routeIs($link['match']),
                           ])>
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </nav>

                <div class="ml-auto flex items-center gap-1">
                    {{-- Each indicator is its own Livewire SFC so it re-renders on
                         events dispatched from page components (see InteractsWithStorefront). --}}
                    <livewire:storefront.compare-indicator />
                    <livewire:storefront.wishlist-indicator />

                    @include('partials.storefront.user-dropdown')

                    <livewire:storefront.cart-indicator />
                </div>
            </div>
        </header>
    </div>

    {{-- Row 3 — Category navigation --}}
    @include('partials.storefront.category-nav')

    <main>
        {{ $slot }}
    </main>

    <livewire:storefront.newsletter-signup />

    @include('partials.storefront.footer')

    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist

    @if ($legal->cookie_consent_enabled)
        @include('partials.storefront.cookie-banner')
    @endif

    <script>
        document.addEventListener('keydown', e => {
            if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                document.querySelector('input[type="search"]')?.focus();
            }
        });
    </script>

    @fluxScripts
</body>

</html>
