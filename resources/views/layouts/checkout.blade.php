<x-layouts::guest>
    <div class="bg-zinc-100">
        {{ $breadcrumbs ?? '' }}
    </div>

    <div class="mx-auto container px-4 py-4 min-h-[80svh]">
        <flux:heading level="1" class="text-2xl! font-bold!">
            {{ $heading ?? 'Checkout' }}
        </flux:heading>

        <div class="mt-4 md:gap-4 lg:flex lg:items-start ">

            {{-- Main content area - each page fills this --}}
            <div class="lg:flex-1 space-y-4">
                {{ $slot }}
            </div>

            {{-- Order summary is always here, no need to repeat it --}}
            @persist('checkout_order_summary')
                <div class="w-full max-w-xs">
                    <livewire:order-summary />
                </div>
            @endpersist
        </div>
    </div>

</x-layouts::guest>
