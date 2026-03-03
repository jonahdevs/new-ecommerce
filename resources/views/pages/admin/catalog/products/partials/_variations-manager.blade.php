<?php

use App\Models\AttributeValue;
use App\Models\Product;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public ?int $productId = null;
    public array $variants = [];
    public array $availableAttributes = [];
    public array $variantsToDelete = [];

    // Bulk action inputs
    public ?float $bulkPrice = null;
    public ?float $bulkSalePrice = null;
    public ?int $bulkStockQuantity = null;
    public ?float $bulkWeight = null;
    public ?float $bulkLength = null;
    public ?float $bulkWidth = null;
    public ?float $bulkHeight = null;

    public function mount(?Product $product = null): void
    {
        if ($product?->exists) {
            $this->productId = $product->id;
            $this->loadExistingVariants($product);
        }
    }

    private function loadExistingVariants(Product $product): void
    {
        $this->variants = $product
            ->variants()
            ->with('attributeValues.attribute')
            ->get()
            ->map(
                fn($variant) => [
                    'id' => $variant->id,
                    'name' => $variant->name,
                    'sku' => $variant->sku,
                    'price' => $variant->price,
                    'sale_price' => $variant->sale_price,
                    'manage_stock' => $variant->manage_stock,
                    'stock_quantity' => $variant->stock_quantity,
                    'stock_status' => $variant->stock_status,
                    'allow_backorders' => $variant->allow_backorders,
                    'low_stock_threshold' => $variant->low_stock_threshold,
                    'weight' => $variant->weight,
                    'length' => $variant->length,
                    'width' => $variant->width,
                    'height' => $variant->height,
                    'description' => $variant->description,
                    'is_active' => $variant->is_active,
                    'is_default' => $variant->is_default,
                    'attributes' => $variant->attributeValues->mapWithKeys(fn($av) => [$av->attribute->name => $av->value])->toArray(),
                    'attribute_value_ids' => $variant->attributeValues->pluck('id')->toArray(),
                    'attribute_hash' => md5(implode('-', $variant->attributeValues->pluck('id')->sort()->toArray())),
                ],
            )
            ->toArray();
    }

    // -----------------------------------------------
    // Push state to parent on save
    // -----------------------------------------------

    #[On('push-state-to-parent')]
    public function pushState(): void
    {
        $this->dispatch('variants-state-ready', variants: $this->variants, toDelete: $this->variantsToDelete);
    }

    // -----------------------------------------------
    // Listen for attributes updated from AttributesManager
    // -----------------------------------------------

    #[On('attributes-updated')]
    public function updateAvailableAttributes(array $attributes): void
    {
        $this->availableAttributes = $attributes;
    }

    // -----------------------------------------------
    // Deactivate / Reactivate
    // -----------------------------------------------

    #[On('deactivate-all-variants')]
    public function deactivateAllVariants(): void
    {
        foreach ($this->variants as $index => $variant) {
            $this->variants[$index]['is_active'] = false;
        }
    }

    #[On('reactivate-all-variants')]
    public function reactivateAllVariants(): void
    {
        foreach ($this->variants as $index => $variant) {
            $this->variants[$index]['is_active'] = true;
        }
    }

    // -----------------------------------------------
    // Generate Variations (Merge Strategy)
    // -----------------------------------------------

    public function generateVariations(): void
    {
        $variationAttributes = collect($this->availableAttributes)->filter(fn($a) => $a['is_variation_attribute'])->values();

        if ($variationAttributes->isEmpty()) {
            $this->dispatch('notify', variant: 'warning', message: 'No attributes marked as "Used for variations". Please set at least one.');
            return;
        }

        $attributeValueGroups = [];

        foreach ($variationAttributes as $attr) {
            $valueIds = is_array($attr['values']) ? $attr['values'] : [];
            if (empty($valueIds)) {
                continue;
            }

            $values = AttributeValue::whereIn('id', $valueIds)->get();
            if ($values->isEmpty()) {
                continue;
            }

            $attributeValueGroups[] = $values
                ->map(
                    fn($v) => [
                        'attribute_name' => $attr['name'],
                        'value' => $v->value,
                        'value_id' => $v->id,
                    ],
                )
                ->toArray();
        }

        if (empty($attributeValueGroups)) {
            $this->dispatch('notify', variant: 'warning', message: 'Please select values for your variation attributes.');
            return;
        }

        $combinations = $this->cartesian($attributeValueGroups);
        $existingHashes = collect($this->variants)->pluck('attribute_hash')->toArray();
        $newCount = 0;

        foreach ($combinations as $combination) {
            $valueIds = collect($combination)->pluck('value_id')->sort()->toArray();
            $hash = md5(implode('-', $valueIds));

            if (in_array($hash, $existingHashes)) {
                continue;
            }

            $this->variants[] = [
                'id' => null,
                'name' => null,
                'sku' => '',
                'price' => null,
                'sale_price' => null,
                'manage_stock' => true,
                'stock_quantity' => 0,
                'stock_status' => 'in_stock',
                'allow_backorders' => false,
                'low_stock_threshold' => null,
                'weight' => null,
                'length' => null,
                'width' => null,
                'height' => null,
                'description' => null,
                'is_active' => true,
                'is_default' => false,
                'attributes' => collect($combination)->mapWithKeys(fn($c) => [$c['attribute_name'] => $c['value']])->toArray(),
                'attribute_value_ids' => $valueIds,
                'attribute_hash' => $hash,
            ];

            $newCount++;
        }

        $this->dispatch('notify', variant: 'success', message: "{$newCount} new variation(s) generated.");
    }

    // -----------------------------------------------
    // Add Manual Variation
    // -----------------------------------------------

    public function addVariant(): void
    {
        $this->variants[] = [
            'id' => null,
            'name' => null,
            'sku' => '',
            'price' => null,
            'sale_price' => null,
            'manage_stock' => true,
            'stock_quantity' => 0,
            'stock_status' => 'in_stock',
            'allow_backorders' => false,
            'low_stock_threshold' => null,
            'weight' => null,
            'length' => null,
            'width' => null,
            'height' => null,
            'description' => null,
            'is_active' => true,
            'is_default' => false,
            'attributes' => [],
            'attribute_value_ids' => [],
            'attribute_hash' => Str::uuid(),
        ];
    }

    // -----------------------------------------------
    // Remove Variation (queued - deleted on save)
    // -----------------------------------------------

    public function removeVariant(int $index): void
    {
        $variant = $this->variants[$index];

        if (!empty($variant['id'])) {
            $this->variantsToDelete[] = $variant['id'];
        }

        array_splice($this->variants, $index, 1);
        $this->variants = array_values($this->variants);
    }

    // -----------------------------------------------
    // Clear All Variants (queues all for deletion)
    // -----------------------------------------------

    public function clearAllVariants(): void
    {
        foreach ($this->variants as $variant) {
            if (!empty($variant['id'])) {
                $this->variantsToDelete[] = $variant['id'];
            }
        }

        $this->variants = [];
        $this->dispatch('notify', variant: 'success', message: 'All variations removed. Save product to apply changes.');
    }

    // -----------------------------------------------
    // Bulk Actions
    // -----------------------------------------------

    public function toggleAllVariantsActive(): void
    {
        foreach ($this->variants as $index => $variant) {
            $this->variants[$index]['is_active'] = !$variant['is_active'];
        }
    }

    public function toggleAllVariantsManageStock(): void
    {
        foreach ($this->variants as $index => $variant) {
            $this->variants[$index]['manage_stock'] = !$variant['manage_stock'];
        }
    }

    public function setAllVariantsStockStatus(string $status): void
    {
        foreach ($this->variants as $index => $variant) {
            $this->variants[$index]['stock_status'] = $status;
        }
    }

    public function applyBulkPricing(): void
    {
        foreach ($this->variants as $index => $variant) {
            if ($this->bulkPrice !== null) {
                $this->variants[$index]['price'] = $this->bulkPrice;
            }
            if ($this->bulkSalePrice !== null) {
                $this->variants[$index]['sale_price'] = $this->bulkSalePrice;
            }
        }

        $this->bulkPrice = null;
        $this->bulkSalePrice = null;
        $this->dispatch('notify', variant: 'success', message: 'Pricing applied to all variations.');
        $this->dispatch('close-modal', name: 'bulk-pricing');
    }

    public function applyBulkStock(): void
    {
        foreach ($this->variants as $index => $variant) {
            if ($this->bulkStockQuantity !== null) {
                $this->variants[$index]['stock_quantity'] = $this->bulkStockQuantity;
            }
        }

        $this->bulkStockQuantity = null;
        $this->dispatch('notify', variant: 'success', message: 'Stock quantity applied to all variations.');
        $this->dispatch('close-modal', name: 'bulk-stock');
    }

    public function applyBulkDimensions(): void
    {
        foreach ($this->variants as $index => $variant) {
            if ($this->bulkWeight !== null) {
                $this->variants[$index]['weight'] = $this->bulkWeight;
            }
            if ($this->bulkLength !== null) {
                $this->variants[$index]['length'] = $this->bulkLength;
            }
            if ($this->bulkWidth !== null) {
                $this->variants[$index]['width'] = $this->bulkWidth;
            }
            if ($this->bulkHeight !== null) {
                $this->variants[$index]['height'] = $this->bulkHeight;
            }
        }

        $this->bulkWeight = $this->bulkLength = $this->bulkWidth = $this->bulkHeight = null;
        $this->dispatch('notify', variant: 'success', message: 'Dimensions applied to all variations.');
        $this->dispatch('close-modal', name: 'bulk-dimensions');
    }

    // -----------------------------------------------
    // Helpers
    // -----------------------------------------------

    private function cartesian(array $arrays): array
    {
        $result = [[]];

        foreach ($arrays as $values) {
            $append = [];
            foreach ($result as $product) {
                foreach ($values as $value) {
                    $append[] = array_merge($product, [$value]);
                }
            }
            $result = $append;
        }

        return $result;
    }
};
?>


