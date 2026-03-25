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
                <flux:navlist.item icon="shopping-cart" wire:navigate :href="route('admin.orders.index')"
                    :current="request()->routeIs('admin.orders.*')">Orders
                </flux:navlist.item>

                <flux:navlist.item icon="banknotes" wire:navigate :href="route('admin.payments.index')"
                    :current="request()->routeIs('admin.payments.*')">Payments
                </flux:navlist.item>
            </flux:navlist.group>


            {{-- Logistics Management --}}
            <flux:navlist.group heading="Logistics" class="grid">

                {{-- Dashboard --}}
                <flux:navlist.item icon="chart-bar-square" wire:navigate :href="route('admin.logistics.overview')"
                    :current="request()->routeIs('admin.logistics.overview')">
                    Overview
                </flux:navlist.item>

                {{--  Configuration  --}}
                <flux:navlist.group heading="Configuration" expandable expanded="false" class="grid">

                    <flux:navlist.item icon="building-office" wire:navigate
                        :href="route('admin.logistics.configuration.providers')"
                        :current="request()->routeIs('admin.logistics.configuration.providers')">
                        Providers
                    </flux:navlist.item>

                    <flux:navlist.item icon="map" wire:navigate
                        :href="route('admin.logistics.configuration.zones')"
                        :current="request()->routeIs('admin.logistics.configuration.zones')">
                        Zones
                    </flux:navlist.item>

                    <flux:navlist.group heading="Locations" expandable expanded="false" class="grid">
                        <flux:navlist.item icon="building-office-2" wire:navigate
                            :href="route('admin.logistics.configuration.locations.counties')"
                            :current="request()->routeIs('admin.logistics.configuration.locations.counties')">
                            Counties
                        </flux:navlist.item>
                        <flux:navlist.item icon="map-pin" wire:navigate
                            :href="route('admin.logistics.configuration.locations.areas')"
                            :current="request()->routeIs('admin.logistics.configuration.locations.areas')">
                            Areas
                        </flux:navlist.item>
                    </flux:navlist.group>

                    <flux:navlist.item icon="truck" wire:navigate
                        :href="route('admin.logistics.configuration.methods')"
                        :current="request()->routeIs('admin.logistics.configuration.methods')">
                        Methods
                    </flux:navlist.item>

                    <flux:navlist.group heading="Rates" expandable expanded="false" class="grid">
                        <flux:navlist.item icon="table-cells" wire:navigate
                            :href="route('admin.logistics.configuration.rates.flat')"
                            :current="request()->routeIs('admin.logistics.configuration.rates.flat')">
                            Flat Rates
                        </flux:navlist.item>

                        <flux:navlist.item icon="calculator" wire:navigate
                            :href="route('admin.logistics.configuration.rates.vehicle')"
                            :current="request()->routeIs('admin.logistics.configuration.rates.vehicle')">
                            Vehicle Rates
                        </flux:navlist.item>

                        <flux:navlist.item icon="plus-circle" wire:navigate
                            :href="route('admin.logistics.configuration.rates.addons')"
                            :current="request()->routeIs('admin.logistics.configuration.rates.addons')">
                            Rate Addons
                        </flux:navlist.item>
                    </flux:navlist.group>

                    <flux:navlist.item icon="building-storefront" wire:navigate
                        :href="route('admin.logistics.configuration.pickup-stations')"
                        :current="request()->routeIs('admin.logistics.configuration.pickup-stations')">
                        Pickup Stations
                    </flux:navlist.item>

                    <flux:navlist.item icon="gift" wire:navigate
                        :href="route('admin.logistics.configuration.free-shipping-rules')"
                        :current="request()->routeIs('admin.logistics.configuration.free-shipping-rules')">
                        Free Shipping Rules
                    </flux:navlist.item>
                </flux:navlist.group>

                {{--  Operations  --}}
                <flux:navlist.group heading="Operations" expandable expanded="false" class="grid">

                    <flux:navlist.item icon="clipboard-document-list" wire:navigate
                        :href="route('admin.logistics.operations.delivery-orders')"
                        :current="request()->routeIs('admin.logistics.operations.delivery-orders')">
                        Delivery Orders
                    </flux:navlist.item>

                    <flux:navlist.item icon="arrow-uturn-left" wire:navigate
                        :href="route('admin.logistics.operations.returns')"
                        :current="request()->routeIs('admin.logistics.operations.returns')">
                        Returns
                    </flux:navlist.item>

                    <flux:navlist.item icon="building-storefront" wire:navigate
                        :href="route('admin.logistics.operations.pus-tracker')"
                        :current="request()->routeIs('admin.logistics.operations.pus-tracker')">
                        PUS Tracker
                    </flux:navlist.item>
                </flux:navlist.group>

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
            </flux:navlist.group>


            {{-- Marketing & Content --}}
            <flux:navlist.group heading="Marketing & Content" expanded="false" class="grid">
                <flux:navlist.item icon="megaphone" wire:navigate href="#">Campaigns</flux:navlist.item>
                <flux:navlist.item icon="ticket" wire:navigate href="#">Coupons & Discounts
                </flux:navlist.item>
                <flux:navlist.item icon="envelope" wire:navigate href="#">Newsletter</flux:navlist.item>
                <flux:navlist.item icon="document-text" wire:navigate href="#">Blog Posts
                </flux:navlist.item>
                <flux:navlist.item icon="question-mark-circle" wire:navigate href="#">FAQ Management
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
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full"
                        data-test="logout-button">
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
