<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
    <flux:sidebar sticky stashable class="border-e border-zinc-200 bg-white ">
        <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

        <a href="{{ route('dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
            <x-app-logo />
        </a>

        <flux:navlist variant="outline">
            <flux:navlist.group :heading="__('Platform')" class="grid">
                <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')"
                    wire:navigate>{{ __('Dashboard') }}</flux:navlist.item>
            </flux:navlist.group>
        </flux:navlist>

        {{-- Sales --}}
        <flux:navlist.group heading="Order Management" class="grid">
            <flux:navlist.item icon="shopping-cart" wire:navigate href="#">Orders</flux:navlist.item>
            <flux:navlist.item icon="truck" wire:navigate href="#">Shipments
            </flux:navlist.item>
            <flux:navlist.item icon="arrow-uturn-left" wire:navigate href="#">Returns &
                Refunds</flux:navlist.item>
            <flux:navlist.item icon="banknotes" wire:navigate href="#">Transaction
            </flux:navlist.item>
        </flux:navlist.group>

        {{-- Catalog Management --}}
        <flux:navlist.group heading="Catalog" class="grid">
            <flux:navlist.item icon="cube" wire:navigate href="#">
                Products
            </flux:navlist.item>

            <flux:navlist.item icon="folder" wire:navigate :href="route('admin.categories')">
                Categories
            </flux:navlist.item>

            <flux:navlist.item icon="adjustments-horizontal" wire:navigate href="#">
                Attributes
            </flux:navlist.item>

            <flux:navlist.item icon="building-office" wire:navigate href="#">
                Brands
            </flux:navlist.item>

            <flux:navlist.item icon="tag" wire:navigate href="#">
                Tags
            </flux:navlist.item>
        </flux:navlist.group>

        {{-- Inventory Management --}}
        <flux:navlist.group heading="Logistics" class="grid">

            {{-- Geographic Setup --}}
            <flux:navlist.item icon="map" wire:navigate :href="route('admin.zones')"
                :current="request()->routeIs('admin.zones*')">
                Shipping Zones
            </flux:navlist.item>

            <flux:navlist.group heading="Counties & Areas" expandable expanded="false" class="grid">
                <flux:navlist.item icon="building-office-2" wire:navigate :href="route('admin.counties')"
                    :current="request()->routeIs('admin.counties*')">
                    Counties
                </flux:navlist.item>

                <flux:navlist.item icon="map-pin" wire:navigate :href="route('admin.areas')"
                    :current="request()->routeIs('admin.areas*')">
                    Areas
                </flux:navlist.item>
            </flux:navlist.group>

            {{-- Shipping Configuration --}}
            <flux:navlist.item icon="truck" wire:navigate :href="route('admin.shipping-methods')"
                :current="request()->routeIs('admin.shipping-methods*')">
                Delivery Methods
            </flux:navlist.item>

            <flux:navlist.item icon="currency-dollar" wire:navigate :href="route('admin.shipping-rates')"
                :current="request()->routeIs('admin.shipping-rates*')">
                Shipping Rates
            </flux:navlist.item>

            <flux:navlist.item icon="building-storefront" wire:navigate :href="route('admin.pickup-stations')"
                :current="request()->routeIs('admin.pickup-stations')">
                Pickup Stations
            </flux:navlist.item>

            <flux:navlist.item icon="gift" wire:navigate :href="route('admin.free-shipping')"
                :current="request()->routeIs('admin.free-shipping')">
                Free Shipping Rules
            </flux:navlist.item>
        </flux:navlist.group>

        {{-- Customer --}}
        <flux:navlist.group heading="Customers" class="grid">
            <flux:navlist.group heading="Customers" expandable expanded="false" class="grid">
                <flux:navlist.item icon="users" wire:navigate href="#">All Customers
                </flux:navlist.item>
                <flux:navlist.item icon="plus-circle" wire:navigate href="#">Add New Customer
                </flux:navlist.item>
            </flux:navlist.group>
            <flux:navlist.item icon="star" wire:navigate href="#">
                Reviews

            </flux:navlist.item>
        </flux:navlist.group>

        {{-- Reports --}}
        <flux:navlist.group heading="Reports & Analytics" expanded="false" class="grid">
            <flux:navlist.item icon="chart-bar" wire:navigate href="#">Sales Reports
            </flux:navlist.item>
            <flux:navlist.item icon="archive-box" wire:navigate href="#">Inventory Reports
            </flux:navlist.item>
            <flux:navlist.item icon="user-group" wire:navigate href="#">Customer Reports
            </flux:navlist.item>
            <flux:navlist.item icon="currency-dollar" wire:navigate href="#">Revenue Reports
            </flux:navlist.item>
            <flux:navlist.item icon="truck" wire:navigate href="#">Shipping Reports</flux:navlist.item>
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

        <flux:navlist.group heading="Settings" class="grid">
            <flux:navlist.item icon="cog" wire:navigate href="#">General Settings
            </flux:navlist.item>
            <flux:navlist.item icon="credit-card" wire:navigate href="#">Payment Gateway
            </flux:navlist.item>
            <flux:navlist.item icon="calculator" wire:navigate href="#">Tax Settings
            </flux:navlist.item>
            <flux:navlist.item icon="at-symbol" wire:navigate href="#">Email Configuration
            </flux:navlist.item>
            <flux:navlist.item icon="shield-check" wire:navigate href="#">Roles & Permissions
            </flux:navlist.item>
        </flux:navlist.group>
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
