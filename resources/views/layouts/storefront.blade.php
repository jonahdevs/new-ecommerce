<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-zinc-900">
        <flux:header sticky class="border-b border-zinc-200 dark:border-zinc-800">
            <a href="{{ route('home') }}" class="flex items-center gap-2" wire:navigate>
                <x-app-logo-icon class="size-7 fill-current text-black dark:text-white" />
                <span class="text-base font-semibold">{{ config('app.name', 'Laravel') }}</span>
            </a>

            <flux:navbar class="ms-6 hidden lg:flex">
                {{-- TODO: replace with dynamic top-level categories (navbar placement) --}}
                <flux:navbar.item href="#" wire:navigate>Shop</flux:navbar.item>
                <flux:navbar.item href="#" wire:navigate>Brands</flux:navbar.item>
                <flux:navbar.item href="#" wire:navigate>Contact</flux:navbar.item>
            </flux:navbar>

            <flux:spacer />

            <flux:input icon="magnifying-glass" placeholder="Search products..." class="hidden max-w-xs md:block" />

            {{-- Cart icon (count badge wired once cart livewire component lands) --}}
            <flux:button icon="shopping-cart" href="#" variant="ghost" aria-label="Cart" />

            @auth
                <flux:dropdown position="bottom" align="end">
                    <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />

                    <flux:menu>
                        <flux:menu.item href="#" icon="user" wire:navigate>{{ __('My Account') }}</flux:menu.item>
                        <flux:menu.item href="#" icon="shopping-bag" wire:navigate>{{ __('Orders') }}</flux:menu.item>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>

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
        </flux:header>

        <flux:main>
            {{ $slot }}
        </flux:main>

        <footer class="mt-12 border-t border-zinc-200 dark:border-zinc-800">
            <div class="mx-auto max-w-7xl px-6 py-12">
                <p class="text-sm text-zinc-500">&copy; {{ date('Y') }} {{ config('app.name', 'Laravel') }}. All rights reserved.</p>
            </div>
        </footer>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
