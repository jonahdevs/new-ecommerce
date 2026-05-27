<x-layouts::account :title="__('My Account')">
    <flux:heading size="lg" level="1">{{ __('Welcome back, :name', ['name' => auth()->user()->name]) }}</flux:heading>
    <flux:text class="mt-2">{{ __('Your recent activity and quick links live here.') }}</flux:text>

    <div class="mt-8 grid gap-4 md:grid-cols-3">
        <a href="#" class="rounded-xl border border-zinc-200 p-6 transition hover:border-zinc-300 hover:shadow-sm dark:border-zinc-800 dark:hover:border-zinc-700" wire:navigate>
            <flux:icon.shopping-bag class="size-6 text-zinc-500" />
            <flux:heading size="sm" class="mt-3">{{ __('Orders') }}</flux:heading>
            <flux:text class="mt-1 text-sm">{{ __('Track shipments and reorder.') }}</flux:text>
        </a>
        <a href="#" class="rounded-xl border border-zinc-200 p-6 transition hover:border-zinc-300 hover:shadow-sm dark:border-zinc-800 dark:hover:border-zinc-700" wire:navigate>
            <flux:icon.map-pin class="size-6 text-zinc-500" />
            <flux:heading size="sm" class="mt-3">{{ __('Addresses') }}</flux:heading>
            <flux:text class="mt-1 text-sm">{{ __('Manage delivery destinations.') }}</flux:text>
        </a>
        <a href="{{ route('profile.edit') }}" class="rounded-xl border border-zinc-200 p-6 transition hover:border-zinc-300 hover:shadow-sm dark:border-zinc-800 dark:hover:border-zinc-700" wire:navigate>
            <flux:icon.user class="size-6 text-zinc-500" />
            <flux:heading size="sm" class="mt-3">{{ __('Profile') }}</flux:heading>
            <flux:text class="mt-1 text-sm">{{ __('Update your details and password.') }}</flux:text>
        </a>
    </div>
</x-layouts::account>
