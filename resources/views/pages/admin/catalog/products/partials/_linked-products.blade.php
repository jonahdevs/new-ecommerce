{{-- Linked Products --}}
<div wire:cloak wire:show="activeTab == 'linked-products'" class="space-y-5">

    {{-- ================================================ --}}
    {{-- KIT ITEMS — only for grouped products            --}}
    {{-- ================================================ --}}
    <div wire:cloak wire:show="form.type === 'grouped'" class="space-y-4">
        <flux:card class="p-0">
            <div class="px-4 py-3 border-b dark:border-zinc-700">
                <flux:heading>Kit Items</flux:heading>
                <flux:subheading>Products included in this kit/bundle</flux:subheading>
            </div>

            <div class="p-4 space-y-4">
                {{-- Search & Add --}}
                <div class="flex gap-2">
                    <div class="flex-1">
                        <x-my-choices-offline wire:model="selectedGroupedProduct" :options="$this->products"
                            placeholder="Search and add a product to kit..." option-sub-label="sku" single
                            option-avatar="image_url" searchable clearable />
                    </div>
                    <flux:button type="button" icon="plus"
                        wire:click="addGroupedProduct({{ $selectedGroupedProduct ?? 'null' }})"
                        :disabled="!$selectedGroupedProduct" class="cursor-pointer disabled:cursor-none">
                        Add
                    </flux:button>
                </div>

                {{-- Kit Items List --}}
                @if (!empty($groupedProducts))
                    <div class="rounded-md border dark:border-zinc-700 divide-y dark:divide-zinc-700">

                        {{-- Header --}}
                        <div
                            class="grid grid-cols-12 gap-3 px-4 py-2 bg-zinc-50 dark:bg-zinc-800 text-xs font-medium text-zinc-500 uppercase tracking-wide">
                            <div class="col-span-5">Product</div>
                            <div class="col-span-2 text-center">Qty</div>
                            <div class="col-span-3 text-right">Unit Price</div>
                            <div class="col-span-1 text-right">Subtotal</div>
                            <div class="col-span-1"></div>
                        </div>

                        {{-- Items --}}
                        @foreach ($groupedProducts as $index => $item)
                            <div class="grid grid-cols-12 gap-3 px-4 py-3 items-center"
                                wire:key="grouped-{{ $index }}">

                                {{-- Product Info --}}
                                <div class="col-span-5">
                                    <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">
                                        {{ $item['name'] }}
                                    </p>
                                    <p class="text-xs text-zinc-400 mt-0.5">{{ $item['sku'] }}</p>
                                </div>

                                {{-- Quantity --}}
                                <div class="col-span-2 flex items-center justify-center">
                                    <flux:input type="number" min="1"
                                        wire:model="groupedProducts.{{ $index }}.quantity"
                                        class="text-center w-16" />
                                </div>

                                {{-- Unit Price --}}
                                <div class="col-span-3 text-right text-sm text-zinc-600 dark:text-zinc-300">
                                    KES {{ number_format($item['price'] ?? 0, 2) }}
                                </div>

                                {{-- Subtotal --}}
                                <div class="col-span-1 text-right text-sm font-medium text-zinc-800 dark:text-zinc-100">
                                    KES {{ number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 1), 2) }}
                                </div>

                                {{-- Remove --}}
                                <div class="col-span-1 flex justify-end">
                                    <flux:button type="button" size="xs" variant="ghost" icon="trash"
                                        icon-variant="outline" class="text-red-500!"
                                        wire:click="removeGroupedProduct({{ $index }})"
                                        wire:confirm="Remove this item from the kit?" />
                                </div>
                            </div>
                        @endforeach

                        {{-- Kit Total --}}
                        <div class="grid grid-cols-12 gap-3 px-4 py-3 bg-zinc-50 dark:bg-zinc-800">
                            <div class="col-span-7 text-sm font-medium text-zinc-600 dark:text-zinc-300 text-right">
                                Kit Total
                            </div>
                            <div class="col-span-3 text-right text-sm font-bold text-zinc-800 dark:text-zinc-100">
                                KES {{ number_format($this->getGroupedTotal(), 2) }}
                            </div>
                            <div class="col-span-2"></div>
                        </div>
                    </div>
                @else
                    {{-- Empty State --}}
                    <div
                        class="text-center py-8 text-zinc-400 border-2 border-dashed border-zinc-200 dark:border-zinc-700 rounded-md">
                        <flux:icon.squares-plus class="size-10 mx-auto mb-2 opacity-40" />
                        <p class="text-sm font-medium">No items in kit yet</p>
                        <p class="text-xs mt-1">Search and add products above</p>
                    </div>
                @endif
            </div>
        </flux:card>
    </div>

    {{-- ================================================ --}}
    {{-- UPSELLS — hidden for grouped                     --}}
    {{-- ================================================ --}}
    <div wire:cloak wire:show="form.type !== 'grouped'">
        <flux:field>
            <flux:label>Upsells</flux:label>
            <flux:subheading>Suggest better/upgraded alternatives on the product page</flux:subheading>
            <x-my-choices-offline wire:model="form.selected_upsells" placeholder="Select products for upsells"
                :options="$this->products" option-sub-label="sku" option-avatar="image_url" clearable searchable />
            <flux:error name="form.selected_upsells" />
        </flux:field>
    </div>

    {{-- ================================================ --}}
    {{-- CROSS-SELLS — hidden for grouped                 --}}
    {{-- ================================================ --}}
    <div wire:cloak wire:show="form.type !== 'grouped'">
        <flux:field>
            <flux:label>Cross-Sells</flux:label>
            <flux:subheading>Suggest complementary products (e.g. accessories, add-ons)</flux:subheading>
            <x-my-choices-offline wire:model="form.selected_cross_sells" :options="$this->products"
                placeholder="Select products for cross-sells (e.g. Accessories)" option-sub-label="sku"
                option-avatar="image_url" clearable searchable />
            <flux:error name="form.selected_cross_sells" />
        </flux:field>
    </div>

</div>
