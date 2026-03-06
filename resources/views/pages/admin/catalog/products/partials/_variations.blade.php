<div class="space-y-4" x-data="{
    allCollapsed: false,
    init() {
        this.$watch('allCollapsed', (value) => {
            this.$dispatch('toggle-all-variants', { collapsed: value })
        })
    }
}">
    {{-- Toolbar --}}
    <div class="flex gap-2 items-center">
        <div class="flex items-center gap-3">
            <flux:button type="button" wire:click="generateVariations" icon="sparkles">
                Generate Variations
            </flux:button>

            <flux:button type="button" wire:click="addVariant" icon="plus">
                Add Manual
            </flux:button>

            @if (!empty($variants))
                <flux:dropdown>
                    <flux:button icon:trailing="chevron-down">Bulk Actions</flux:button>
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
            <div class="ms-auto flex items-center gap-2 text-sm">
                <span>{{ count($variants) }} variation(s)</span>
                (
                <button type="button" @click="allCollapsed = true"
                    class="text-blue-500 italic cursor-pointer">Expand</button>
                /
                <button type="button" @click="allCollapsed = false"
                    class="text-blue-500 italic cursor-pointer">Close</button>
                )
            </div>
        @endif
    </div>

    {{-- Variations List --}}
    @if (!empty($variants))
        <div class="space-y-4">
            @foreach ($variants as $index => $variant)
                <div class="border rounded-lg p-4 shadow-xs"
                    wire:key="variant-{{ $index }}-{{ $variant['attribute_hash'] ?? $index }}"
                    x-data="{
                        collapsed: {{ $loop->first ? 'true' : 'false' }},
                        readonlyName: @js(!empty($variant['name']))
                    }" @toggle-all-variants.window="collapsed = $event.detail.collapsed">

                    {{-- Variant Header --}}
                    <div class="flex items-center justify-between" :class="{ 'border-b pb-1 mb-3': collapsed }">
                        <flux:heading size="lg">
                            {{ !empty($variant['attributes'])
                                ? implode(' - ', array_values($variant['attributes']))
                                : 'Manual Variation #' . ($index + 1) }}
                        </flux:heading>

                        <div class="flex items-center gap-3 text-sm">
                            <button type="button" wire:click="removeVariant({{ $index }})"
                                wire:confirm="Remove this variation?"
                                class="text-red-500 cursor-pointer">Remove</button>
                            <button type="button" @click="collapsed = !collapsed"
                                class="text-blue-500 cursor-pointer">Edit</button>
                        </div>
                    </div>

                    {{-- Variant Body --}}
                    <div x-cloak x-show="collapsed" x-collapse class="py-4 space-y-5">

                        {{-- SKU --}}
                        <div class="grid grid-cols-2 gap-3">
                            <flux:input wire:model="variants.{{ $index }}.sku" label="SKU"
                                placeholder="Leave blank to auto-generate" />
                        </div>

                        {{-- Flags --}}
                        <div class="flex items-center gap-4 border-y py-3">
                            <flux:checkbox wire:model="variants.{{ $index }}.is_active" label="Active" />
                            <flux:checkbox wire:model.live="variants.{{ $index }}.manage_stock"
                                label="Manage Stock" />
                            <flux:checkbox wire:model="variants.{{ $index }}.is_default"
                                label="Default Variation" />
                        </div>

                        {{-- Variation Name --}}
                        <flux:input wire:model="variants.{{ $index }}.name" ::readonly="readonlyName"
                            label="Variation Name">
                            <x-slot name="iconTrailing">
                                <flux:button size="sm" variant="subtle" @click="readonlyName = !readonlyName"
                                    icon="pencil" class="-mr-1" />
                            </x-slot>
                        </flux:input>

                        {{-- Pricing --}}
                        <div class="grid grid-cols-2 gap-3">
                            <flux:input type="number" step="0.01" wire:model="variants.{{ $index }}.price"
                                label="Regular Price (KES)" />
                            <flux:input type="number" step="0.01"
                                wire:model="variants.{{ $index }}.sale_price" label="Sale Price (KES)" />
                        </div>

                        {{-- Stock --}}
                        @if ($variant['manage_stock'])
                            <div class="grid grid-cols-2 gap-3">
                                <flux:input type="number" wire:model="variants.{{ $index }}.stock_quantity"
                                    label="Stock Quantity" min="0" />
                                <flux:select label="Allow Backorders"
                                    wire:model="variants.{{ $index }}.allow_backorders">
                                    <flux:select.option value="0">Do not allow</flux:select.option>
                                    <flux:select.option value="1">Allow</flux:select.option>
                                </flux:select>
                            </div>
                            <flux:input wire:model="variants.{{ $index }}.low_stock_threshold"
                                label="Low Stock Threshold" type="number" min="0" />
                        @else
                            <flux:select wire:model="variants.{{ $index }}.stock_status" label="Stock Status">
                                <flux:select.option value="in_stock">In Stock</flux:select.option>
                                <flux:select.option value="out_of_stock">Out of Stock</flux:select.option>
                                <flux:select.option value="backorder">Backorder</flux:select.option>
                            </flux:select>
                        @endif

                        {{-- Shipping --}}
                        <div class="grid grid-cols-2 gap-3">
                            <flux:input label="Weight (kg)" type="number" step="0.01"
                                wire:model="variants.{{ $index }}.weight" />
                            <flux:field>
                                <flux:label>Dimensions (L x W x H)</flux:label>
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
                        <flux:textarea wire:model="variants.{{ $index }}.description" label="Description"
                            rows="2" />
                    </div>
                </div>
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

    {{-- Bulk Pricing Modal --}}
    <flux:modal name="bulk-pricing" class="max-w-sm space-y-5">
        <flux:heading>Set Prices for All Variations</flux:heading>
        <flux:input wire:model="bulkPrice" label="Regular Price (KES)" type="number" step="0.01"
            placeholder="Leave blank to skip" />
        <flux:input wire:model="bulkSalePrice" label="Sale Price (KES)" type="number" step="0.01"
            placeholder="Leave blank to skip" />
        <div class="flex gap-3 justify-end">
            <flux:button @click="$flux.modal('bulk-pricing').close()">Cancel</flux:button>
            <flux:button variant="primary" wire:click="applyBulkPricing">Apply</flux:button>
        </div>
    </flux:modal>

    {{-- Bulk Stock Modal --}}
    <flux:modal name="bulk-stock" class="max-w-sm space-y-5">
        <flux:heading>Set Stock Quantity for All Variations</flux:heading>
        <flux:input wire:model="bulkStockQuantity" label="Stock Quantity" type="number" min="0" />
        <div class="flex gap-3 justify-end">
            <flux:button @click="$flux.modal('bulk-stock').close()">Cancel</flux:button>
            <flux:button variant="primary" wire:click="applyBulkStock">Apply</flux:button>
        </div>
    </flux:modal>

    {{-- Bulk Dimensions Modal --}}
    <flux:modal name="bulk-dimensions" class="max-w-sm space-y-5">
        <flux:heading>Set Dimensions & Weight for All Variations</flux:heading>
        <flux:input wire:model="bulkWeight" label="Weight (kg)" type="number" step="0.01"
            placeholder="Leave blank to skip" />
        <flux:field>
            <flux:label>Dimensions (L x W x H)</flux:label>
            <flux:input.group>
                <flux:input wire:model="bulkLength" placeholder="Length" />
                <flux:input wire:model="bulkWidth" placeholder="Width" />
                <flux:input wire:model="bulkHeight" placeholder="Height" />
            </flux:input.group>
        </flux:field>
        <div class="flex gap-3 justify-end">
            <flux:button @click="$flux.modal('bulk-dimensions').close()">Cancel</flux:button>
            <flux:button variant="primary" wire:click="applyBulkDimensions">Apply</flux:button>
        </div>
    </flux:modal>
</div>
