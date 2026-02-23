<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
    <flux:sidebar sticky collapsible class="border-e border-zinc-200 bg-white ">
        <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

        <a href="{{ route('dashboard') }}" class="me-5 my-4 flex items-center space-x-2 rtl:space-x-reverse"
            wire:navigate>
            {{-- <x-app-logo /> --}}
            <img src="{{ asset('logo-inverse.png') }}" alt="" class="w-40 h-auto mx-auto">
        </a>

        <flux:sidebar.nav variant="outline">
            <flux:sidebar.group :heading="__('Platform')" class="grid">
                <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')"
                    wire:navigate>{{ __('Dashboard') }}</flux:sidebar.item>
            </flux:sidebar.group>


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

            {{-- Sales --}}
            <flux:sidebar.group heading="Sales" class="grid">
                <flux:sidebar.item icon="shopping-cart" wire:navigate :href="route('admin.orders')"
                    :current="request()->routeIs('admin.orders.*')">Orders
                </flux:sidebar.item>

                <flux:sidebar.item icon="banknotes" wire:navigate :href="route('admin.payments')"
                    :current="request()->routeIs('admin.payments.*')">Payments
                </flux:sidebar.item>

                <flux:sidebar.item icon="arrow-uturn-left" wire:navigate href="#">Returns &
                    Refunds</flux:sidebar.item>
            </flux:sidebar.group>


            {{-- Inventory Management --}}
            <flux:sidebar.group heading="Logistics" class="grid">

                {{-- Geographic Setup --}}
                <flux:sidebar.item icon="map" wire:navigate :href="route('admin.zones')"
                    :current="request()->routeIs('admin.zones*')">
                    Shipping Zones
                </flux:sidebar.item>

                <flux:sidebar.group heading="Counties & Areas" expandable expanded="false" class="grid">
                    <flux:sidebar.item icon="building-office-2" wire:navigate :href="route('admin.counties')"
                        :current="request()->routeIs('admin.counties*')">
                        Counties
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="map-pin" wire:navigate :href="route('admin.areas')"
                        :current="request()->routeIs('admin.areas*')">
                        Areas
                    </flux:sidebar.item>
                </flux:sidebar.group>

                {{-- Shipping Configuration --}}
                <flux:sidebar.item icon="truck" wire:navigate :href="route('admin.shipping-methods')"
                    :current="request()->routeIs('admin.shipping-methods*')">
                    Shipping Methods
                </flux:sidebar.item>

                <flux:sidebar.item icon="building-storefront" wire:navigate :href="route('admin.pickup-stations')"
                    :current="request()->routeIs('admin.pickup-stations')">
                    Pickup Stations
                </flux:sidebar.item>

                <flux:sidebar.item icon="currency-dollar" wire:navigate :href="route('admin.shipping-rates')"
                    :current="request()->routeIs('admin.shipping-rates*')">
                    Shipping Rates
                </flux:sidebar.item>

                {{-- <flux:sidebar.item icon="gift" wire:navigate :href="route('admin.free-shipping')"
                :current="request()->routeIs('admin.free-shipping')">
                Free Shipping Rules
            </flux:sidebar.item> --}}
            </flux:sidebar.group>

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

            <flux:sidebar.group heading="Access & Control" class="grid">
                <flux:sidebar.item icon="shield" wire:navigate :href="route('admin.roles.index')" wire:navigate
                    :current="request()->routeIs('admin.roles*')">Roles
                </flux:sidebar.item>

                <flux:sidebar.item icon="key" wire:navigate :href="route('admin.permissions.index')"
                    :current="request()->routeIs('admin.permissions*')">
                    Permissions
                </flux:sidebar.item>
            </flux:sidebar.group>

            {{-- Reports --}}
            <flux:sidebar.group heading="Reports & Analytics" expanded="false" class="grid">
                <flux:sidebar.item icon="chart-bar" wire:navigate href="#">Reports
                </flux:sidebar.item>
            </flux:sidebar.group>

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
