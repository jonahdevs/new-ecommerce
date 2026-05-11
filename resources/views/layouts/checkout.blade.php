<x-layouts::guest>
    <div class="bg-white border-b border-zinc-200 py-3">
        {{ $breadcrumbs ?? '' }}
    </div>

    <div class="mx-auto container px-4 py-4 min-h-[80svh]">
        <flux:heading level="1" class="text-2xl! font-bold! font-serif">
            {{ $heading ?? 'Checkout' }}
        </flux:heading>

        <div class="mt-4 flex flex-col lg:flex-row lg:items-start lg:gap-6">

            {{-- Main content area --}}
            <div class="flex-1 min-w-0">
                {{ $slot }}
            </div>

            {{-- Order summary sidebar — pages can override via <x-slot:orderSummaryCta> --}}
            <div class="w-full lg:w-96 shrink-0 mt-4 lg:mt-0 lg:sticky lg:top-28">
                <livewire:order-summary>
                    {{ $orderSummaryCta ?? '' }}
                </livewire:order-summary>
            </div>

        </div>
    </div>

</x-layouts::guest>
