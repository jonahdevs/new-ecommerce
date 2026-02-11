<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="bg-zinc-50">
        {{ $slot }}
    </flux:main>

    <x-toast-notification />
</x-layouts::app.sidebar>
