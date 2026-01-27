<x-layouts::guest>

    <!-- Breadcrumbs -->
    <div class=" px-4 py-3 bg-zinc-100">
        <flux:breadcrumbs class="container mx-auto">
            <flux:breadcrumbs.item href="{{ route('home') }}">
                <flux:icon.home class="w-4 h-4 me-1.5 inline-block" />
                Home
            </flux:breadcrumbs.item>

            @if (isset($title) && $title)
                <flux:breadcrumbs.item>{{ $title }}</flux:breadcrumbs.item>
            @endif
        </flux:breadcrumbs>
    </div>

    <!-- Main Content -->
    <main class="flex-1 container mx-auto px-4 py-12 min-h-[77svh] flex items-center">
        <div class="flex w-full max-w-md mx-auto flex-col gap-6 border p-6 bg-white shadow-sm rounded-sm">
            {{ $slot }}
        </div>
    </main>
</x-layouts::guest>
