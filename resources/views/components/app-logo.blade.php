@props([
    'sidebar' => false,
])

@if ($sidebar)
    <flux:sidebar.brand name="{{ config('app.name') }}" {{ $attributes }}>
        <x-slot name="logo"
            class="flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
            <img src="{{ asset('favicon.png') }}" alt="logo" class="w-full h-full">
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="{{ config('app.name') }}" name-classes="text-white">
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center  text-accent-foreground">
            <img src="{{ asset('favicon.png') }}" alt="logo" class="w-full h-full">
        </x-slot>
    </flux:brand>
@endif
