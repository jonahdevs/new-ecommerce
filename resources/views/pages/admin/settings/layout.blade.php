@props([
    'heading' => '',
    'subheading' => '',
])

@php
    // During Livewire update requests, Route::currentRouteName() returns
    // the Livewire endpoint name instead of the page route. We fall back
    // to the previous URL so the active-tab resolution stays correct.
    $currentRoute = Route::currentRouteName();

    if (request()->routeIs('livewire.*') || str_contains($currentRoute ?? '', 'livewire')) {
        $previousUrl = url()->previous();
        try {
            $previousRoute = app('router')->getRoutes()->match(
                request()->create($previousUrl)
            )->getName();
            if ($previousRoute) {
                $currentRoute = $previousRoute;
            }
        } catch (\Throwable $e) {
            // keep $currentRoute as-is
        }
    }

    $tabs = [
        'account' => [
            'label' => __('Account'),
            'icon' => 'user-circle',
            'route' => 'profile.edit',
            'permission' => null,
            'active_on' => ['profile.edit', 'user-password.edit', 'two-factor.show', 'appearance.edit'],
        ],
        'general' => [
            'label' => __('General'),
            'icon' => 'building-storefront',
            'route' => 'settings.store-info',
            'permission' => 'manage.settings',
            'active_on' => ['settings.store-info', 'settings.localization', 'settings.regional'],
        ],
        'commerce' => [
            'label' => __('Commerce'),
            'icon' => 'shopping-bag',
            'route' => 'settings.orders',
            'permission' => 'manage.settings',
            'active_on' => ['settings.orders', 'settings.quotations', 'settings.tax', 'settings.tax-classes', 'settings.reviews', 'settings.inventory'],
        ],
        'payments' => [
            'label' => __('Payments'),
            'icon' => 'credit-card',
            'route' => 'settings.payments.gateways',
            'permission' => 'manage.settings',
            'active_on' => [
                'settings.payments.gateways',
                'settings.payments.mpesa',
                'settings.payments.stripe',
                'settings.payments.paypal',
                'settings.payments.pesapal',
                'settings.payments.pesawise',
                'settings.payments.cod',
            ],
        ],
        'notifications' => [
            'label' => __('Notifications'),
            'icon' => 'bell',
            'route' => 'settings.mail',
            'permission' => 'manage.settings',
            'active_on' => ['settings.mail', 'settings.admin-alerts', 'settings.customer-emails'],
        ],
        'seo' => [
            'label' => __('SEO & Marketing'),
            'icon' => 'magnifying-glass',
            'route' => 'settings.seo',
            'permission' => 'manage.settings',
            'active_on' => ['settings.seo', 'settings.social'],
        ],
        'system' => [
            'label' => __('System'),
            'icon' => 'cog-6-tooth',
            'route' => 'settings.maintenance',
            'permission' => 'manage.settings',
            'active_on' => ['settings.maintenance'],
        ],
    ];

    $subnavs = [
        'account' => [
            ['label' => __('Profile'), 'route' => 'profile.edit', 'permission' => null],
            ['label' => __('Password'), 'route' => 'user-password.edit', 'permission' => null],
            [
                'label' => __('Two-Factor Auth'),
                'route' => 'two-factor.show',
                'permission' => null,
                'visible' => Laravel\Fortify\Features::canManageTwoFactorAuthentication(),
            ],
            ['label' => __('Appearance'), 'route' => 'appearance.edit', 'permission' => null],
        ],
        'general' => [
            ['label' => __('Store info'), 'route' => 'settings.store-info', 'permission' => 'manage.settings'],
            ['label' => __('Localization'), 'route' => 'settings.localization', 'permission' => 'manage.settings'],
            ['label' => __('Regional'), 'route' => 'settings.regional', 'permission' => 'manage.settings'],
        ],
        'commerce' => [
            ['label' => __('Orders'), 'route' => 'settings.orders', 'permission' => 'manage.settings'],
            ['label' => __('Quotations'), 'route' => 'settings.quotations', 'permission' => 'manage.settings'],
            ['label' => __('Tax'), 'route' => 'settings.tax', 'permission' => 'manage.settings'],
            ['label' => __('Tax Classes'), 'route' => 'settings.tax-classes', 'permission' => 'manage.settings'],
            ['label' => __('Reviews'), 'route' => 'settings.reviews', 'permission' => 'manage.settings'],
            ['label' => __('Inventory'), 'route' => 'settings.inventory', 'permission' => 'manage.settings'],
        ],

        //  Payments subnav is built dynamically below based on gateway_mode
        'payments' => [],

        'notifications' => [
            ['label' => __('Mail config'), 'route' => 'settings.mail', 'permission' => 'manage.settings'],
            ['label' => __('Admin alerts'), 'route' => 'settings.admin-alerts', 'permission' => 'manage.settings'],
            [
                'label' => __('Customer emails'),
                'route' => 'settings.customer-emails',
                'permission' => 'manage.settings',
            ],
        ],
        'seo' => [
            ['label' => __('SEO'), 'route' => 'settings.seo', 'permission' => 'manage.settings'],
            ['label' => __('Social links'), 'route' => 'settings.social', 'permission' => 'manage.settings'],
        ],
        'system' => [
            ['label' => __('Maintenance'), 'route' => 'settings.maintenance', 'permission' => 'manage.settings'],
        ],
    ];

    //  Resolve active tab
    $activeTab =
        collect($tabs)
            ->filter(function ($tab) use ($currentRoute) {
                return in_array($currentRoute, $tab['active_on']);
            })
            ->keys()
            ->first() ?? 'account';

    //  Build payments subnav dynamically from PaymentSettings
    // Items shown depend on gateway_mode so the subnav stays consistent
    // with the Overview page toggle without requiring a page reload.
    if (auth()->user()->can('manage.settings')) {
        $paymentSettings = app(\App\Settings\PaymentSettings::class);
        $gatewayMode = $paymentSettings->gateway_mode; // individual | aggregator

        $paymentItems = [
            // Overview is always the first item
            ['label' => __('Gateways'), 'route' => 'settings.payments.gateways'],
        ];

        if ($gatewayMode === 'individual') {
            $paymentItems[] = ['label' => __('M-Pesa'), 'route' => 'settings.payments.mpesa'];
            $paymentItems[] = ['label' => __('Stripe'), 'route' => 'settings.payments.stripe'];
            $paymentItems[] = ['label' => __('PayPal'), 'route' => 'settings.payments.paypal'];
        } else {
            // Aggregator — show both providers so either can be configured
            $paymentItems[] = ['label' => __('PesaPal'), 'route' => 'settings.payments.pesapal'];
            $paymentItems[] = ['label' => __('PesaWise'), 'route' => 'settings.payments.pesawise'];
        }

        // COD always available — independent of mode
        $paymentItems[] = ['label' => __('Cash on delivery'), 'route' => 'settings.payments.cod'];

        $subnavs['payments'] = collect($paymentItems)
            ->map(fn($item) => array_merge($item, ['permission' => 'manage.settings']))
            ->toArray();
    }
