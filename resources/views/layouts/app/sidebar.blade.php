<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
    @include('partials.admin.sidebar')

    {{-- Top navbar — always visible, contains toolbar actions --}}
    <flux:header class="border-b border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 print:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        {{-- Page breadcrumbs (pushed by each page) --}}
        <div class="hidden min-w-0 lg:block">
            @stack('breadcrumbs')
        </div>

        <flux:spacer />

        {{-- Notification bell (requires an authenticated user) --}}
        @auth
            <livewire:admin.notification-bell />
        @endauth

        {{-- Appearance toggle: light → dark → system → light --}}
        <flux:tooltip content="Appearance" position="bottom">
            <flux:button variant="ghost" square x-data aria-label="Toggle color scheme"
                x-on:click="$flux.appearance = $flux.appearance === 'light' ? 'dark' : ($flux.appearance === 'dark' ? 'system' : 'light')">
                <flux:icon.sun x-show="$flux.appearance === 'light'" variant="mini" />
                <flux:icon.moon x-show="$flux.appearance === 'dark'" variant="mini" />
                <flux:icon.computer-desktop x-show="$flux.appearance === 'system'" variant="mini" />
            </flux:button>
        </flux:tooltip>

        {{-- Account dropdown — only when signed in (e.g. an expired session landing
             on an error page has no user, so fall back to a sign-in link). --}}
        @auth
            <flux:dropdown position="bottom" align="end">
                <flux:profile circle :initials="auth()->user()->initials()" icon-trailing="chevron-down" />

                <flux:menu>
                    <div class="flex items-center gap-3 px-3 py-2">
                        <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" />
                        <div class="min-w-0">
                            <div class="truncate text-sm font-semibold dark:text-white">{{ auth()->user()->name }}</div>
                            <div class="truncate text-xs text-zinc-500">{{ auth()->user()->email }}</div>
                        </div>
                    </div>

                    <flux:menu.separator />

                    <flux:menu.item :href="auth()->user()->hasRole(['admin', 'staff']) ? route('admin.settings.general', ['section' => 'profile']) : route('profile.edit')" icon="cog" wire:navigate>Settings</flux:menu.item>
                    <flux:menu.item :href="route('home')" icon="arrow-top-right-on-square" target="_blank">View storefront</flux:menu.item>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                                        class="w-full cursor-pointer" data-test="logout-button">
                            Log out
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        @else
            <flux:button :href="route('login')" icon="arrow-right-end-on-rectangle" size="sm" variant="ghost" wire:navigate>
                Log in
            </flux:button>
        @endauth
    </flux:header>

    {{ $slot }}

    @auth
        <livewire:concurrent-session-guard />
    @endauth

    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist

    @fluxScripts
</body>

</html>
