<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
    <flux:sidebar sticky collapsible="mobile"
        class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.header>
            <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
            <flux:sidebar.collapse class="lg:hidden" />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            <flux:sidebar.group :heading="__('Overview')" class="grid">
                <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')"
                    wire:navigate>
                    {{ __('Dashboard') }}
                </flux:sidebar.item>
            </flux:sidebar.group>

            <flux:sidebar.group :heading="__('Catalog')" class="grid">
                <flux:sidebar.item icon="cube" :href="route('admin.products.index')" :current="request()->routeIs('admin.products.*')"
                    wire:navigate>
                    {{ __('Products') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="folder" :href="route('admin.categories.index')" :current="request()->routeIs('admin.categories.*')"
                    wire:navigate>
                    {{ __('Categories') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="tag" :href="route('admin.brands.index')" :current="request()->routeIs('admin.brands.*')"
                    wire:navigate>
                    {{ __('Brands') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="adjustments-horizontal" :href="route('admin.attributes.index')"
                    :current="request()->routeIs('admin.attributes.*')" wire:navigate>
                    {{ __('Attributes') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="hashtag" :href="route('admin.tags.index')"
                    :current="request()->routeIs('admin.tags.*')" wire:navigate>
                    {{ __('Tags') }}
                </flux:sidebar.item>
            </flux:sidebar.group>

            <flux:sidebar.group :heading="__('Sales')" class="grid">
                <flux:sidebar.item icon="document-text" :href="route('admin.quotes.index')" :current="request()->routeIs('admin.quotes.*')"
                    wire:navigate>
                    {{ __('Quotations') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="shopping-cart" :href="route('admin.orders.index')" :current="request()->routeIs('admin.orders.*')"
                    wire:navigate>
                    {{ __('Orders') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="credit-card" :href="route('admin.payments.index')" :current="request()->routeIs('admin.payments.*')"
                    wire:navigate>
                    {{ __('Payments') }}
                </flux:sidebar.item>
            </flux:sidebar.group>

            <flux:sidebar.group :heading="__('Customers')" class="grid">
                <flux:sidebar.item icon="users" :href="route('admin.customers.index')" :current="request()->routeIs('admin.customers.*')"
                    wire:navigate>
                    {{ __('All customers') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="star" :href="route('admin.reviews.index')" :current="request()->routeIs('admin.reviews.*')"
                    wire:navigate>
                    {{ __('Reviews') }}
                </flux:sidebar.item>
            </flux:sidebar.group>

            <flux:sidebar.group :heading="__('Access')" class="grid">
                <flux:sidebar.item icon="shield-check" :href="route('admin.roles.index')" :current="request()->routeIs('admin.roles.*')"
                    wire:navigate>
                    {{ __('Roles') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="key" :href="route('admin.permissions.index')" :current="request()->routeIs('admin.permissions.*')"
                    wire:navigate>
                    {{ __('Permissions') }}
                </flux:sidebar.item>
            </flux:sidebar.group>

            <flux:sidebar.group :heading="__('Logistics')" class="grid">
                <flux:sidebar.item icon="truck" :href="route('admin.delivery-zones')"
                    :current="request()->routeIs('admin.delivery-zones')" wire:navigate>
                    {{ __('Delivery zones') }}
                </flux:sidebar.item>
            </flux:sidebar.group>

            <flux:sidebar.group :heading="__('System')" class="grid">
                <flux:sidebar.item icon="cog-6-tooth" :href="route('admin.settings.index')" :current="request()->routeIs('admin.settings.*')"
                    wire:navigate>
                    {{ __('Settings') }}
                </flux:sidebar.item>
            </flux:sidebar.group>
        </flux:sidebar.nav>

    </flux:sidebar>

    {{-- Top navbar — always visible, contains toolbar actions --}}
    <flux:header class="border-b border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        {{-- Page breadcrumbs (pushed by each page) --}}
        <div class="hidden min-w-0 lg:block">
            @stack('breadcrumbs')
        </div>

        <flux:spacer />

        {{-- Notification bell --}}
        <flux:tooltip content="Notifications" position="bottom">
            <flux:button variant="ghost" square icon="bell" aria-label="Notifications" />
        </flux:tooltip>

        {{-- Appearance toggle --}}
        <flux:dropdown x-data align="end">
            <flux:tooltip content="Appearance" position="bottom">
                <flux:button variant="ghost" square aria-label="Color scheme">
                    <flux:icon.sun x-show="$flux.appearance === 'light'" variant="mini" />
                    <flux:icon.moon x-show="$flux.appearance === 'dark'" variant="mini" />
                    <flux:icon.moon x-show="$flux.appearance === 'system' && $flux.dark" variant="mini" />
                    <flux:icon.sun x-show="$flux.appearance === 'system' && ! $flux.dark" variant="mini" />
                </flux:button>
            </flux:tooltip>
            <flux:menu>
                <flux:menu.item icon="sun" x-on:click="$flux.appearance = 'light'">Light</flux:menu.item>
                <flux:menu.item icon="moon" x-on:click="$flux.appearance = 'dark'">Dark</flux:menu.item>
                <flux:menu.item icon="computer-desktop" x-on:click="$flux.appearance = 'system'">System</flux:menu.item>
            </flux:menu>
        </flux:dropdown>

        {{-- Account dropdown --}}
        <flux:dropdown position="bottom" align="end">
            <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />

            <flux:menu>
                <div class="flex items-center gap-3 px-3 py-2">
                    <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" />
                    <div class="min-w-0">
                        <div class="truncate text-sm font-semibold dark:text-white">{{ auth()->user()->name }}</div>
                        <div class="truncate text-xs text-zinc-500">{{ auth()->user()->email }}</div>
                    </div>
                </div>

                <flux:menu.separator />

                <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>Settings</flux:menu.item>
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
    </flux:header>

    {{ $slot }}

    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist

    @fluxScripts
</body>

</html>
