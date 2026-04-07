<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800 app-layout">
    <flux:sidebar sticky stashable class="border-e border-zinc-200 ">
        <flux:sidebar.header>
            <img src="{{ asset('logo-inverse.png') }}" alt="" class="w-40 h-auto mx-auto">
        </flux:sidebar.header>

        <flux:navlist>
            <flux:navlist.group :heading="__('Platform')" icon="squares-2x2" class="grid">
                <flux:navlist.item icon="home" :href="route('admin.dashboard')"
                    :current="request()->routeIs('admin.dashboard')" wire:navigate>{{ __('Dashboard') }}
                </flux:navlist.item>
            </flux:navlist.group>


            {{-- Catalog Management --}}
            <flux:navlist.group heading="Catalog" class="grid">
                <flux:navlist.item icon="cube" wire:navigate :href="route('admin.catalog.products.index')"
                    wire:navigate :current="request()->routeIs('admin.catalog.products.*')">
                    Products
                </flux:navlist.item>

                <flux:navlist.item icon="folder" wire:navigate :href="route('admin.catalog.categories.index')"
                    :current="request()->routeIs('admin.catalog.categories.*')">
                    Categories
                </flux:navlist.item>

                <flux:navlist.item icon="adjustments-horizontal" wire:navigate
                    :href="route('admin.catalog.attributes.index')"
                    :current="request()->routeIs('admin.catalog.attributes.*')">
                    Attributes
                </flux:navlist.item>

                <flux:navlist.item icon="building-office" wire:navigate :href="route('admin.catalog.brands.index')"
                    :current="request()->routeIs('admin.catalog.brands.*')">
                    Brands
                </flux:navlist.item>

                <flux:navlist.item icon="tag" wire:navigate :href="route('admin.catalog.tags.index')"
                    :current="request()->routeIs('admin.catalog.tags.*')">
                    Tags
                </flux:navlist.item>
            </flux:navlist.group>


            {{-- Sales --}}
            <flux:navlist.group heading="Sales" class="grid">
                <flux:navlist.item icon="document-text" wire:navigate :href="route('admin.quotations.index')"
                    :current="request()->routeIs('admin.quotations.*')">Quotations
                </flux:navlist.item>

                <flux:navlist.item icon="shopping-cart" wire:navigate :href="route('admin.orders.index')"
                    :current="request()->routeIs('admin.orders.*')">Orders
                </flux:navlist.item>

                <flux:navlist.item icon="banknotes" wire:navigate :href="route('admin.payments.index')"
                    :current="request()->routeIs('admin.payments.*')">Payments
                </flux:navlist.item>
            </flux:navlist.group>


            {{-- Logistics --}}
            <flux:navlist.group heading="Logistics" class="grid">
                <flux:navlist.item icon="truck" wire:navigate :href="route('admin.logistics.overview')"
                    :current="request()->routeIs('admin.logistics.*')">
                    Logistics
                </flux:navlist.item>
            </flux:navlist.group>


            {{-- Customer --}}
            <flux:navlist.group heading="Customers" class="grid">
                <flux:navlist.item icon="users" wire:navigate :href="route('admin.customers.index')"
                    :current="request()->routeIs('admin.customers*')">All Customers
                </flux:navlist.item>

                <flux:navlist.item icon="star" wire:navigate :href="route('admin.reviews.index')"
                    :current="request()->routeIs('admin.reviews*')">
                    Reviews
                </flux:navlist.item>
            </flux:navlist.group>


            <flux:navlist.group heading="Access & Control" class="grid">
                <flux:navlist.item icon="shield" wire:navigate :href="route('admin.access-control.roles.index')"
                    wire:navigate :current="request()->routeIs('admin.access-control.roles*')">Roles
                </flux:navlist.item>

                <flux:navlist.item icon="key" wire:navigate :href="route('admin.access-control.permissions')"
                    :current="request()->routeIs('admin.access-control.permissions*')">
                    Permissions
                </flux:navlist.item>
            </flux:navlist.group>


            {{-- Reports --}}
            <flux:navlist.group heading="Reports & Analytics" expanded="false" class="grid">
                <flux:navlist.item icon="chart-bar" wire:navigate href="#">Reports
                </flux:navlist.item>
                <flux:navlist.item icon="clipboard-document-list" wire:navigate
                    :href="route('admin.activity-logs.index')" :current="request()->routeIs('admin.activity-logs.*')">
                    Activity Logs
                </flux:navlist.item>
            </flux:navlist.group>


            {{-- Marketing & Content --}}
            <flux:navlist.group heading="Marketing & Content" expanded="false" class="grid">
                <flux:navlist.item icon="megaphone" wire:navigate :href="route('admin.marketing.campaigns.index')"
                    :current="request()->routeIs('admin.marketing.campaigns.*')">Campaigns</flux:navlist.item>
                <flux:navlist.item icon="ticket" wire:navigate :href="route('admin.marketing.coupons.index')"
                    :current="request()->routeIs('admin.marketing.coupons.*')">Coupons & Discounts
                </flux:navlist.item>
                <flux:navlist.item icon="envelope" wire:navigate :href="route('admin.marketing.newsletter.index')"
                    :current="request()->routeIs('admin.marketing.newsletter.*')">Newsletter</flux:navlist.item>
                <flux:navlist.item icon="document-text" wire:navigate :href="route('admin.content.blog.index')"
                    :current="request()->routeIs('admin.content.blog.*')">Blog Posts
                </flux:navlist.item>
                <flux:navlist.item icon="question-mark-circle" wire:navigate :href="route('admin.content.faq.index')"
                    :current="request()->routeIs('admin.content.faq.*')">FAQ Management
                </flux:navlist.item>

            </flux:navlist.group>


            <flux:navlist.group heading="Settings & Others" class="grid">
                <flux:navlist.item icon="cog" wire:navigate :href="route('profile.edit')">Settings
                </flux:navlist.item>
            </flux:navlist.group>
        </flux:navlist>
    </flux:sidebar>

    <!-- Mobile User Menu -->
    <flux:header class="bg-white dark:bg-zinc-900/90 border-b border-zinc-200 dark:border-zinc-700">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <flux:spacer />

        <div class="flex items-center gap-3">
            {{-- Notifications --}}
            <livewire:admin-notifications-dropdown />

            <flux:dropdown x-data align="end" hover>
                <flux:button variant="subtle" square class="group" aria-label="Preferred color scheme">
                    <flux:icon.sun x-show="$flux.appearance === 'light'" variant="mini"
                        class="text-zinc-500 dark:text-white" />
                    <flux:icon.moon x-show="$flux.appearance === 'dark'" variant="mini"
                        class="text-zinc-500 dark:text-white" />
                    <flux:icon.moon x-show="$flux.appearance === 'system' && $flux.dark" variant="mini" />
                    <flux:icon.sun x-show="$flux.appearance === 'system' && ! $flux.dark" variant="mini" />
                </flux:button>

                <flux:menu>
                    <flux:menu.item icon="sun" x-on:click="$flux.appearance = 'light'">Light</flux:menu.item>
                    <flux:menu.item icon="moon" x-on:click="$flux.appearance = 'dark'">Dark</flux:menu.item>
                    <flux:menu.item icon="computer-desktop" x-on:click="$flux.appearance = 'system'">System
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>

            <flux:dropdown position="top" align="end" hover>
                <flux:profile circle :initials="auth()->user()->initials()" icon-trailing="chevron-down" />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item href="{{ route('profile.edit') }}" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                            class="w-full" data-test="logout-button">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </div>
    </flux:header>

    {{ $slot }}

    @fluxScripts
    @stack('scripts')
</body>

</html>
