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
        <header class="border-t-2 border-brand-blue-700 border-b border-zinc-200">
            <div class="shell flex h-18 items-center gap-6">
                <a href="{{ route('home') }}" class="flex shrink-0 items-center" wire:navigate aria-label="{{ config('app.name', 'Sheffield') }} — Home">
                    <img src="/logo.png" alt="{{ config('app.name', 'Sheffield') }}" class="h-10 w-auto" />
                </a>

                <flux:input icon="search" placeholder="Search by SKU, brand or product..." class="flex-1 max-w-xl" />

                <nav class="hidden items-center gap-6 text-sm text-zinc-600 lg:flex">
                    <a href="{{ route('catalog') }}" class="flex items-center gap-1 hover:text-zinc-900" wire:navigate>
                        Shop
                        <flux:icon.chevron-down variant="micro" class="size-3.5" />
                    </a>
                    <a href="{{ route('catalog') }}" class="hover:text-zinc-900" wire:navigate>Request quote</a>
                    <a href="#" class="hover:text-zinc-900">Service</a>
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

    @include('partials.storefront.footer')

    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist

    @fluxScripts
</body>

</html>
