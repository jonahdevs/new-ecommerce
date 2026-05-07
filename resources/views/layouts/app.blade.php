<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="bg-zinc-50 dark:bg-zinc-800">
        {{ $slot }}
    </flux:main>

    <x-toast-notification />

    {{-- Override Livewire's NProgress bar color for the admin layout --}}
    <style>
        [x-cloak] { display: none !important; }
        :root { --livewire-progress-bar-color: var(--primary); }
    </style>
</x-layouts::app.sidebar>
