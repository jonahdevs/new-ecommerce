@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="Sheffield" {{ $attributes }}>
        <x-slot name="logo" class="flex size-8 items-center justify-center rounded-md bg-white shadow-sm ring-1 ring-zinc-200 dark:ring-zinc-700">
            <x-app-logo-icon class="size-6" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="Sheffield" {{ $attributes }}>
        <x-slot name="logo" class="flex size-8 items-center justify-center rounded-md bg-white shadow-sm ring-1 ring-zinc-200 dark:ring-zinc-700">
            <x-app-logo-icon class="size-6" />
        </x-slot>
    </flux:brand>
@endif
