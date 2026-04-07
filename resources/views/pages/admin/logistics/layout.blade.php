@props([
    'heading' => '',
    'subheading' => '',
    'actions' => null,
])

@php
    // During Livewire update requests, Route::currentRouteName() returns
    // the Livewire endpoint name instead of the page route. We fall back
    // to the previous URL so the active-tab resolution stays correct.
    $currentRoute = Route::currentRouteName();

    if (request()->routeIs('livewire.*') || str_contains($currentRoute ?? '', 'livewire')) {
        $previousUrl = url()->previous();
        try {
            $previousRoute = app('router')
                ->getRoutes()
                ->match(request()->create($previousUrl))
                ->getName();
            if ($previousRoute) {
                $currentRoute = $previousRoute;
            }
        } catch (\Throwable $e) {
            // keep $currentRoute as-is
        }
    }

    $tabs = [
        'overview' => [
            'label' => __('Overview'),
            'icon' => 'chart-bar',
            'route' => 'admin.logistics.overview',
            'active_on' => ['admin.logistics.overview'],
        ],
        'configuration' => [
            'label' => __('Configuration'),
            'icon' => 'cog-6-tooth',
            'route' => 'admin.logistics.configuration.providers',
            'active_on' => [
                'admin.logistics.configuration.providers',
                'admin.logistics.configuration.zones',
                'admin.logistics.configuration.methods',
                'admin.logistics.configuration.pickup-stations',
                'admin.logistics.configuration.free-shipping-rules',
                'admin.logistics.configuration.locations.counties',
                'admin.logistics.configuration.locations.areas',
                'admin.logistics.configuration.rates.addons',
                'admin.logistics.configuration.rates.flat',
                'admin.logistics.configuration.rates.vehicle',
            ],
        ],
        'operations' => [
            'label' => __('Operations'),
            'icon' => 'truck',
            'route' => 'admin.logistics.operations.delivery-orders',
            'active_on' => [
                'admin.logistics.operations.delivery-orders',
                'admin.logistics.operations.pus-tracker',
                'admin.logistics.operations.returns',
            ],
        ],
    ];

    $subnavs = [
        'overview' => [],
        'configuration' => [
            ['label' => __('Providers'), 'route' => 'admin.logistics.configuration.providers'],
            ['label' => __('Zones'), 'route' => 'admin.logistics.configuration.zones'],
            ['label' => __('Methods'), 'route' => 'admin.logistics.configuration.methods'],
            ['label' => __('Pickup Stations'), 'route' => 'admin.logistics.configuration.pickup-stations'],
            ['label' => __('Free Shipping'), 'route' => 'admin.logistics.configuration.free-shipping-rules'],
            ['type' => 'separator'],
            ['type' => 'heading', 'label' => __('Locations')],
            [
                'label' => __('Counties'),
                'route' => 'admin.logistics.configuration.locations.counties',
                'indent' => true,
            ],
            ['label' => __('Areas'), 'route' => 'admin.logistics.configuration.locations.areas', 'indent' => true],
            ['type' => 'separator'],
            ['type' => 'heading', 'label' => __('Rates')],
            ['label' => __('Flat Rate'), 'route' => 'admin.logistics.configuration.rates.flat', 'indent' => true],
            ['label' => __('Vehicle Rate'), 'route' => 'admin.logistics.configuration.rates.vehicle', 'indent' => true],
            ['label' => __('Add-ons'), 'route' => 'admin.logistics.configuration.rates.addons', 'indent' => true],
        ],
        'operations' => [
            ['label' => __('Delivery Orders'), 'route' => 'admin.logistics.operations.delivery-orders'],
            ['label' => __('PUS Tracker'), 'route' => 'admin.logistics.operations.pus-tracker'],
            ['label' => __('Returns'), 'route' => 'admin.logistics.operations.returns'],
        ],
    ];

    // Resolve active tab
    $activeTab =
        collect($tabs)
            ->filter(function ($tab) use ($currentRoute) {
                return in_array($currentRoute, $tab['active_on']);
            })
            ->keys()
            ->first() ?? 'overview';
@endphp

<div class="flex flex-col">

    {{-- Page heading --}}
    <div class="mb-1">
        <flux:heading size="xl">{{ __('Logistics') }}</flux:heading>
        <flux:subheading>{{ __('Manage shipping, delivery, and logistics operations') }}</flux:subheading>
    </div>

    {{-- Top tabs --}}
    <div class="mt-4 border-b border-zinc-200 dark:border-zinc-600">
        <nav class="flex gap-1 overflow-x-auto">
            @foreach ($tabs as $key => $tab)
                @php
                    $isActive = $activeTab === $key;
                @endphp

                <a href="{{ route($tab['route']) }}" wire:navigate @class([
                    'inline-flex items-center gap-1.5 px-3 py-2 text-sm whitespace-nowrap transition-colors duration-150',
                    'bg-brand-primary text-brand-primary-content font-medium' => $isActive,
                    'text-zinc-500 hover:text-zinc-800 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:text-zinc-200 dark:hover:bg-zinc-800' => !$isActive,
                ])>
                    <flux:icon :name="$tab['icon']" class="size-4 shrink-0" variant="outline" />
                    {{ $tab['label'] }}
                </a>
            @endforeach
        </nav>
    </div>

    {{-- Body: sub-nav + content --}}
    <div class="flex items-start gap-8 mt-6 max-md:flex-col">

        {{-- Left sub-nav (only show if not overview) --}}
        @if ($activeTab !== 'overview' && !empty($subnavs[$activeTab]))
            <div class="w-full md:w-[200px] shrink-0">
                <flux:navlist aria-label="{{ __('Logistics navigation') }}">
                    @foreach ($subnavs[$activeTab] as $item)
                        @if (($item['type'] ?? null) === 'separator')
                            <flux:separator class="my-2" />
                        @elseif (($item['type'] ?? null) === 'heading')
                            <div class="px-3 py-2 text-xs font-semibold text-zinc-400 uppercase tracking-wider">
                                {{ $item['label'] }}
                            </div>
                        @else
                            <flux:navlist.item :href="route($item['route'])" :current="$currentRoute === $item['route']"
                                wire:navigate @class([
                                    'pl-6' => $item['indent'] ?? false,
                                ])>
                                {{ $item['label'] }}
                            </flux:navlist.item>
                        @endif
                    @endforeach
                </flux:navlist>
            </div>

            <flux:separator class="md:hidden" />
        @endif

        {{-- Main content --}}
        <div @class(['flex-1 min-w-0', 'w-full' => $activeTab === 'overview'])>
            @if ($heading || $subheading)
                <div class="flex items-start justify-between mb-5">
                    <div>
                        @if ($heading)
                            <flux:heading size="lg">{{ $heading }}</flux:heading>
                        @endif
                        @if ($subheading)
                            <flux:subheading>{{ $subheading }}</flux:subheading>
                        @endif
                    </div>
                    @if (isset($actions) && !$actions->isEmpty())
                        <div class="flex items-center gap-2 shrink-0">{{ $actions }}</div>
                    @endif
                </div>
            @endif

            {{ $slot }}
        </div>

    </div>
</div>
