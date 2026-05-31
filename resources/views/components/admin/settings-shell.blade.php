@props(['tab', 'section'])

@php
    $tabs = \App\Support\AdminSettingsNav::tabs();
    $sections = $tabs[$tab]['sections'] ?? [];
@endphp

<div>
    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Settings</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $tabs[$tab]['label'] ?? 'Settings' }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <flux:heading size="xl">Settings</flux:heading>
    <flux:subheading>Manage your store configuration.</flux:subheading>

    {{-- Top-level tabs --}}
    <div class="mt-6 overflow-x-auto border-b border-zinc-200 dark:border-zinc-700">
        <nav class="-mb-px flex min-w-max gap-1">
            @foreach ($tabs as $key => $t)
                <a href="{{ route('admin.settings.' . $key) }}" wire:navigate
                    @class([
                        'inline-flex items-center gap-2 whitespace-nowrap border-b-2 px-4 py-3 text-sm font-medium transition-colors',
                        'border-brand-500 text-brand-600 dark:text-brand-400' => $tab === $key,
                        'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200' => $tab !== $key,
                    ])>
                    <flux:icon :icon="$t['icon']" variant="micro" class="size-4" />
                    {{ $t['label'] }}
                </a>
            @endforeach
        </nav>
    </div>

    {{-- Subnav + section content --}}
    <div class="mt-6 flex flex-col gap-6 lg:flex-row lg:items-start">
        <nav class="shrink-0 space-y-0.5 lg:w-56" aria-label="{{ $tabs[$tab]['label'] ?? '' }} sections">
            @foreach ($sections as $key => $s)
                <button type="button" wire:click="$set('section', '{{ $key }}')"
                    @class([
                        'flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm font-medium transition-colors',
                        'bg-brand-50 text-brand-600 dark:bg-brand-950/40 dark:text-brand-400' => $section === $key,
                        'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-200' => $section !== $key,
                    ])>
                    <flux:icon :icon="$s['icon']" variant="micro" class="size-4 shrink-0" />
                    {{ $s['label'] }}
                </button>
            @endforeach
        </nav>

        <div class="min-w-0 flex-1">
            {{ $slot }}
        </div>
    </div>
</div>
