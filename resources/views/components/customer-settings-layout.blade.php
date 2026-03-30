@props([
    'heading' => '',
    'subheading' => '',
])

@php
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

    $navItems = [
        ['label' => __('Profile'), 'route' => 'customer.settings.profile', 'icon' => 'user'],
        ['label' => __('Security'), 'route' => 'customer.settings.security', 'icon' => 'shield-check'],
        ['label' => __('Preferences'), 'route' => 'customer.settings.preferences', 'icon' => 'adjustments-horizontal'],
    ];
@endphp

<div class="space-y-4">
    <flux:card class="p-0 rounded-md">
        <div class="border-b px-4 py-3">
            <flux:heading size="lg">{{ __('Settings') }}</flux:heading>
        </div>

        <div class="flex flex-col md:flex-row">
            {{-- Left nav --}}
            <div class="w-full md:w-48 shrink-0 border-b md:border-b-0 md:border-r p-4">
                <flux:navlist class="w-full">
                    @foreach ($navItems as $item)
                        <flux:navlist.item 
                            :href="route($item['route'])" 
                            :icon="$item['icon']"
                            :current="$currentRoute === $item['route']"
                            wire:navigate
                        >
                            {{ $item['label'] }}
                        </flux:navlist.item>
                    @endforeach
                </flux:navlist>
            </div>

            {{-- Content --}}
            <div class="flex-1 p-6">
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
    </flux:card>
</div>
