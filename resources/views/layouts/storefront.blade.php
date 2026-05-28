<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white text-ink antialiased">
    {{-- Rows 1 & 2 pin together as a single sticky block --}}
    <div class="sticky top-0 z-40 bg-white">
        {{-- Row 1 — Promo banner --}}
        @include('partials.storefront.promo-banner')

        {{-- Row 2 — Logo + search + nav + actions --}}
        <header style="background-image: url('/images/navbar-bg.webp'); background-size: cover; background-position: center;">
            <div class="shell flex h-18 items-center gap-6">
                <a href="{{ route('home') }}" class="flex shrink-0 items-center" wire:navigate aria-label="{{ config('app.name', 'Sheffield') }} — Home">
                    <img src="/logo.png" alt="{{ config('app.name', 'Sheffield') }}" class="h-10 w-auto" />
                </a>

                <livewire:storefront.search-dropdown />

                <nav class="hidden items-center gap-6 text-sm font-semibold text-zinc-900 lg:flex">
                    <a href="{{ route('catalog') }}" class="flex items-center gap-1 hover:text-brand-500 transition-colors" wire:navigate>
                        Shop
                        <flux:icon.chevron-down variant="micro" class="size-3.5" />
                    </a>
                    <a href="{{ route('quote.request') }}" class="hover:text-brand-500 transition-colors" wire:navigate>Request quote</a>
                    <a href="{{ route('contact') }}" class="hover:text-brand-500 transition-colors" wire:navigate>Contact</a>
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