{{-- Variations Manager --}}
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
            <flux:button type="button" wire:click="generateVariations" icon="sparkles" class="cursor-pointer"
                size="sm">
                Generate Variations
            </flux:button>

            <flux:button type="button" wire:click="addVariant" icon="plus" class="cursor-pointer" size="sm">
                Add Manual
            </flux:button>

            @if (!empty($variants))
                <flux:dropdown>
                    <flux:button icon:trailing="chevron-down" class="cursor-pointer" size="sm">Bulk Actions
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
            <div class="ms-auto flex items-center gap-1 text-xs">
                <span class="me-2">{{ count($variants) }} variation(s)</span>
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
                <flux:card class="p-0">
                    <div wire:key="variant-{{ $index }}-{{ $variant['attribute_hash'] ?? $index }}"
                        x-data="{
                            collapsed: {{ $loop->first ? 'true' : 'false' }},
                            readonlyName: @js(!empty($variant['name']))
                        }" @toggle-all-variants.window="collapsed = $event.detail.collapsed">

                        {{-- Variant Header --}}
                        <div class="flex items-center justify-between px-3 py-2" :class="{ 'border-b': collapsed }">
                            <flux:heading>
                                {{ !empty($variant['attributes']) ? implode(' - ', array_values($variant['attributes'])) : 'Manual Variation #' . ($index + 1) }}
                            </flux:heading>

                            <div class="flex items-center gap-3 text-sm">
                                <flux:button type="button" icon="trash" icon-variant="outline" size="xs"
                                    variant="ghost" class="cursor-pointer text-red-500!" tooltip="Delete Variation"
                                    wire:click="removeVariant({{ $index }})" />

                                <flux:button icon="chevron-down" size="xs" variant="ghost"
                                    class="cursor-pointer transition-transform duration-300"
                                    x-bind:class="{ 'rotate-180': collapsed }" @click="collapsed = !collapsed" />
                            </div>
                        </div>

                        {{-- Variant Body --}}
                        <div x-cloak x-show="collapsed" x-collapse class="p-5 space-y-5">

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
                                <flux:input type="number" step="0.01"
                                    wire:model="variants.{{ $index }}.price" label="Regular Price (KES)" />
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
                                <flux:select wire:model="variants.{{ $index }}.stock_status"
                                    label="Stock Status">
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
                </flux:card>
            @endforeach
        </div>
    @else
        {{-- Empty State --}}
        <div class="text-center py-10 text-zinc-500">
            <flux:icon.cube class="size-12 mx-auto mb-3 opacity-40" />
            <p class="font-medium">No variations yet</p>
            <p class="text-sm mt-1">Select attributes marked as "Used for variations" then click Generate, or add
                manually.</p>
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
