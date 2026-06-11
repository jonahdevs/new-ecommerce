@php
    $branding = app(\App\Settings\BrandingSettings::class);
    $analytics = app(\App\Settings\AnalyticsSettings::class);
    $legal = app(\App\Settings\LegalSettings::class);
    $storeName = $branding->store_name ?: config('app.name', 'Sheffield');
    $headerLogo = $branding->logo_path
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($branding->logo_path)
        : '/logo.png';

    // Shared by the desktop category bar (category-nav partial) and the mobile drawer.
    // 12 = max grid capacity (6 cols × 2 rows at lg).
    $navCategories = \App\Models\CategoryPlacement::query()
        ->with('category')
        ->where('location', \App\Enums\CategorySection::NAVBAR)
        ->where('status', \App\Enums\CategoryStatus::ACTIVE)
        ->orderBy('sort_order')
        ->take(12)
        ->get()
        ->pluck('category')
        ->filter();

    $primaryNav = [
        ['label' => 'Shop', 'route' => 'catalog', 'match' => 'catalog*'],
        ['label' => 'Request quote', 'route' => 'quote.request', 'match' => 'quote.*'],
        ['label' => 'Contact', 'route' => 'contact', 'match' => 'contact*'],
    ];
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

    {{-- Rows 1 & 2 pin together as a single sticky block. Alpine drawer state lives here. --}}
    <div class="sticky top-0 z-40 bg-white" x-data="{ drawerOpen: false, searchOpen: false }"
        x-effect="document.body.style.overflow = drawerOpen ? 'hidden' : ''">
        {{-- Row 1 — Promo banner --}}
        @include('partials.storefront.promo-banner')

        {{-- Row 2 — Logo + search + nav + actions.
             Mobile: single line (logo bar). Search collapses behind a toggle icon and only
             expands to its own full-width line when searchOpen is true.
             lg+: single row with search always inline (logo · search · nav · actions). --}}
        <header style="background-image: url('/images/navbar-bg.webp'); background-size: cover; background-position: center;">
            <div class="shell relative flex flex-wrap items-center gap-x-4 gap-y-3 py-3 lg:h-18 lg:flex-nowrap lg:gap-6 lg:py-0">
                {{-- Hamburger — mobile/tablet only --}}
                <button type="button" @click="drawerOpen = true" aria-label="Open menu"
                    class="order-1 inline-flex size-11 shrink-0 items-center justify-center rounded-md text-zinc-900 transition hover:bg-black/5 lg:hidden">
                    <flux:icon.bars-3 variant="outline" class="size-6" />
                </button>

                <a href="{{ route('home') }}" class="order-2 flex shrink-0 items-center lg:order-1" wire:navigate aria-label="{{ $storeName }} — Home">
                    <img src="{{ $headerLogo }}" alt="{{ $storeName }}" class="h-9 w-auto sm:h-10" />
                </a>

                {{-- Search — on mobile it drops down as an absolute overlay below the bar (no height
                     change to the header); on lg+ it sits inline (flex-1) and the toggle state is ignored. --}}
                <div class="absolute inset-x-0 top-full z-50 border-t border-zinc-200 bg-white p-3 shadow-lg
                            lg:static lg:order-2 lg:inset-auto lg:top-auto lg:z-auto lg:!block lg:w-auto lg:max-w-xl lg:flex-1 lg:border-0 lg:bg-transparent lg:p-0 lg:shadow-none"
                    x-bind:class="searchOpen ? 'block' : 'hidden'"
                    x-on:keydown.escape.window="searchOpen = false"
                    x-on:click.outside="if (! $refs.searchToggle?.contains($event.target)) searchOpen = false">
                    <livewire:storefront.search-dropdown />
                </div>

                <nav class="order-3 hidden items-center gap-6 text-sm font-semibold text-zinc-900 lg:order-3 lg:flex">
                    @foreach ($primaryNav as $link)
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

                <div class="order-3 ml-auto flex items-center gap-1 lg:order-4">
                    {{-- Search toggle — mobile/tablet only. Expands the full-width search line below. --}}
                    <button type="button" aria-label="Toggle search" x-ref="searchToggle" x-bind:aria-expanded="searchOpen"
                        @click="searchOpen = !searchOpen; if (searchOpen) $nextTick(() => $root.querySelector('input[type=search]')?.focus())"
                        class="inline-flex size-11 shrink-0 items-center justify-center rounded-md text-zinc-900 transition hover:bg-black/5 lg:hidden">
                        <flux:icon.magnifying-glass variant="outline" class="size-6" x-show="!searchOpen" />
                        <flux:icon.x-mark variant="outline" class="size-6" x-show="searchOpen" x-cloak />
                    </button>

                    {{-- Each indicator is its own Livewire SFC so it re-renders on
                         events dispatched from page components (see InteractsWithStorefront).
                         Compare + wishlist fold into the drawer on mobile to save the row. --}}
                    <div class="hidden items-center gap-1 sm:flex">
                        <livewire:storefront.compare-indicator />
                        <livewire:storefront.wishlist-indicator />
                    </div>

                    @include('partials.storefront.user-dropdown')

                    <livewire:storefront.cart-indicator />
                </div>
            </div>
        </header>

        {{-- Mobile navigation drawer (left slide-over) --}}
        <div x-show="drawerOpen" x-cloak class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true"
            x-on:keydown.escape.window="drawerOpen = false">
            {{-- Backdrop --}}
            <div x-show="drawerOpen" x-transition.opacity.duration.200ms @click="drawerOpen = false"
                class="absolute inset-0 bg-black/50"></div>

            {{-- Panel --}}
            <div x-show="drawerOpen"
                x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
                x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
                class="absolute inset-y-0 left-0 flex w-[88%] max-w-sm flex-col bg-white shadow-xl">

                {{-- Drawer header --}}
                <div class="flex h-16 shrink-0 items-center justify-between border-b border-zinc-200 px-4">
                    <a href="{{ route('home') }}" wire:navigate @click="drawerOpen = false" class="flex items-center">
                        <img src="{{ $headerLogo }}" alt="{{ $storeName }}" class="h-9 w-auto" />
                    </a>
                    <button type="button" @click="drawerOpen = false" aria-label="Close menu"
                        class="inline-flex size-11 items-center justify-center rounded-md text-ink-2 transition hover:bg-surface-sunken">
                        <flux:icon.x-mark variant="outline" class="size-6" />
                    </button>
                </div>

                {{-- Scrollable body --}}
                <div class="scrollbar-thin flex-1 overflow-y-auto">
                    {{-- Primary nav --}}
                    <nav class="border-b border-zinc-100 py-2">
                        @foreach ($primaryNav as $link)
                            <a href="{{ route($link['route']) }}" wire:navigate @click="drawerOpen = false"
                               @class([
                                   'flex items-center px-4 py-3 text-[15px] font-semibold transition',
                                   'text-brand-500'                              => request()->routeIs($link['match']),
                                   'text-ink hover:bg-surface-sunken hover:text-brand-500' => ! request()->routeIs($link['match']),
                               ])>
                                {{ $link['label'] }}
                            </a>
                        @endforeach
                    </nav>

                    {{-- Categories live in the dedicated category bar (Browse dropdown + scroller),
                         so they are intentionally not duplicated here. --}}

                    {{-- Quick links folded out of the action bar on mobile --}}
                    <div class="border-t border-zinc-100 py-2 sm:hidden">
                        <a href="{{ route('compare') }}" wire:navigate @click="drawerOpen = false"
                            class="flex items-center gap-3 px-4 py-2.5 text-[14px] text-ink transition hover:bg-surface-sunken">
                            <flux:icon.scale variant="micro" class="size-4 text-ink-3" /> Compare
                        </a>
                        <a href="{{ route('wishlist') }}" wire:navigate @click="drawerOpen = false"
                            class="flex items-center gap-3 px-4 py-2.5 text-[14px] text-ink transition hover:bg-surface-sunken">
                            <flux:icon.heart variant="micro" class="size-4 text-ink-3" /> Wishlist
                        </a>
                    </div>
                </div>

                {{-- Drawer footer --}}
                <div class="shrink-0 border-t border-zinc-200 px-4 py-4">
                    @guest
                        <a href="{{ route('login') }}" wire:navigate @click="drawerOpen = false"
                            class="flex items-center gap-2 text-[14px] font-semibold text-ink hover:text-brand-500">
                            <flux:icon.user variant="micro" class="size-4" /> Trade Login
                        </a>
                    @endguest
                    <div class="mt-3 flex items-center gap-2 text-[13px] text-ink-3">
                        <flux:icon.phone variant="micro" class="size-4" />
                        <a href="tel:+254713777111" class="hover:text-ink">+254&nbsp;713&nbsp;777&nbsp;111</a>
                    </div>
                </div>
            </div>
        </div>
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
