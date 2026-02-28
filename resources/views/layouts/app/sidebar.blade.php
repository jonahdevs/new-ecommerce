<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
    <flux:sidebar sticky collapsible class="border-e border-zinc-200 bg-white ">
        <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

        <a href="{{ route('admin.dashboard') }}" class="me-5 my-4 flex items-center space-x-2 rtl:space-x-reverse"
            wire:navigate>
            {{-- <x-app-logo /> --}}
            <img src="{{ asset('logo-inverse.png') }}" alt="" class="w-40 h-auto mx-auto">
        </a>

        <flux:sidebar.nav>
            <flux:sidebar.group :heading="__('Platform')" class="grid">
                <flux:sidebar.item icon="home" :href="route('admin.dashboard')"
                    :current="request()->routeIs('admin.dashboard')" wire:navigate>{{ __('Dashboard') }}
                </flux:sidebar.item>
            </flux:sidebar.group>

            <flux:sidebar.spacer class="my-2" />

            {{-- Catalog Management --}}
            <flux:sidebar.group heading="Catalog" class="grid">
                <flux:sidebar.item icon="cube" wire:navigate :href="route('admin.products.index')" wire:navigate
                    :current="request()->routeIs('admin.products.*')">
                    Products
                </flux:sidebar.item>

                <flux:sidebar.item icon="folder" wire:navigate :href="route('admin.categories.index')"
                    :current="request()->routeIs('admin.categories.*')">
                    Categories
                </flux:sidebar.item>

                <flux:sidebar.item icon="adjustments-horizontal" wire:navigate :href="route('admin.attributes.index')"
                    :current="request()->routeIs('admin.attributes.*')">
                    Attributes
                </flux:sidebar.item>

                <flux:sidebar.item icon="building-office" wire:navigate :href="route('admin.brands.index')"
                    :current="request()->routeIs('admin.brands.*')">
                    Brands
                </flux:sidebar.item>

                <flux:sidebar.item icon="tag" wire:navigate :href="route('admin.tags.index')"
                    :current="request()->routeIs('admin.tags.*')">
                    Tags
                </flux:sidebar.item>
            </flux:sidebar.group>

            <flux:sidebar.spacer class="my-2" />

            {{-- Sales --}}
            <flux:sidebar.group heading="Sales" class="grid">
                <flux:sidebar.item icon="shopping-cart" wire:navigate :href="route('admin.orders.index')"
                    :current="request()->routeIs('admin.orders.*')">Orders
                </flux:sidebar.item>

                <flux:sidebar.item icon="banknotes" wire:navigate :href="route('admin.payments.index')"
                    :current="request()->routeIs('admin.payments.*')">Payments
                </flux:sidebar.item>

                <flux:sidebar.item icon="arrow-uturn-left" wire:navigate href="#">Returns &
                    Refunds</flux:sidebar.item>
            </flux:sidebar.group>

            <flux:sidebar.spacer class="my-2" />

            {{-- Logistics Management --}}
            <flux:sidebar.group heading="Logistics" class="grid">

                {{-- Dashboard --}}
                <flux:sidebar.item icon="chart-bar-square" wire:navigate href="#">
                    Overview
                </flux:sidebar.item>

                {{--  Configuration  --}}
                <flux:sidebar.group heading="Configuration" expandable expanded="false" class="grid">

                    <flux:sidebar.item icon="building-office" wire:navigate
                        :href="route('admin.logistics.configurations.providers')"
                        :current="request()->routeIs('admin.logistics.configurations.providers')">
                        Providers
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="map" wire:navigate
                        :href="route('admin.logistics.configurations.zones')"
                        :current="request()->routeIs('admin.logistics.configurations.zones')">
                        Zones
                    </flux:sidebar.item>

                    <flux:sidebar.group heading="Locations" expandable expanded="false" class="grid">
                        <flux:sidebar.item icon="building-office-2" wire:navigate
                            :href="route('admin.logistics.configurations.locations.counties')"
                            :current="request()->routeIs('admin.logistics.configurations.locations.counties')">
                            Counties
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="map-pin" wire:navigate href="#">
                            Areas
                        </flux:sidebar.item>
                    </flux:sidebar.group>

                    <flux:sidebar.item icon="truck" wire:navigate
                        :href="route('admin.logistics.configurations.methods')"
                        :current="request()->routeIs('admin.logistics.configurations.methods')">
                        Methods
                    </flux:sidebar.item>

                    <flux:sidebar.group heading="Rates" expandable expanded="false" class="grid">
                        <flux:sidebar.item icon="table-cells" wire:navigate
                            :href="route('admin.logistics.configurations.rates.flat')"
                            :current="request()->routeIs('admin.logistics.configurations.rates.flat')">
                            Flat Rates
                        </flux:sidebar.item>

                        <flux:sidebar.item icon="calculator" wire:navigate
                            :href="route('admin.logistics.configurations.rates.vehicle')"
                            :current="request()->routeIs('admin.logistics.configurations.rates.vehicle')">
                            Vehicle Rates
                        </flux:sidebar.item>

                        <flux:sidebar.item icon="plus-circle" wire:navigate
                            :href="route('admin.logistics.configurations.rates.addons')"
                            :current="request()->routeIs('admin.logistics.configurations.rates.addons')">
                            Rate Addons
                        </flux:sidebar.item>
                    </flux:sidebar.group>

                    <flux:sidebar.item icon="building-storefront" wire:navigate
                        :href="route('admin.logistics.configurations.pickup-stations')"
                        :current="request()->routeIs('admin.logistics.configurations.pickup-stations')">
                        Pickup Stations
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="gift" wire:navigate
                        :href="route('admin.logistics.configurations.free-shipping-rules')"
                        :current="request()->routeIs('admin.logistics.configurations.free-shipping-rules')">
                        Free Shipping Rules
                    </flux:sidebar.item>
                </flux:sidebar.group>

                {{--  Operations  --}}
                <flux:sidebar.group heading="Operations" expandable expanded="false" class="grid">

                    <flux:sidebar.item icon="clipboard-document-list" wire:navigate
                        :href="route('admin.logistics.operations.delivery-orders')"
                        :current="request()->routeIs('admin.logistics.operations.delivery-orders')">
                        Delivery Orders
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="arrow-uturn-left" wire:navigate
                        :href="route('admin.logistics.operations.returns')"
                        :current="request()->routeIs('admin.logistics.operations.returns')">
                        Returns
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="building-storefront" wire:navigate
                        :href="route('admin.logistics.operations.pus-tracker')"
                        :current="request()->routeIs('admin.logistics.operations.pus-tracker')">
                        PUS Tracker
                    </flux:sidebar.item>
                </flux:sidebar.group>

            </flux:sidebar.group>

            <flux:sidebar.spacer class="my-2" />

            {{-- Customer --}}
            <flux:sidebar.group heading="Customers" class="grid">
                <flux:sidebar.item icon="users" wire:navigate :href="route('admin.customers.index')"
                    :current="request()->routeIs('admin.customers*')">All Customers
                </flux:sidebar.item>

                <flux:sidebar.item icon="star" wire:navigate :href="route('admin.reviews.index')"
                    :current="request()->routeIs('admin.reviews*')">
                    Reviews
                </flux:sidebar.item>
            </flux:sidebar.group>

            <flux:sidebar.spacer class="my-2" />

            <flux:sidebar.group heading="Access & Control" class="grid">
                <flux:sidebar.item icon="shield" wire:navigate :href="route('admin.roles.index')" wire:navigate
                    :current="request()->routeIs('admin.roles*')">Roles
                </flux:sidebar.item>

                <flux:sidebar.item icon="key" wire:navigate :href="route('admin.permissions.index')"
                    :current="request()->routeIs('admin.permissions*')">
                    Permissions
                </flux:sidebar.item>
            </flux:sidebar.group>

            <flux:sidebar.spacer class="my-2" />

            {{-- Reports --}}
            <flux:sidebar.group heading="Reports & Analytics" expanded="false" class="grid">
                <flux:sidebar.item icon="chart-bar" wire:navigate href="#">Reports
                </flux:sidebar.item>
            </flux:sidebar.group>

            <flux:sidebar.spacer class="my-2" />

            {{-- Marketing & Content --}}
            <flux:sidebar.group heading="Marketing & Content" expanded="false" class="grid">
                <flux:sidebar.item icon="megaphone" wire:navigate href="#">Campaigns</flux:sidebar.item>
                <flux:sidebar.item icon="ticket" wire:navigate href="#">Coupons & Discounts
                </flux:sidebar.item>
                <flux:sidebar.item icon="envelope" wire:navigate href="#">Newsletter</flux:sidebar.item>
                <flux:sidebar.item icon="document-text" wire:navigate href="#">Blog Posts
                </flux:sidebar.item>
                <flux:sidebar.item icon="question-mark-circle" wire:navigate href="#">FAQ Management
                </flux:sidebar.item>
            </flux:sidebar.group>

            <flux:sidebar.spacer class="my-2" />

            <flux:sidebar.group heading="Settings & Others" class="grid">
                <flux:sidebar.item icon="cog" wire:navigate :href="route('profile.edit')">Settings
                </flux:sidebar.item>
            </flux:sidebar.group>
        </flux:sidebar.nav>
    </flux:sidebar>

    <!-- Mobile User Menu -->
    <flux:header class="bg-white border-b ">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <flux:spacer />

        <flux:dropdown position="top" align="end">
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
                    <flux:menu.item href="#" icon="cog" wire:navigate>
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
    </flux:header>

    {{ $slot }}



    @fluxScripts
    @stack('scripts')
</body>

</html>
