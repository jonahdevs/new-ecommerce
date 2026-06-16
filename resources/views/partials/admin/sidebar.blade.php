{{-- Admin navigation sidebar. Shared by the main admin layout and the admin
     error layout. Items are permission-gated, so an unauthenticated viewer (e.g.
     an expired session landing on an error page) simply sees the minimal set. --}}
<flux:sidebar sticky collapsible="mobile"
    class="scrollbar-thin border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 print:hidden">
    <flux:sidebar.header>
        <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
        <flux:sidebar.collapse class="lg:hidden" />
    </flux:sidebar.header>

    <flux:sidebar.nav class="scrollbar-thin">
        <flux:sidebar.group :heading="__('Overview')" class="grid">
            <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')"
                wire:navigate>
                {{ __('Dashboard') }}
            </flux:sidebar.item>
        </flux:sidebar.group>

        @canany(['products.view', 'catalog.manage', 'tags.manage'])
        <flux:sidebar.group :heading="__('Catalog')" class="grid">
            @can('products.view')
            <flux:sidebar.item icon="cube" :href="route('admin.products.index')" :current="request()->routeIs('admin.products.*')"
                wire:navigate>
                {{ __('Products') }}
            </flux:sidebar.item>
            @endcan
            @can('catalog.manage')
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
            @endcan
            @can('tags.manage')
            <flux:sidebar.item icon="hashtag" :href="route('admin.tags.index')"
                :current="request()->routeIs('admin.tags.*')" wire:navigate>
                {{ __('Tags') }}
            </flux:sidebar.item>
            @endcan
            @can('catalog.manage')
            <flux:sidebar.item icon="receipt-percent" :href="route('admin.tax-classes.index')"
                :current="request()->routeIs('admin.tax-classes.*')" wire:navigate>
                {{ __('Tax classes') }}
            </flux:sidebar.item>
            @endcan
        </flux:sidebar.group>
        @endcanany

        @canany(['quotes.view', 'orders.view', 'payments.view'])
        <flux:sidebar.group :heading="__('Sales')" class="grid">
            @can('quotes.view')
            <flux:sidebar.item icon="document-text" :href="route('admin.quotes.index')" :current="request()->routeIs('admin.quotes.*')"
                wire:navigate>
                {{ __('Quotations') }}
            </flux:sidebar.item>
            @endcan
            @can('orders.view')
            <flux:sidebar.item icon="shopping-cart" :href="route('admin.orders.index')" :current="request()->routeIs('admin.orders.*')"
                wire:navigate>
                {{ __('Orders') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="arrow-path-rounded-square" :href="route('admin.sap-sync')" :current="request()->routeIs('admin.sap-sync')"
                wire:navigate>
                {{ __('SAP sync') }}
            </flux:sidebar.item>
            @endcan
            @can('payments.view')
            <flux:sidebar.item icon="credit-card" :href="route('admin.payments.index')" :current="request()->routeIs('admin.payments.*')"
                wire:navigate>
                {{ __('Payments') }}
            </flux:sidebar.item>
            @endcan
        </flux:sidebar.group>
        @endcanany

        @canany(['customers.view', 'reviews.manage'])
        <flux:sidebar.group :heading="__('Customers')" class="grid">
            @can('customers.view')
            <flux:sidebar.item icon="users" :href="route('admin.customers.index')" :current="request()->routeIs('admin.customers.*')"
                wire:navigate>
                {{ __('All customers') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="envelope" :href="route('admin.subscribers.index')" :current="request()->routeIs('admin.subscribers.*')"
                wire:navigate>
                {{ __('Subscribers') }}
            </flux:sidebar.item>
            @endcan
            @can('reviews.manage')
            <flux:sidebar.item icon="star" :href="route('admin.reviews.index')" :current="request()->routeIs('admin.reviews.*')"
                wire:navigate>
                {{ __('Reviews') }}
            </flux:sidebar.item>
            @endcan
        </flux:sidebar.group>
        @endcanany

        @can('roles.manage')
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
        @endcan

        @can('settings.manage')
        <flux:sidebar.group :heading="__('Content')" class="grid">
            <flux:sidebar.item icon="document-text" :href="route('admin.pages.index')"
                :current="request()->routeIs('admin.pages.*')" wire:navigate>
                {{ __('Pages') }}
            </flux:sidebar.item>
        </flux:sidebar.group>
        @endcan

        @can('delivery.manage')
        <flux:sidebar.group :heading="__('Logistics')" class="grid">
            <flux:sidebar.item icon="map-pin" :href="route('admin.delivery-zones')"
                :current="request()->routeIs('admin.delivery-zones')" wire:navigate>
                {{ __('Delivery zones') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="tag" :href="route('admin.delivery-promotions')"
                :current="request()->routeIs('admin.delivery-promotions')" wire:navigate>
                {{ __('Delivery promotions') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="truck" :href="route('admin.shipping.methods.index')"
                :current="request()->routeIs('admin.shipping.methods.*')" wire:navigate>
                {{ __('Shipping methods') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="link" :href="route('admin.shipping.carriers.index')"
                :current="request()->routeIs('admin.shipping.carriers.*')" wire:navigate>
                {{ __('Carriers') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="building-office" :href="route('admin.shipping.warehouses.index')"
                :current="request()->routeIs('admin.shipping.warehouses.*')" wire:navigate>
                {{ __('Warehouses') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="building-storefront" :href="route('admin.showrooms.index')"
                :current="request()->routeIs('admin.showrooms.*')" wire:navigate>
                {{ __('Showrooms') }}
            </flux:sidebar.item>
        </flux:sidebar.group>
        @endcan

        @can('settings.manage')
        <flux:sidebar.group :heading="__('System')" class="grid">
            <flux:sidebar.item icon="cog-6-tooth" :href="route('admin.settings.general')" :current="request()->routeIs('admin.settings.*')"
                wire:navigate>
                {{ __('Settings') }}
            </flux:sidebar.item>
        </flux:sidebar.group>
        @endcan
    </flux:sidebar.nav>

</flux:sidebar>
