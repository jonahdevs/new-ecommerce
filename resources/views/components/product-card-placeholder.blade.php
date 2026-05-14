<flux:card class="overflow-hidden h-full p-0">
    <div class="h-full flex flex-col">
        {{-- Image --}}
        <div class="w-full aspect-square">
            <flux:skeleton animate="shimmer" class="w-full h-full rounded-none" />
        </div>

        {{-- Details --}}
        <div class="p-4 flex flex-col gap-1 h-full">
            {{-- Brand --}}
            <flux:skeleton animate="shimmer" class="h-3 rounded w-1/4" />

            {{-- Product name (2 lines) --}}
            <div class="space-y-1.5 mt-1">
                <flux:skeleton animate="shimmer" class="h-4 rounded w-full" />
                <flux:skeleton animate="shimmer" class="h-4 rounded w-3/4" />
            </div>

            {{-- Star rating --}}
            <flux:skeleton animate="shimmer" class="h-4 rounded w-24 mt-1" />

            {{-- Price (pushed to bottom) --}}
            <div class="pt-2 mt-auto">
                <flux:skeleton animate="shimmer" class="h-5 rounded w-1/3" />
            </div>
        </div>
    </div>
</flux:card>