@endphp

<div class="flex flex-col">

    {{--  Page heading  --}}
    <div class="mb-1">
        <flux:heading size="xl">{{ __('Settings') }}</flux:heading>
        <flux:subheading>{{ __('Manage your store configuration and account preferences') }}</flux:subheading>
    </div>

    {{--  Top tabs  --}}
    <div class="mt-4 border-b border-zinc-200 dark:border-zinc-600">
        <nav class="flex gap-1 overflow-x-auto">
            @foreach ($tabs as $key => $tab)
                @php
                    $canSee = $tab['permission'] ? auth()->user()->can($tab['permission']) : true;
                    $isActive = $activeTab === $key;
                @endphp

                @if ($canSee)
                    <a href="{{ route($tab['route']) }}" wire:navigate @class([
                        'inline-flex items-center gap-1.5 px-3 py-2 text-sm whitespace-nowrap transition-colors duration-150',
                        'bg-brand-primary text-brand-primary-content font-medium' => $isActive,
                        'text-zinc-500 hover:text-zinc-800 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:text-zinc-200 dark:hover:bg-zinc-800' => !$isActive,
                    ])>
                        <flux:icon :name="$tab['icon']" class="size-4 shrink-0" variant="outline" />
                        {{ $tab['label'] }}
                    </a>
                @endif
            @endforeach
        </nav>
    </div>

    {{--  Body: sub-nav + content ─ --}}
    <div class="flex items-start gap-8 mt-6 max-md:flex-col">

        {{-- Left sub-nav --}}
        <div class="w-full md:w-[200px] shrink-0">
            <flux:navlist aria-label="{{ __('Settings navigation') }}">
                @foreach ($subnavs[$activeTab] as $item)
                    @php
                        $itemVisible = $item['visible'] ?? true;
                        $itemAllowed = $item['permission'] ? auth()->user()->can($item['permission']) : true;
                    @endphp

                    @if ($itemVisible && $itemAllowed)
                        <flux:navlist.item :href="route($item['route'])" :current="$currentRoute === $item['route']"
                            wire:navigate>
                            {{ $item['label'] }}
                        </flux:navlist.item>
                    @endif
                @endforeach
            </flux:navlist>
        </div>

        <flux:separator class="md:hidden" />

        {{-- Main content --}}
        <div class="flex-1 min-w-0">
            @if ($heading || $subheading)
                <div class="mb-5">
                    @if ($heading)
                        <flux:heading size="lg">{{ $heading }}</flux:heading>
                    @endif
                    @if ($subheading)
                        <flux:subheading>{{ $subheading }}</flux:subheading>
                    @endif
                </div>
            @endif

            {{ $slot }}
        </div>

    </div>
</div>
