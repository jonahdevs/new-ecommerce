{{-- Linked Products --}}
<div wire:cloak wire:show="activeTab == 'linked-products'" class="space-y-5 p-5">

    {{-- ================================================ --}}
    {{-- GROUPED PRODUCTS                                 --}}
    {{-- ================================================ --}}
    <div wire:cloak wire:show="form.type === 'grouped'" class="space-y-4">

        <flux:field>
            <flux:label>Grouped Products</flux:label>
            <div class="flex gap-2">
                <div class="flex-1">
                    <x-my-choices-offline wire:model.live="selectedGroupedProducts" :options="$this->products"
                        placeholder="Search and select products..." option-sub-label="sku" option-avatar="image_url"
                        searchable clearable />
                </div>
                <flux:button type="button" icon="plus" wire:click="addGroupedProducts"
                    :disabled="empty($selectedGroupedProducts)" class="cursor-pointer disabled:cursor-not-allowed">
                    Add
                </flux:button>
            </div>
        </flux:field>

        @if (!empty($groupedProducts))
            <div class="rounded-md border dark:border-zinc-700 overflow-hidden">

                <div
                    class="hidden sm:grid grid-cols-12 gap-3 px-4 py-2 bg-zinc-50 dark:bg-zinc-800 text-xs font-medium text-zinc-500 uppercase tracking-wide border-b dark:border-zinc-700">
                    <div class="col-span-5">Product</div>
                    <div class="col-span-2 text-center">Qty</div>
                    <div class="col-span-2 text-right">Unit Price</div>
                    <div class="col-span-2 text-right">Subtotal</div>
                    <div class="col-span-1"></div>
                </div>

                <div class="divide-y dark:divide-zinc-700">
                    @foreach ($groupedProducts as $index => $item)
                        {{-- Desktop row --}}
                        <div class="hidden sm:grid grid-cols-12 gap-3 px-4 py-3 items-center"
                            wire:key="grouped-desktop-{{ $index }}">
                            <div class="col-span-5">
                                <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $item['name'] }}</p>
                                <p class="text-xs text-zinc-400 mt-0.5">{{ $item['sku'] }}</p>
                            </div>
                            <div class="col-span-2 flex justify-center">
                                <flux:input type="number" min="1"
                                    wire:model="groupedProducts.{{ $index }}.quantity"
                                    class="text-center w-20!" />
                            </div>
                            <div class="col-span-2 text-right text-sm text-zinc-500 dark:text-zinc-400">
                                KES {{ number_format($item['price'] ?? 0, 2) }}
                            </div>
                            <div class="col-span-2 text-right text-sm font-medium text-zinc-800 dark:text-zinc-100">
                                KES {{ number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 1), 2) }}
                            </div>
                            <div class="col-span-1 flex justify-end">
                                <button type="button" wire:click="removeGroupedProduct({{ $index }})"
                                    wire:confirm="Remove this item from the kit?"
                                    class="text-zinc-400 hover:text-red-500 transition-colors">
                                    <flux:icon.x-mark class="size-4" />
                                </button>
                            </div>
                        </div>

                        {{-- Mobile card --}}
                        <div class="sm:hidden px-4 py-3 space-y-2" wire:key="grouped-mobile-{{ $index }}">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $item['name'] }}
                                    </p>
                                    <p class="text-xs text-zinc-400 mt-0.5">{{ $item['sku'] }}</p>
                                </div>
                                <button type="button" wire:click="removeGroupedProduct({{ $index }})"
                                    wire:confirm="Remove this item from the kit?"
                                    class="text-zinc-400 hover:text-red-500 transition-colors shrink-0">
                                    <flux:icon.x-mark class="size-4" />
                                </button>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-zinc-500">Qty</span>
                                    <flux:input type="number" min="1"
                                        wire:model="groupedProducts.{{ $index }}.quantity"
                                        class="text-center w-20" />
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-zinc-400">KES {{ number_format($item['price'] ?? 0, 2) }}
                                        each</p>
                                    <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">
                                        KES {{ number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 1), 2) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div
                    class="grid grid-cols-12 gap-3 px-4 py-3 bg-zinc-50 dark:bg-zinc-800 border-t dark:border-zinc-700">
                    <div class="col-span-9 text-sm font-medium text-zinc-500 text-right">Kit Total</div>
                    <div class="col-span-2 text-right text-sm font-bold text-zinc-800 dark:text-zinc-100">
                        KES {{ number_format($this->getGroupedTotal(), 2) }}
                    </div>
                    <div class="col-span-1"></div>
                </div>
            </div>
        @else
            <div
                class="text-center py-8 text-zinc-400 border-2 border-dashed border-zinc-200 dark:border-zinc-700 rounded-md">
                <flux:icon.squares-plus class="size-10 mx-auto mb-2 opacity-40" />
                <p class="text-sm font-medium">No items in kit yet</p>
                <p class="text-xs mt-1">Search and select products above then click Add</p>
            </div>
        @endif
    </div>

    {{-- ================================================ --}}
    {{-- UPSELLS                                          --}}
    {{-- ================================================ --}}
    <flux:field>
        <flux:label>Upsells</flux:label>
        <flux:description class="text-xs">Higher-end alternatives to suggest to the customer.</flux:description>
        <x-my-choices-offline wire:model="form.selected_upsells" placeholder="Select products for upsells"
            :options="$this->products" option-sub-label="sku" option-avatar="image_url" clearable searchable />
        <flux:error name="form.selected_upsells" />
    </flux:field>

    {{-- ================================================ --}}
    {{-- CROSS-SELLS — hidden for grouped                 --}}
    {{-- ================================================ --}}
    <div wire:cloak wire:show="form.type !== 'grouped'">
        <flux:field>
            <flux:label>Cross-Sells</flux:label>
            <flux:description class="text-xs">Related products suggested in the cart.</flux:description>
            <x-my-choices-offline wire:model="form.selected_cross_sells" :options="$this->products"
                placeholder="Select products for cross-sells" option-sub-label="sku" option-avatar="image_url" clearable
                searchable />
            <flux:error name="form.selected_cross_sells" />
        </flux:field>
    </div>

    {{-- ================================================ --}}
    {{-- ACCESSORIES — hidden for grouped                 --}}
    {{-- ================================================ --}}
    <div wire:cloak wire:show="form.type !== 'grouped'" class="space-y-4">

        <flux:field>
            <flux:label>Accessories</flux:label>
            <flux:description class="text-xs">
                Products that work with or are required for this product. Set the recommended quantity for each.
            </flux:description>
            <div class="flex gap-2">
                <div class="flex-1">
                    <x-my-choices-offline wire:model.live="selectedAccessories" :options="$this->products"
                        placeholder="Search and select accessories..." option-sub-label="sku" option-avatar="image_url"
                        searchable clearable />
                </div>
                <flux:button type="button" icon="plus" wire:click="addAccessories"
                    :disabled="empty($selectedAccessories)" class="cursor-pointer disabled:cursor-not-allowed">
                    Add
                </flux:button>
            </div>
            <flux:error name="form.accessories" />
        </flux:field>

        @if (!empty($accessories))
            <div class="rounded-md border dark:border-zinc-700 overflow-hidden">

                {{-- Header — hidden on mobile --}}
                <div
                    class="hidden sm:grid grid-cols-12 gap-3 px-4 py-2 bg-zinc-50 dark:bg-zinc-800 text-xs font-medium text-zinc-500 uppercase tracking-wide border-b dark:border-zinc-700">
                    <div class="col-span-6">Product</div>
                    <div class="col-span-3 text-center">Recommended Qty</div>
                    <div class="col-span-2 text-right">Unit Price</div>
                    <div class="col-span-1"></div>
                </div>

                <div class="divide-y dark:divide-zinc-700">
                    @foreach ($accessories as $index => $item)
                        {{-- Desktop row --}}
                        <div class="hidden sm:grid grid-cols-12 gap-3 px-4 py-3 items-center"
                            wire:key="accessory-desktop-{{ $index }}">
                            <div class="col-span-6">
                                <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $item['name'] }}
                                </p>
                                <p class="text-xs text-zinc-400 mt-0.5">{{ $item['sku'] }}</p>
                            </div>
                            <div class="col-span-3 flex justify-center">
                                <flux:input type="number" min="1"
                                    wire:model="accessories.{{ $index }}.quantity"
                                    class="text-center w-20!" />
                            </div>
                            <div class="col-span-2 text-right text-sm text-zinc-500 dark:text-zinc-400">
                                KES {{ number_format($item['price'] ?? 0, 2) }}
                            </div>
                            <div class="col-span-1 flex justify-end">
                                <button type="button" wire:click="removeAccessory({{ $index }})"
                                    wire:confirm="Remove this accessory?"
                                    class="text-zinc-400 hover:text-red-500 transition-colors cursor-pointer">
                                    <flux:icon.x-mark class="size-4" />
                                </button>
                            </div>
                        </div>

                        {{-- Mobile card --}}
                        <div class="sm:hidden px-4 py-3 space-y-2" wire:key="accessory-mobile-{{ $index }}">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">
                                        {{ $item['name'] }}</p>
                                    <p class="text-xs text-zinc-400 mt-0.5">{{ $item['sku'] }}</p>
                                </div>
                                <button type="button" wire:click="removeAccessory({{ $index }})"
                                    wire:confirm="Remove this accessory?"
                                    class="text-zinc-400 hover:text-red-500 transition-colors shrink-0">
                                    <flux:icon.x-mark class="size-4" />
                                </button>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-zinc-500">Recommended Qty</span>
                                    <flux:input type="number" min="1"
                                        wire:model="accessories.{{ $index }}.quantity"
                                        class="text-center w-20" />
                                </div>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                    KES {{ number_format($item['price'] ?? 0, 2) }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div
                class="text-center py-8 text-zinc-400 border-2 border-dashed border-zinc-200 dark:border-zinc-700 rounded-md">
                <flux:icon.puzzle-piece class="size-10 mx-auto mb-2 opacity-40" />
                <p class="text-sm font-medium">No accessories added yet</p>
                <p class="text-xs mt-1">Search and select accessories above then click Add</p>
            </div>
        @endif
    </div>

</div>
