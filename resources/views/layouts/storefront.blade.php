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
    <div class="sticky top-0 z-40 bg-white" x-data="{ drawerOpen: false }"
        x-effect="document.body.style.overflow = drawerOpen ? 'hidden' : ''"
        x-init="$nextTick(() => { const sync = () => document.documentElement.style.setProperty('--sticky-header-h', $el.offsetHeight + 'px'); sync(); window.addEventListener('resize', sync); new ResizeObserver(sync).observe($el); })">
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
                    <img src="{{ $headerLogo }}" alt="{{ $storeName }}" class="h-10 w-auto sm:h-12" />
                </a>

                {{-- Search — desktop only (inline flex-1); mobile opens a full-screen overlay
                     via the toggle button below, handled inside the search-dropdown component. --}}
                <div class="hidden lg:order-2 lg:flex lg:flex-1 lg:max-w-xl">
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
                    {{-- Search toggle — mobile/tablet only. Opens the full-screen overlay inside search-dropdown. --}}
                    <button type="button" aria-label="Search" @click="$dispatch('open-mobile-search')"
                        class="inline-flex size-11 shrink-0 items-center justify-center rounded-md text-zinc-900 transition hover:bg-black/5 lg:hidden">
                        <flux:icon.magnifying-glass variant="outline" class="size-6" />
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
                        class="inline-flex size-11 items-center justify-center rounded-md text-ink-2 transition hover:bg-surface-sunken [&>svg]:transition [&>svg]:duration-300 hover:[&>svg]:rotate-90">
                        <flux:icon.x-mark variant="outline" class="size-6" />
                    </button>
                </div>

                {{-- Scrollable body --}}
                <div class="scrollbar-thin flex-1 overflow-y-auto">

                    {{-- My Account --}}
                    <section class="border-b border-zinc-100">
                        <p class="px-4 pb-2 pt-4 text-[11px] font-semibold uppercase tracking-wider text-ink-3">My Account</p>
                        @auth
                            <nav class="pb-2">
                                <a href="{{ route('account.dashboard') }}" wire:navigate @click="drawerOpen = false"
                                    class="flex items-center gap-3 px-4 py-2.5 text-[14px] text-ink transition hover:text-brand-500">
                                    <flux:icon.squares-2x2 variant="micro" class="size-4 shrink-0 text-ink-3" /> Account dashboard
                                </a>
                                <a href="{{ route('account.orders.index') }}" wire:navigate @click="drawerOpen = false"
                                    class="flex items-center gap-3 px-4 py-2.5 text-[14px] text-ink transition hover:text-brand-500">
                                    <flux:icon.document-text variant="micro" class="size-4 shrink-0 text-ink-3" /> My Orders
                                </a>
                                <a href="{{ route('account.quotes.index') }}" wire:navigate @click="drawerOpen = false"
                                    class="flex items-center gap-3 px-4 py-2.5 text-[14px] text-ink transition hover:text-brand-500">
                                    <flux:icon.clipboard-document-list variant="micro" class="size-4 shrink-0 text-ink-3" /> My Quotes
                                </a>
                                <a href="{{ route('wishlist') }}" wire:navigate @click="drawerOpen = false"
                                    class="flex items-center gap-3 px-4 py-2.5 text-[14px] text-ink transition hover:text-brand-500">
                                    <flux:icon.heart variant="micro" class="size-4 shrink-0 text-ink-3" /> Wishlist
                                </a>
                                <a href="{{ route('compare') }}" wire:navigate @click="drawerOpen = false"
                                    class="flex items-center gap-3 px-4 py-2.5 text-[14px] text-ink transition hover:text-brand-500">
                                    <flux:icon.scale variant="micro" class="size-4 shrink-0 text-ink-3" /> Compare
                                </a>
                            </nav>
                        @else
                            <nav class="pb-2">
                                <a href="{{ route('login') }}" wire:navigate @click="drawerOpen = false"
                                    class="flex items-center gap-3 px-4 py-2.5 text-[14px] text-ink transition hover:text-brand-500">
                                    <flux:icon.user variant="micro" class="size-4 shrink-0 text-ink-3" /> Sign in
                                </a>
                                <a href="{{ route('register') }}" wire:navigate @click="drawerOpen = false"
                                    class="flex items-center gap-3 px-4 py-2.5 text-[14px] text-ink transition hover:text-brand-500">
                                    <flux:icon.user-plus variant="micro" class="size-4 shrink-0 text-ink-3" /> Create account
                                </a>
                                <a href="{{ route('wishlist') }}" wire:navigate @click="drawerOpen = false"
                                    class="flex items-center gap-3 px-4 py-2.5 text-[14px] text-ink transition hover:text-brand-500">
                                    <flux:icon.heart variant="micro" class="size-4 shrink-0 text-ink-3" /> Wishlist
                                </a>
                                <a href="{{ route('compare') }}" wire:navigate @click="drawerOpen = false"
                                    class="flex items-center gap-3 px-4 py-2.5 text-[14px] text-ink transition hover:text-brand-500">
                                    <flux:icon.scale variant="micro" class="size-4 shrink-0 text-ink-3" /> Compare
                                </a>
                            </nav>
                        @endauth
                    </section>

                    {{-- Our Categories --}}
                    <section class="border-b border-zinc-100">
                        <div class="flex items-center justify-between px-4 pb-2 pt-4">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-ink-3">Our Categories</p>
                            <a href="{{ route('catalog') }}" wire:navigate @click="drawerOpen = false"
                                class="text-[11px] font-medium text-brand-500 underline transition hover:text-brand-600">View all</a>
                        </div>
                        <nav class="pb-2">
                            @foreach ($navCategories as $category)
                                <a href="{{ route('category.show', $category) }}" wire:navigate @click="drawerOpen = false"
                                    class="flex items-center gap-3 px-4 py-2.5 text-[14px] text-ink transition hover:text-brand-500">
                                    @if ($category->icon_svg)
                                        <span class="grid size-4 shrink-0 place-items-center text-ink-3 [&>svg]:size-full">
                                            {!! $category->icon_svg !!}
                                        </span>
                                    @elseif ($category->icon_image_url)
                                        <img src="{{ $category->icon_image_url }}" alt=""
                                            class="size-4 shrink-0 object-contain opacity-60" loading="lazy" />
                                    @else
                                        <flux:icon.tag variant="micro" class="size-4 shrink-0 text-ink-3" />
                                    @endif
                                    <span class="truncate">{{ $category->name }}</span>
                                </a>
                            @endforeach
                        </nav>
                    </section>

                    {{-- Help Centre --}}
                    <section>
                        <p class="px-4 pb-1.5 pt-4 text-[11px] font-semibold uppercase tracking-wider text-ink-3">Help Centre</p>
                        <nav class="pb-4">
                            <a href="{{ route('contact') }}" wire:navigate @click="drawerOpen = false"
                                class="flex items-center gap-3 px-4 py-2 text-[13px] text-ink transition hover:text-brand-500">
                                <flux:icon.chat-bubble-left-right variant="micro" class="size-4 shrink-0 text-ink-3" /> Contact us
                            </a>
                            <a href="{{ route('quote.request') }}" wire:navigate @click="drawerOpen = false"
                                class="flex items-center gap-3 px-4 py-2 text-[13px] text-ink transition hover:text-brand-500">
                                <flux:icon.document-plus variant="micro" class="size-4 shrink-0 text-ink-3" /> Request a quote
                            </a>
                            <a href="tel:+254713777111"
                                class="flex items-center gap-3 px-4 py-2 text-[13px] text-ink transition hover:text-brand-500">
                                <flux:icon.phone variant="micro" class="size-4 shrink-0 text-ink-3" /> +254&nbsp;713&nbsp;777&nbsp;111
                            </a>
                        </nav>
                    </section>
                </div>
            </div>
        </div>
    </div>

    {{-- Row 3 — Category navigation --}}
    @include('partials.storefront.category-nav')

    {{-- Demo-site notice — hazard-striped banner.
         Sits after the category bar at rest; on scroll it sticks just below the
         sticky logo/search header (offset measured into --sticky-header-h). --}}
    <div class="sticky z-30 bg-[#f7d000] text-center" style="top: var(--sticky-header-h, 108px)">
        <div class="h-1" style="background-image: repeating-linear-gradient(-45deg, #1a1a1a 0, #1a1a1a 6px, #f7d000 6px, #f7d000 12px);"></div>
        <p class="px-4 py-2 text-[13px] font-bold leading-snug tracking-wide text-zinc-900">
            This is a demo site - products, pricing and content are for demonstration purposes only.
        </p>
        <div class="h-1" style="background-image: repeating-linear-gradient(-45deg, #1a1a1a 0, #1a1a1a 6px, #f7d000 6px, #f7d000 12px);"></div>
    </div>

    <main>
        {{ $slot }}
    </main>

    <livewire:storefront.newsletter-signup />

    @include('partials.storefront.footer')

    <livewire:storefront.chat-widget defer />

    @auth
        <livewire:concurrent-session-guard />
    @endauth

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
