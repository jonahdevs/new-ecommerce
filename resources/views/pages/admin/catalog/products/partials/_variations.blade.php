@php
    $regionalSettings = app(\App\Settings\RegionalSettings::class);
    $weightUnit = $regionalSettings->weight_unit;
    $dimensionUnit = $regionalSettings->dimension_unit;
@endphp
<div x-data="{
    allCollapsed: false,
    init() {
        this.$watch('allCollapsed', (value) => {
            this.$dispatch('toggle-all-variants', { collapsed: value })
        })
    }
}">
    {{-- Toolbar --}}
    {{-- Default Variation Selector --}}
    @if ($form->type === 'variable' && !empty($variants) && !empty($availableAttributes))
        <div class="flex flex-wrap items-center gap-4 border-b px-4 py-2">
            <flux:label>Default Variation</flux:label>
            @foreach ($availableAttributes as $attribute)
                <flux:select size="sm" class="w-fit"
                    wire:model.live="defaultVariantAttributes.{{ $attribute['name'] }}">
                    <flux:select.option value="">No default {{ ucfirst($attribute['name']) }}
                    </flux:select.option>
                    @foreach ($attribute['values'] as $valueId)
                        @php
                            $val = collect(
                                $this->productAttributeValueOptions[$attribute['attribute_id']] ?? [],
                            )->firstWhere('id', $valueId);
                        @endphp
                        @if ($val)
                            <flux:select.option value="{{ $val['name'] }}">
                                {{ $val['name'] }}
                            </flux:select.option>
                        @endif
                    @endforeach
                </flux:select>
            @endforeach
        </div>
    @endif

    <section class="p-5 space-y-5">

        <div class="flex gap-2 items-center">
            <div class="flex items-center gap-3">
                {{-- Regenerate — only shown when variants already exist --}}
                @if (!empty($variants))
                    <flux:button size="sm" type="button" icon="arrow-path" wire:click="regenerateVariations"
                        wire:confirm="This will add any missing variation combinations based on your current attributes. Existing variations will not be changed. Continue?"
                        class="cursor-pointer">
                        Regenerate
                    </flux:button>
                @else
                    <flux:button size="sm" type="button" wire:click="generateVariations" icon="sparkles"
                        class="cursor-pointer">
                        Generate Variations
                    </flux:button>
                @endif

                <flux:button size="sm" type="button" wire:click="addVariant" icon="plus"
                    class="cursor-pointer">
                    Add Manual
                </flux:button>

                @if (!empty($variants))
                    <flux:dropdown>
                        <flux:button size="sm" icon:trailing="chevron-down" class="cursor-pointer">Bulk Actions
                        </flux:button>
                        <flux:menu class="min-w-32">
                            <flux:menu.group heading="Status">
                                <flux:menu.item wire:click="toggleAllVariantsActive">
                                    Toggle "Active"
                                </flux:menu.item>
                            </flux:menu.group>
                            <flux:menu.group heading="Pricing">
                                <flux:menu.item @click="$flux.modal('bulk-pricing').show()">
                                    Set prices...
                                </flux:menu.item>
                            </flux:menu.group>
                            <flux:menu.group heading="Inventory">
                                <flux:menu.item wire:click="toggleAllVariantsManageStock">
                                    Toggle "Manage stock"
                                </flux:menu.item>
                                <flux:menu.item @click="$flux.modal('bulk-stock').show()">
                                    Set stock quantity...
                                </flux:menu.item>
                                <flux:menu.item wire:click="setAllVariantsStockStatus('in_stock')">
                                    Set Status - In stock
                                </flux:menu.item>
                                <flux:menu.item wire:click="setAllVariantsStockStatus('out_of_stock')">
                                    Set Status - Out of stock
                                </flux:menu.item>
                                <flux:menu.item wire:click="setAllVariantsStockStatus('backorder')">
                                    Set Status - On Backorder
                                </flux:menu.item>
                            </flux:menu.group>
                            <flux:menu.group heading="Shipping">
                                <flux:menu.item @click="$flux.modal('bulk-dimensions').show()">
                                    Set dimensions & weight...
                                </flux:menu.item>
                            </flux:menu.group>
                            <flux:menu.group heading="Danger">
                                <flux:menu.item wire:click="clearAllVariants"
                                    wire:confirm="Delete all variations? This cannot be undone." variant="danger">
                                    Delete all variations
                                </flux:menu.item>
                            </flux:menu.group>
                        </flux:menu>
                    </flux:dropdown>
                @endif
            </div>

            @if (!empty($variants))
                <div class="ms-auto flex items-center gap-1.5 text-xs">
                    <span>{{ count($variants) }} variation(s)</span>
                    (
                    <button type="button" @click="allCollapsed = true"
                        class="text-blue-500 italic cursor-pointer">Expand
                        all</button>
                    /
                    <button type="button" @click="allCollapsed = false"
                        class="text-blue-500 italic cursor-pointer">Collapse all</button>
                    )
                </div>
            @endif
        </div>

        @if ($this->unpricedVariantsCount > 0)
            {{-- <div
                class="flex items-start justify-between gap-4 bg-amber-50 border border-amber-200 rounded-md px-4 py-3">
                <div class="flex items-start gap-2">
                    <flux:icon.exclamation-triangle class="size-5 shrink-0 mt-0.5 text-amber-500" />
                    <div>
                        <p class="text-sm font-medium text-amber-800">
                            {{ $this->unpricedVariantsCount }}
                            variation{{ $this->unpricedVariantsCount > 1 ? 's do' : ' does' }} not have a price.
                        </p>
                    </div>
                </div>
                <flux:button size="sm" variant="filled" @click="$flux.modal('bulk-pricing').show()"
                    class="shrink-0 cursor-pointer">
                    Set prices
                </flux:button>
            </div> --}}

            <flux:callout variant="warning" icon="exclamation-circle" inline>
                <flux:callout.heading>{{ $this->unpricedVariantsCount }}
                    variation{{ $this->unpricedVariantsCount > 1 ? 's do' : ' does' }} not have a price.
                </flux:callout.heading>
                <x-slot name="actions">
                    <flux:button size="sm" @click="$flux.modal('bulk-pricing').show()" class="cursor-pointer">
                        Set prices
                    </flux:button>
                </x-slot>
            </flux:callout>
        @endif

        {{-- Variations List --}}
        @if (!empty($variants))
            <div class="space-y-4">
                @foreach ($variants as $index => $variant)
                    <flux:card class="p-0">
                        <div wire:key="variant-{{ $index }}-{{ $variant['attribute_hash'] ?? $index }}"
                            x-data="{
                                collapsed: {{ $loop->first ? 'true' : 'false' }},
                                readonlyName: @js(!empty($variant['name']))
                            }" @toggle-all-variants.window="collapsed = $event.detail.collapsed">

                            {{-- Variant Header --}}
                            <div class="flex items-center justify-between px-4 py-2"
                                :class="{ 'border-b pb-1 mb-3': collapsed }">
                                <flux:heading>
                                    {{ !empty($variant['attributes'])
                                        ? implode(' - ', array_values($variant['attributes']))
                                        : 'Manual Variation #' . ($index + 1) }}
                                </flux:heading>

                                <div class="flex items-center gap-3 text-sm">
                                    <flux:button size="xs" icon="trash" icon-variant="outline" type="button"
                                        variant="ghost" wire:click="removeVariant({{ $index }})"
                                        wire:confirm="Remove this attribute?" class="text-red-500! cursor-pointer" />

                                    <flux:button icon="chevron-down" size="xs" variant="ghost" type="button"
                                        class="transition-transform duration-300 cursor-pointer"
                                        x-bind:class="{ 'rotate-180': collapsed }" @click="collapsed = !collapsed" />
                                </div>
                            </div>

                            {{-- Variant Body --}}
                            <div x-cloak x-show="collapsed" x-collapse class="space-y-5 p-5">

                                <div class="grid grid-cols-2 gap-5">
                                    {{-- Variant Image --}}
                                    <div class="space-y-2 flex flex-col">
                                        {{-- <flux:label>Variation Image</flux:label> --}}

                                        <input type="file" class="hidden" id="variant-image-{{ $index }}"
                                            wire:model="variantImages.{{ $index }}" accept="image/*" />

                                        {{-- Loading --}}
                                        <flux:skeleton wire:loading wire:target="variantImages.{{ $index }}"
                                            class="w-24 h-24" animate="shimmer" />

                                        <div wire:loading.remove wire:target="variantImages.{{ $index }}">
                                            @if (!empty($variantImages[$index] ?? null))
                                                {{-- New upload preview --}}
                                                <div class="relative w-24 h-24 rounded-md overflow-hidden group cursor-pointer"
                                                    @click="document.getElementById('variant-image-{{ $index }}').click()">
                                                    <img src="{{ $variantImages[$index]->temporaryUrl() }}"
                                                        class="w-full h-full object-cover" alt="Variant image">
                                                    <div
                                                        class="absolute inset-0 group-hover:bg-black/40 transition-all flex items-center justify-center">
                                                        <flux:icon.pencil
                                                            class="opacity-0 group-hover:opacity-100 text-white size-4" />
                                                    </div>

                                                    @if (!empty($variant['id']))
                                                        <div class="absolute top-0 left-0">
                                                            <flux:badge color="green" size="sm" variant="solid"
                                                                class="rounded-tl-md rounded-br-md rounded-none">New
                                                            </flux:badge>
                                                        </div>
                                                    @endif
                                                </div>
                                            @elseif (!empty($variant['image_path']))
                                                {{-- Existing saved image --}}
                                                <div class="relative w-24 h-24 rounded-md overflow-hidden border-2 border-zinc-200 group cursor-pointer"
                                                    @click="document.getElementById('variant-image-{{ $index }}').click()">
                                                    <img src="{{ Storage::url($variant['image_path']) }}"
                                                        class="w-full h-full object-cover" alt="Variant image">
                                                    <div
                                                        class="absolute inset-0 group-hover:bg-black/40 transition-all flex items-center justify-center">
                                                        <flux:icon.pencil
                                                            class="opacity-0 group-hover:opacity-100 text-white size-4" />
                                                    </div>
                                                </div>
                                            @else
                                                {{-- No image --}}
                                                <div class="flex items-center gap-3">
                                                    <button type="button"
                                                        @click="document.getElementById('variant-image-{{ $index }}').click()"
                                                        class="w-24 h-24 rounded-md border-2 border-dashed border-zinc-300 dark:border-zinc-600 flex flex-col items-center justify-center gap-1 hover:border-brand-secondary hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-all cursor-pointer">
                                                        <flux:icon.photo class="size-6 text-zinc-400" />
                                                        <span class="text-xs text-zinc-400">Add image</span>
                                                    </button>
                                                </div>
                                            @endif

                                            {{-- Remove image link --}}
                                            @if (!empty($variantImages[$index] ?? null) || !empty($variant['image_path']))
                                                <flux:link wire:click="removeVariantImage({{ $index }})"
                                                    class="text-xs text-red-500 cursor-pointer mt-1 block">
                                                    Remove image
                                                </flux:link>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- SKU --}}
                                    <flux:input wire:model="variants.{{ $index }}.sku" label="SKU"
                                        placeholder="Leave blank to auto-generate" />
                                </div>

                                {{-- Flags --}}
                                <div class="flex items-center gap-4 border-y py-3">
                                    <flux:checkbox wire:model="variants.{{ $index }}.is_active"
                                        label="Active" />

                                    <flux:checkbox wire:model="variants.{{ $index }}.manage_stock"
                                        label="Manage Stock" />
                                </div>

                                {{-- Variation Name --}}
                                <flux:input wire:model="variants.{{ $index }}.name" ::readonly="readonlyName"
                                    label="Variation Name">
                                    <x-slot name="iconTrailing">
                                        <flux:button size="sm" variant="subtle"
                                            @click="readonlyName = !readonlyName" icon="pencil"
                                            icon-variant="outline" class="-mr-1 cursor-pointer" />
                                    </x-slot>
                                </flux:input>

                                {{-- Pricing --}}
                                <div class="grid grid-cols-2 gap-3">
                                    <flux:input type="number" step="0.01"
                                        wire:model="variants.{{ $index }}.price"
                                        label="Regular Price (KES)" />
                                    <flux:input type="number" step="0.01"
                                        wire:model="variants.{{ $index }}.sale_price"
                                        label="Sale Price (KES)" />
                                </div>

                                {{-- Stock  --}}
                                <div x-show="$wire.variants[{{ $index }}].manage_stock" x-cloak
                                    class="space-y-3">
                                    <div class="grid grid-cols-2 gap-3">
                                        <flux:input type="number"
                                            wire:model="variants.{{ $index }}.stock_quantity"
                                            label="Stock Quantity" min="0" />
                                        <flux:select label="Allow Backorders"
                                            wire:model="variants.{{ $index }}.allow_backorders">
                                            <flux:select.option value="">Do not allow</flux:select.option>
                                            <flux:select.option value="1">Allow</flux:select.option>
                                        </flux:select>
                                    </div>

                                    {{-- Backorder Settings — shown when backorders are allowed --}}
                                    <div x-show="$wire.variants[{{ $index }}].allow_backorders == '1'" x-cloak
                                        class="space-y-3">
                                        <flux:separator />

                                        <flux:textarea wire:model="variants.{{ $index }}.backorder_message"
                                            label="Backorder Message" rows="2" />

                                        <div class="grid grid-cols-2 gap-3">
                                            <flux:input type="number" min="1"
                                                wire:model="variants.{{ $index }}.max_backorder_quantity"
                                                label="Max Backorder Quantity"
                                                placeholder="Leave blank for unlimited" />

                                            <flux:input type="date"
                                                wire:model="variants.{{ $index }}.expected_restock_date"
                                                label="Expected Restock Date" />
                                        </div>
                                    </div>

                                    <flux:input wire:model="variants.{{ $index }}.low_stock_threshold"
                                        label="Low Stock Threshold" type="number" min="0" />

                                </div>

                                {{-- Stock status when manage_stock is OFF --}}
                                <div x-show="!$wire.variants[{{ $index }}].manage_stock" x-cloak>
                                    <flux:select wire:model="variants.{{ $index }}.stock_status"
                                        label="Stock Status">
                                        <flux:select.option value="in_stock">In Stock</flux:select.option>
                                        <flux:select.option value="out_of_stock">Out of Stock</flux:select.option>
                                        <flux:select.option value="backorder">Backorder</flux:select.option>
                                    </flux:select>
                                </div>

                                {{-- Shipping --}}
                                <div class="grid grid-cols-2 gap-3">
                                    <flux:input label="Weight ({{ $weightUnit }})" type="number" step="0.01"
                                        wire:model="variants.{{ $index }}.weight" />
                                    <flux:field>
                                        <flux:label>Dimensions - L x W x H ({{ $dimensionUnit }})</flux:label>
                                        <flux:input.group>
                                            <flux:input placeholder="Length"
                                                wire:model="variants.{{ $index }}.length" />
                                            <flux:input placeholder="Width"
                                                wire:model="variants.{{ $index }}.width" />
                                            <flux:input placeholder="Height"
                                                wire:model="variants.{{ $index }}.height" />
                                        </flux:input.group>
                                    </flux:field>
                                </div>

                                {{-- Description --}}
                                <flux:textarea wire:model="variants.{{ $index }}.description"
                                    label="Description" rows="2" />

                                <div wire:show="form.is_downloadable" wire:cloak>
                                    <flux:separator />

                                    <div class="space-y-3">
                                        <div>
                                            <flux:heading size="sm">Downloadable Files</flux:heading>
                                            <flux:subheading class="text-xs">
                                                Files the customer receives after purchasing this variation.
                                                Leave empty to use the product-level files.
                                            </flux:subheading>
                                        </div>

                                        @if (!empty($variants[$index]['downloads']))
                                            <div class="rounded-md border dark:border-zinc-700 overflow-hidden">

                                                {{-- Header --}}
                                                <div
                                                    class="grid grid-cols-12 gap-3 px-4 py-2 bg-zinc-50 dark:bg-zinc-800 text-xs font-medium text-zinc-500 uppercase tracking-wide border-b dark:border-zinc-700">
                                                    <div class="col-span-4">Name</div>
                                                    <div class="col-span-5">File</div>
                                                    <div class="col-span-3 text-center">Actions</div>
                                                </div>

                                                <div class="divide-y dark:divide-zinc-700">
                                                    @foreach ($variants[$index]['downloads'] as $downloadIndex => $download)
                                                        <div class="grid grid-cols-12 gap-3 px-4 py-3 items-center"
                                                            wire:key="variant-{{ $index }}-download-{{ $downloadIndex }}">

                                                            {{-- Name --}}
                                                            <div class="col-span-4">
                                                                <flux:input
                                                                    wire:model="variants.{{ $index }}.downloads.{{ $downloadIndex }}.name"
                                                                    placeholder="e.g. License File" />
                                                            </div>

                                                            {{-- File --}}
                                                            <div class="col-span-5">
                                                                <flux:icon.loading wire:loading
                                                                    wire:target="variants.{{ $index }}.downloads.{{ $downloadIndex }}.file"
                                                                    class="size-4" />

                                                                <div wire:loading.remove
                                                                    wire:target="variants.{{ $index }}.downloads.{{ $downloadIndex }}.file">
                                                                    @if (!empty($download['file']))
                                                                        <div class="flex items-center gap-1.5">
                                                                            <flux:icon.check-circle
                                                                                class="size-4 text-green-500 shrink-0" />
                                                                            <span
                                                                                class="text-sm text-zinc-600 dark:text-zinc-300 truncate">
                                                                                {{ is_object($download['file'])
                                                                                    ? $download['file']->getClientOriginalName()
                                                                                    : $download['file_name'] ?? 'Uploaded file' }}
                                                                            </span>
                                                                        </div>
                                                                    @elseif (!empty($download['file_path']))
                                                                        <div class="flex items-center gap-1.5">
                                                                            <flux:icon.paper-clip
                                                                                class="size-4 text-zinc-400 shrink-0" />
                                                                            <span
                                                                                class="text-sm text-zinc-600 dark:text-zinc-300 truncate">
                                                                                {{ $download['file_name'] ?? 'Existing file' }}
                                                                            </span>
                                                                            @if (!empty($download['formatted_file_size']))
                                                                                <span
                                                                                    class="text-xs text-zinc-400 shrink-0">
                                                                                    {{ $download['formatted_file_size'] }}
                                                                                </span>
                                                                            @endif
                                                                        </div>
                                                                    @else
                                                                        <span class="text-sm text-zinc-400 italic">No
                                                                            file
                                                                            chosen</span>
                                                                    @endif
                                                                </div>
                                                            </div>

                                                            {{-- Actions --}}
                                                            <div
                                                                class="col-span-3 flex items-center justify-center gap-2">
                                                                <label
                                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs border border-zinc-300 dark:border-zinc-600 rounded-md text-zinc-600 dark:text-zinc-300 hover:border-brand-secondary hover:text-brand-secondary cursor-pointer transition-colors">
                                                                    <flux:icon.arrow-up-tray class="size-3.5" />
                                                                    {{ !empty($download['file']) || !empty($download['file_path']) ? 'Replace' : 'Choose File' }}
                                                                    <input type="file" class="hidden"
                                                                        wire:model="variants.{{ $index }}.downloads.{{ $downloadIndex }}.file" />
                                                                </label>

                                                                <button type="button"
                                                                    wire:click="removeVariantDownloadFile({{ $index }}, {{ $downloadIndex }})"
                                                                    wire:confirm="Remove this download file?"
                                                                    class="text-zinc-400 hover:text-red-500 transition-colors cursor-pointer">
                                                                    <flux:icon.x-mark class="size-4" />
                                                                </button>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        <flux:button type="button" size="sm"
                                            wire:click="addVariantDownloadFile({{ $index }})"
                                            class="cursor-pointer font-normal!">
                                            Add File
                                        </flux:button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </flux:card>
                @endforeach
            </div>
        @else
            {{-- Empty State --}}
            <div class="text-center py-10 text-zinc-500">
                <flux:icon.cube class="size-12 mx-auto mb-3 opacity-40" />
                <p class="font-medium">No variations yet</p>
                <p class="text-sm mt-1">Select attributes marked as "Used for variations" then click
                    Generate, or add manually.</p>
            </div>
        @endif
    </section>
</div>
