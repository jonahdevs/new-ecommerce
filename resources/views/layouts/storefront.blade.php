<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
    {{-- Rows 1 & 2 pin together as a single sticky block --}}
    <div class="sticky top-0 z-40 bg-white dark:bg-zinc-800">
        {{-- Row 1 — Promo banner --}}
        @include('partials.storefront.promo-banner')

        {{-- Row 2 — Logo + search + cart + user --}}
        <header class="border-b border-zinc-200 dark:border-zinc-800">
            <div class="mx-auto flex h-16 max-w-7xl items-center gap-4 px-6">
                <a href="{{ route('home') }}" class="flex items-center gap-2" wire:navigate>
                    <x-app-logo-icon class="size-7 fill-current text-black dark:text-white" />
                    <span class="text-base font-semibold">{{ config('app.name', 'Laravel') }}</span>
                </a>

                <flux:spacer />

                <flux:input icon="magnifying-glass" placeholder="Search products..." class="hidden w-full max-w-md md:block" />

                <flux:spacer />

                <flux:button icon="shopping-cart" href="#" variant="ghost" aria-label="Cart" />

                @auth
                    <flux:dropdown position="bottom" align="end">
                        <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />

                        <flux:menu>
                            <flux:menu.item href="#" icon="user" wire:navigate>{{ __('My Account') }}</flux:menu.item>
                            <flux:menu.item href="#" icon="shopping-bag" wire:navigate>{{ __('Orders') }}</flux:menu.item>
                            <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}
                            </flux:menu.item>

                            <flux:menu.separator />

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                                    {{ __('Log out') }}
                                </flux:menu.item>
                            </form>
                        </flux:menu>
                    </flux:dropdown>
                @else
                    <flux:button :href="route('login')" variant="ghost" wire:navigate>{{ __('Sign in') }}</flux:button>
                    <flux:button :href="route('register')" variant="primary" wire:navigate>{{ __('Sign up') }}</flux:button>
                @endauth
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
