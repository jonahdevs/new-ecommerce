@props([
    'sidebar' => false,
])

@if ($sidebar)
    <flux:sidebar.brand name="{{ config('app.name') }}" {{ $attributes }}>
        <x-slot name="logo"
            class="flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
            <x-app-logo-icon class="size-5 fill-current text-white dark:text-black" />
            <img src="{{ asset('favicon.png') }}" alt="logo" class="w-full h-full">
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="{{ config('app.name') }}">
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center  text-accent-foreground">
            <img src="{{ asset('favicon.png') }}" alt="logo" class="w-full h-full">
        </x-slot>
    </flux:brand>
@endif
