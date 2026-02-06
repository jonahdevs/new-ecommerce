<?php
use App\Models\Product;
use App\Models\Category;
use App\Livewire\Forms\Admin\ProductForm;
use Livewire\Component;
use Livewire\Attributes\{Computed};

new class extends Component {
    public ProductForm $form;
    public Product $product;

    public string $activeTab = 'general';

    #[Computed]
    public function categories()
    {
        return Category::active()->orderBy('name')->get();
    }
}; ?>

<div x-data="productForm">
    {{-- Page Header --}}
    <section class="flex items-center justify-between gap-5 flex-wrap mb-6">
        <div>
            <flux:heading size="xl">Edit Product</flux:heading>
            <flux:subheading>Manage your product details, pricing, inventory, and more</flux:subheading>
            <flux:breadcrumbs class="mt-2">
                <flux:breadcrumbs.item :href="route('dashboard')" icon="home" icon-variant="outline">Dashboard
                </flux:breadcrumbs.item>
                <flux:breadcrumbs.item :href="route('admin.products')">Products</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $product ? 'Edit' : 'Create' }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center gap-3">
            <flux:button variant="ghost" href="{{ route('admin.products') }}" wire:navigate>
                Cancel
            </flux:button>
            <flux:button type="submit" form="product-form" variant="primary">
                {{ $product ? 'Update Product' : 'Create Product' }}
            </flux:button>
        </div>
    </section>

    <form wire:submit="save" id="product-form">
        {{-- Two-column layout --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left Panel - Main Details --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Basic Information Section --}}

                <flux:card class="p-0">
                    <div class="px-3 py-2 border-b">
                        <flux:heading size="lg">Basic Information</flux:heading>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-5">
                        <!-- product name -->
                        <flux:input wire:model.blur="form.name" label="Name" class="!md:col-span-2" />

                        <!-- Model Number -->
                        <flux:input wire:model="form.model_number" label="Model Number" />

                        <!-- Sku -->
                        <flux:input wire:model="form.sku" label="Sku" placeholder="e.g., MX-2024" />

                        <!-- Slug-->
                        <flux:input wire:model="form.slug" label="Slug" placeholder="e.g., wireless-mouse" />


                        <div class="md:col-span-2" wire:key="short-description-editor-wrapper">
                            <flux:label class="mb-2">Short Description</flux:label>
                            {{-- <x-ckeditor wire:model="form.short_description" id="short_description_editor"
                                placeholder="Brief product description (max 500 characters)" :height="150"
                                toolbar="minimal" /> --}}
                            <flux:error name="form.short_description" />
                        </div>

                        <div class="md:col-span-2" wire:key="description-editor-wrapper">
                            <flux:label class="mb-2">Description</flux:label>
                            {{-- <x-ckeditor wire:model="form.description" id="description_editor"
                                placeholder="Detailed product description with formatting options" :height="400"
                                toolbar="full" /> --}}
                            <flux:error name="form.description" />
                        </div>

                        {{-- Technical Specification --}}
                    </div>
                </flux:card>


                {{-- Product Details Tabs --}}
                <flux:card class="p-0">
                    <div class="px-3 py-2 border-b">
                        <div class="flex items-center gap-2">
                            <flux:heading size="lg">Product Details</flux:heading>

                            <flux:select size="sm" wire:model.change="form.product_type"
                                placeholder="Choose Product Type..." class="w-fit">
                                <flux:select.option value="simple">Simple Product</flux:select.option>
                                <flux:select.option value="variable">Variable Product</flux:select.option>
                            </flux:select>
                        </div>
                    </div>

                    <div>
                        {{-- Tab Headers --}}
                        <div class="flex border-b border-zinc-200 overflow-x-auto">
                            <button type="button" @click="$wire.activeTab = 'general'" @class([
                                'whitespace-nowrap py-3 px-4 border-b-2 font-medium text-sm transition-colors cursor-pointer',
                                'border-blue-500 text-blue-600' => $activeTab === 'general',
                                'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300' =>
                                    $activeTab !== 'general',
                            ])>
                                General
                            </button>

                            <button type="button" @click="$wire.activeTab = 'inventory'" @class([
                                'whitespace-nowrap py-3 px-4 border-b-2 font-medium text-sm transition-colors cursor-pointer',
                                'border-blue-500 text-blue-600' => $activeTab === 'inventory',
                                'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300' =>
                                    $activeTab !== 'inventory',
                            ])>
                                Inventory
                            </button>
                            <button type="button" @click="$wire.activeTab = 'shipping'" @class([
                                'whitespace-nowrap py-3 px-4 border-b-2 font-medium text-sm transition-colors cursor-pointer',
                                'border-blue-500 text-blue-600' => $activeTab === 'shipping',
                                'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300' =>
                                    $activeTab !== 'shipping',
                            ])>
                                Shipping
                            </button>
                            <button type="button" @click="$wire.activeTab = 'accessories'"
                                @class([
                                    'whitespace-nowrap py-3 px-4 border-b-2 font-medium text-sm transition-colors cursor-pointer',
                                    'border-blue-500 text-blue-600' => $activeTab === 'accessories',
                                    'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300' =>
                                        $activeTab !== 'accessories',
                                ])>
                                Accessories
                            </button>
                            <button type="button" @click="$wire.activeTab = 'attributes'" @class([
                                'whitespace-nowrap py-3 px-4 border-b-2 font-medium text-sm transition-colors cursor-pointer',
                                'border-blue-500 text-blue-600' => $activeTab === 'attributes',
                                'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300' =>
                                    $activeTab !== 'attributes',
                            ])>
                                Attributes
                            </button>

                            <button wire:show="$wire.form->product_type == 'variable'" type="button"
                                @click="$wire.activeTab = 'variations'" @class([
                                    'whitespace-nowrap py-3 px-4 border-b-2 font-medium text-sm transition-colors cursor-pointer',
                                    'border-blue-500 text-blue-600' => $activeTab === 'variations',
                                    'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300' =>
                                        $activeTab !== 'variations',
                                ])>
                                Variations
                            </button>
                        </div>

                        {{-- Tab Content: General --}}
                        <div wire:show="activeTab === 'general'" class="grid grid-cols-1 md:grid-cols-2 gap-6 p-5">
                            {{-- Price --}}
                            <flux:input type="number" wire:model="form.price" label="Regular Price" placeholder="0.00"
                                step="0.01" min="0" required />

                            {{-- Sale Price --}}
                            <flux:input type="number" wire:model="form.sale_price" label="Sale Price"
                                placeholder="0.00" hint="Leave empty if no discount" step="0.01" min="0" />

                            {{-- Cost Price --}}
                            <flux:input type="number" wire:model="form.cost_price" label="Cost Price"
                                placeholder="0.00" step="0.01" min="0" />

                            {{-- Tax Rate --}}
                            <flux:input type="number" wire:model="form.tax_rate" label="Tax Rate (%)"
                                placeholder="0.00" step="0.01" min="0" max="100" />
                        </div>

                        {{-- Tab Content: Inventory --}}
                        <div wire:show="activeTab === 'inventory'" class="space-y-6 p-5">

                            <div class="pb-4 border-b">
                                <flux:field variant="inline">
                                    <flux:checkbox wire:model="form.manage_stock" />
                                    <flux:label>Manage Stock <span class="text-zinc-500 font-normal">-
                                            Enable stock management for
                                            this
                                            product</span></flux:label>
                                    <flux:error name="form.manage_stock" />
                                </flux:field>
                            </div>

                            <div x-cloak x-show="manageStock">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    {{-- Stock Quantity --}}
                                    <flux:input wire:model="form.stock_quantity" label="Stock Quantity *"
                                        type="number" placeholder="0" min="0" required />

                                    {{-- Low Stock Threshold --}}
                                    <flux:input wire:model="form.low_stock_threshold" label=" Low Stock Threshold *"
                                        type="number" placeholder="0" min="0" required />


                                    {{-- Stock Status --}}
                                    <flux:select wire:model="form.stock_status" label="Stock Status *">
                                        <flux:select.option value="in_stock">In Stock
                                        </flux:select.option>
                                        <flux:select.option value="out_of_stock">Out of Stock
                                        </flux:select.option>
                                        <flux:select.option value="backorder">Backorder
                                        </flux:select.option>
                                    </flux:select>

                                </div>
                            </div>

                            {{-- Allow Backorders Checkbox --}}
                            <div class="pb-4 border-b">
                                <flux:field variant="inline">
                                    <flux:checkbox wire:model="form.allow_backorders" />
                                    <flux:label>Allow Backorders <span class="text-zinc-500 font-normal">-
                                            Allow customers to
                                            purchase
                                            when out
                                            of
                                            stock</span></flux:label>
                                    <flux:error name="form.manage_stock" />
                                </flux:field>
                            </div>

                            {{-- Backorder Fields (visible only if allow_backorders is checked) --}}
                            <div x-cloak x-show="allowBackorders">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                                    {{-- Max Backorder Quantity --}}
                                    <flux:input wire:model="form.max_backorder_quantity"
                                        label="Max Backorder Quantity" type="number" placeholder="0"
                                        min="0" />


                                    {{-- Expected Restock Date --}}
                                    <flux:field>
                                        <flux:label> Expected Restock Date</flux:label>
                                        {{-- <x-my-datepicker wire:model="form.expected_restock_date" /> --}}
                                        <flux:error name="form.expected_restock_date" />
                                    </flux:field>


                                    {{-- Backorder Message --}}
                                    <div class="md:col-span-2">
                                        <flux:textarea wire:model="form.backorder_message" label="Backorder Message"
                                            placeholder="Message to display when product is on backorder" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Tab Content: Shipping --}}
                        <div wire:show="activeTab === 'shipping'" class="space-y-6 p-5">

                            <flux:input type="number" wire:model="form.weight" label=" Weight (kg)"
                                placeholder="0.00" step="0.01" min="0" />

                            <flux:field>
                                <flux:label>Dimensions</flux:label>

                                <flux:input.group>
                                    <flux:input type="number" wire:model="form.length" placeholder="Length (0.00)"
                                        step="0.01" min="0" />

                                    <flux:input type="number" wire:model="form.width" placeholder="Width (0.00)"
                                        step="0.01" min="0" />

                                    <flux:input type="number" wire:model="form.height" placeholder="Height (0.00)"
                                        step="0.01" min="0" />
                                </flux:input.group>

                                <flux:error name="form.length" />
                                <flux:error name="form.width" />
                                <flux:error name="form.height" />
                            </flux:field>

                            {{-- Estimated Delivery Time --}}
                            <flux:field>
                                <flux:label> Estimated Delivery Time</flux:label>
                                {{-- <x-my-datepicker wire:model="form.estimated_delivery_time" /> --}}
                                <flux:error name="form.estimated_delivery_time" />
                            </flux:field>

                            {{-- Shipping Information --}}
                            <flux:textarea wire:model="form.shipping_information" label="Shipping Information"
                                placeholder="Additional shipping information" />

                            {{-- Warranty Information --}}
                            <flux:textarea wire:model="form.warranty_information" label="Warranty Information"
                                placeholder="Warranty details" />

                            {{-- Return Policy --}}
                            <flux:textarea wire:model="form.return_policy" label="Return Policy"
                                placeholder="Return policy details" />
                        </div>

                        {{-- Tab Content: Accessories --}}
                        <div wire:show="activeTab === 'accessories'" class="space-y-4 p-5">
                            {{-- Accessories --}}
                            <flux:field>
                                <flux:label> Accessories
                                    <span class="text-xs text-zinc-500 font-normal">- Products that
                                        complement
                                        this item</span>
                                </flux:label>

                                {{-- <x-my-choices-offline wire:model="form.selectedAccessories" :options="$this->products"
                                        option-sub-label="sku" placeholder="Select Accessories"
                                        option-avatar="image_url" clearable searchable /> --}}
                                <flux:error name="form.selectedAccessories" />
                            </flux:field>

                            <flux:field>
                                <flux:label> Upsells
                                    <span class="text-xs text-zinc-500 font-normal">- Higher value
                                        alternatives
                                        to suggest</span>
                                </flux:label>
                                {{--
                                    <x-my-choices-offline wire:model="form.selectedUpsells"
                                        placeholder="Select products for upsells" :options="$this->products"
                                        option-sub-label="sku" option-avatar="image_url" clearable searchable /> --}}
                                <flux:error name="form.selectedUpsells" />
                            </flux:field>

                            <flux:field>
                                <flux:label> Cross Sells
                                    <span class="text-xs text-zinc-500 font-normal">- Products to show
                                        at
                                        checkout</span>
                                </flux:label>

                                {{-- <x-my-choices-offline wire:model="form.selectedCrossSells" :options="$this->products"
                                        option-sub-label="sku" option-avatar="image_path" clearable searchable /> --}}
                                <flux:error name="form.selectedCrossSells" />
                            </flux:field>

                            <flux:field>
                                <flux:label> Related Products
                                    <span class="text-xs text-zinc-500 font-normal">- Similar products
                                        to
                                        display</span>
                                </flux:label>

                                {{-- <x-my-choices-offline wire:model="form.selectedRelated" :options="$this->products"
                                        option-sub-label="sku" option-avatar="image_path" clearable searchable /> --}}
                                <flux:error name="form.selectedRelated" />
                            </flux:field>
                        </div>

                        {{-- Tab Content: Attributes --}}
                        <div wire:show="activeTab === 'attributes'" class="p-5">
                            <div class="space-y-4" x-data="{
                                allCollapsed: false,
                                init() {
                                    this.$watch('allCollapsed', (value) => {
                                        this.$dispatch('toggle-all-attributes', { collapsed: value })
                                    })
                                }
                            }">
                                <div class="text-sm text-base-content/60">
                                    Add descriptive pieces of information that customers can use to search
                                    for
                                    this
                                    product,
                                    such as " Material" or "Brand" </div>

                                <section class="flex items-center gap-3">
                                    <flux:button wire:click="addNewAttribute">
                                        Add New
                                    </flux:button>

                                    <flux:select wire:model.change="form.selectedExistingAttribute"
                                        placeholder="Add existing" class="max-w-fit">
                                        {{-- @foreach ($this->productAttributes as $attr)
                                                <option value="{{ $attr->id }}" wire:key="{{ $attr->id }}">
                                                    {{ ucfirst($attr->name) }}</option>
                                            @endforeach --}}
                                    </flux:select>

                                    @if (!empty($form->selectedAttributes))
                                        <section class="ms-auto flex items-center gap-2">
                                            <span class="text-sm">
                                                {{ count($form->selectedAttributes) }}
                                                attribute(s)
                                            </span>

                                            <div class="flex items-center gap-1.5">
                                                (
                                                <button type="button" @click="allCollapsed = true"
                                                    class="text-sheffield-blue italic text-sm cursor-pointer">Expand</button>
                                                /
                                                <button type="button" @click="allCollapsed = false"
                                                    class="text-sheffield-blue italic text-sm cursor-pointer">Close</button>
                                                )
                                            </div>
                                        </section>
                                    @endif
                                </section>

                                @foreach ($form->selectedAttributes as $index => $attr)
                                    <section class="border p-5 rounded-sm " wire:key="attribute-{{ $index }}"
                                        x-data="{ collapsed: {{ $loop->first ? 'true' : 'false' }} }"
                                        @toggle-all-attributes.window="collapsed = $event.detail.collapsed">
                                        <section class="flex items-center gap-4"
                                            :class="{ 'border-b pb-1 mb-4': collapsed }">
                                            <flux:heading size="lg" class="font-semibold">
                                                {{ $attr['name'] ? ucfirst($attr['name']) : 'New Attribute' }}
                                            </flux:heading>

                                            <div class="flex items-center ms-auto gap-3 text-sm">
                                                @if (
                                                    !(count($form->selectedAttributes) === 1 &&
                                                        $attr['attribute_id'] === null &&
                                                        $attr['is_new'] === true &&
                                                        empty($attr['name']) &&
                                                        empty($attr['values'])
                                                    ))
                                                    <button type="button"
                                                        wire:click="removeSelectedAttribute({{ $index }})"
                                                        class="text-red-500 cursor-pointer">Remove</button>
                                                @endif

                                                <button @click="collapsed = !collapsed" type="button"
                                                    class="text-blue-500 cursor-pointer">Edit</button>
                                            </div>

                                        </section>

                                        <section x-cloak x-show="collapsed" x-collapse class="grid grid-cols-3 gap-5">
                                            <div class="col-span-1 space-y-4">
                                                @if ($attr['is_new'])
                                                    <flux:input label="Name"
                                                        wire:model.blur="form.selectedAttributes.{{ $index }}.name"
                                                        placeholder="e.g., Size, Material" />
                                                @else
                                                    <div>
                                                        Name: <span
                                                            class="ms-2 font-semibold">{{ ucfirst($attr['name']) }}</span>
                                                    </div>
                                                @endif

                                                <flux:checkbox
                                                    wire:model.live="form.selectedAttributes.{{ $index }}.visible"
                                                    label="Visible on the product page" />

                                                <flux:checkbox
                                                    wire:model.live="form.selectedAttributes.{{ $index }}.used_for_variations"
                                                    label="Used for variations" />
                                            </div>

                                            <div class="col-span-2">
                                                @if ($attr['is_new'])
                                                    <flux:textarea label="Value (s)"
                                                        wire:model.blur="form.selectedAttributes.{{ $index }}.values"
                                                        placeholder="Enter values separated by '|' e.g. Blue | Large | Medium" />
                                                @else
                                                    {{-- <x-my-choices
                                                            wire:model="form.selectedAttributes.{{ $index }}.values"
                                                            :options="$form->getProductAttributeValues(
                                                                $attr['attribute_id'],
                                                            )" placeholder="Search ..." clearable /> --}}
                                                @endif
                                            </div>
                                        </section>

                                    </section>
                                @endforeach
                            </div>
                        </div>

                        {{-- Tab Content: Variations --}}
                        <div wire:show="$form->product_type === 'variable' && activeTab === 'variations'">
                            <div class="space-y-4">
                                <div class="space-y-4" x-data="{
                                    allCollapsed: false,
                                    init() {
                                        this.$watch('allCollapsed', (value) => {
                                            this.$dispatch('toggle-all-variants', { collapsed: value })
                                        })
                                    }
                                }">
                                    {{-- Generate Variations Button --}}
                                    <div class="flex gap-2 items-center">
                                        <section class="flex items-center gap-3">

                                            <flux:button type="button" wire:click="generateVariations"
                                                icon="sparkles">
                                                Generate Variations
                                            </flux:button>

                                            <flux:button type="button" wire:click="addVariant" icon="plus">
                                                Add Manual
                                            </flux:button>

                                            @if (!empty($form->variants))
                                                <flux:dropdown>
                                                    <flux:button icon:trailing="chevron-down">
                                                        Bulk Actions
                                                    </flux:button>
                                                    <flux:menu class="min-w-32">
                                                        <flux:menu.group heading="Bulk Actions">
                                                            <flux:menu.item wire:click="clearAllVariants"
                                                                variant="danger">Delete all variations
                                                            </flux:menu.item>
                                                        </flux:menu.group>
                                                        <flux:menu.group heading="Status">
                                                            <flux:menu.item wire:click="toggleAllVariantsActive">
                                                                Toggle "Active"
                                                            </flux:menu.item>
                                                        </flux:menu.group>
                                                        <flux:menu.group heading="Pricing">
                                                            <flux:menu.item
                                                                @click="$flux.modal('bulk-pricing').show()">
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
                                                            <flux:menu.item
                                                                wire:click="setAllVariantsStockStatus('in_stock')">
                                                                Set Status - In stock
                                                            </flux:menu.item>
                                                            <flux:menu.item
                                                                wire:click="setAllVariantsStockStatus('out_of_stock')">
                                                                Set Status - Out of stock
                                                            </flux:menu.item>
                                                            <flux:menu.item
                                                                wire:click="setAllVariantsStockStatus('backorder')">
                                                                Set Status - On Backorder
                                                            </flux:menu.item>
                                                        </flux:menu.group>
                                                        <flux:menu.group heading="Shipping">
                                                            <flux:menu.item
                                                                @click="$flux.modal('bulk-dimensions').show()">
                                                                Set dimensions & weight...
                                                            </flux:menu.item>
                                                        </flux:menu.group>
                                                    </flux:menu>
                                                </flux:dropdown>
                                            @endif
                                        </section>
                                        @if (!empty($form->variants))
                                            <section class="ms-auto flex items-center gap-2">
                                                <span class="text-sm"> {{ count($form->variants) }}
                                                    variation(s)
                                                </span>

                                                <div class="flex items-center gap-1.5">
                                                    (
                                                    <button type="button" @click="allCollapsed = true"
                                                        class="text-blue-500 italic text-sm cursor-pointer">Expand</button>
                                                    /
                                                    <button type="button" @click="allCollapsed = false"
                                                        class="text-blue-500 italic text-sm cursor-pointer">Close</button>
                                                    )
                                                </div>
                                            </section>
                                        @endif
                                    </div>

                                    {{-- Variations List --}}
                                    @if (!empty($form->variants))
                                        <div class="space-y-4">
                                            @foreach ($form->variants as $index => $variant)
                                                <div class="border rounded-lg p-4 shadow-xs"
                                                    wire:key="variant-{{ $index }}-{{ $variant['attribute_hash'] ?? $index }}"
                                                    x-data="{ collapsed: {{ $loop->first ? 'true' : 'false' }}, readonlyName: @js($variant['name'] ? true : false) }"
                                                    @toggle-all-variants.window="collapsed = $event.detail.collapsed">
                                                    {{--  Variant header --}}
                                                    <div class="flex items-center justify-between"
                                                        :class="{ 'border-b pb-1 mb-3': collapsed }">
                                                        <flux:heading size="lg" class="font-semibold">
                                                            {{ implode(' - ', array_values($variant['attributes'])) }}
                                                        </flux:heading>

                                                        <div class="flex items-center gap-3 text-sm">
                                                            <button type="button"
                                                                wire:click="removeVariant({{ $index }})"
                                                                class="text-red-500 cursor-pointer">Remove</button>
                                                            <button @click="collapsed = !collapsed" type="button"
                                                                class="text-blue-500 cursor-pointer">Edit</button>
                                                        </div>
                                                    </div>

                                                    {{-- variant body --}}
                                                    <section x-cloak x-show="collapsed" x-collapse
                                                        class="py-4 space-y-5">
                                                        <div class="grid grid-cols-2 gap-3">
                                                            {{-- <x-image-input
                                                                                                       wire-model="form.variants.{{ $index }}.image"
                                                                                                       :existing-image="$variant['existing_image']" label="Variant Image" /> --}}


                                                            <flux:input
                                                                wire:model="form.variants.{{ $index }}.sku"
                                                                label="SKU" required />
                                                        </div>

                                                        <div class="flex items-center gap-4 border-y py-3">
                                                            <flux:checkbox
                                                                wire:model="form.variants.{{ $index }}.is_active"
                                                                label="Active" />
                                                            <flux:checkbox
                                                                wire:model.live="form.variants.{{ $index }}.manage_stock"
                                                                label="Manage Stock" />
                                                        </div>

                                                        <flux:input
                                                            wire:model="form.variants.{{ $index }}.name"
                                                            ::readonly="readonlyName" label="Variation Name">
                                                            <x-slot name="iconTrailing">
                                                                <flux:button size="sm" variant="subtle"
                                                                    @click="readonlyName = !readonlyName"
                                                                    icon="pencil" class="-mr-1">
                                                                </flux:button>
                                                            </x-slot>
                                                        </flux:input>

                                                        <div class="grid grid-cols-2 gap-3">
                                                            <flux:input type="number" step="0.01"
                                                                wire:model="form.variants.{{ $index }}.sale_price"
                                                                label="Sale Price (KES)" required />
                                                            <flux:input type="number" step="0.01"
                                                                wire:model="form.variants.{{ $index }}.price"
                                                                label="Regular Price" />
                                                        </div>

                                                        @if ($variant['manage_stock'])
                                                            <div class="grid grid-cols-2 gap-3">
                                                                <flux:input type="number"
                                                                    wire:model="form.variants.{{ $index }}.stock_quantity"
                                                                    label="Stock Quantity" min="0" />

                                                                <flux:select label="Allow backorders"
                                                                    wire:model="form.variants.{{ $index }}.allow_backorders">
                                                                    <flux:select.option value="false">
                                                                        Do
                                                                        not allow
                                                                    </flux:select.option>
                                                                    <flux:select.option value="true">
                                                                        Allow
                                                                    </flux:select.option>
                                                                </flux:select>
                                                            </div>

                                                            <flux:input label="Low stock threshold"
                                                                wire:model="form.variants.{{ $index }}.low_stock_threshold" />
                                                        @else
                                                            <flux:select
                                                                wire:model="form.variants.{{ $index }}.stock_status"
                                                                label="Stock Status">
                                                                <option value="in_stock">In Stock
                                                                </option>
                                                                <option value="out_of_stock">Out of
                                                                    Stock
                                                                </option>
                                                                <option value="backorder">Backorder
                                                                </option>
                                                            </flux:select>
                                                        @endif

                                                        <div class="grid grid-cols-2 gap-3">
                                                            <flux:input label="Weight"
                                                                wire:model="form.variants.{{ $index }}.weight" />

                                                            <flux:field>
                                                                <flux:label>Dimensions (LxWxH) (in)
                                                                </flux:label>

                                                                <flux:input.group>
                                                                    <flux:input placeholder="Length"
                                                                        wire:model="form.variants.{{ $index }}.length" />
                                                                    <flux:input placeholder="Width"
                                                                        wire:model="form.variants.{{ $index }}.width" />
                                                                    <flux:input placeholder="Height"
                                                                        wire:model="form.variants.{{ $index }}.height" />
                                                                </flux:input.group>
                                                            </flux:field>
                                                        </div>

                                                        <flux:textarea label="Description"
                                                            wire:model="form.variants.{{ $index }}.description" />
                                                    </section>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-center py-8 text-base-content/60">
                                            <flux:icon.cube class="size-12 mx-auto mb-3 opacity-50" />
                                            <p>No variations yet</p>
                                            <p class="text-sm">Select attributes and generate variations
                                                or
                                                add
                                                them
                                                manually</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </flux:card>

                {{-- SEO Section --}}
                <flux:card class="p-0">
                    <div class="px-3 py-2 border-b">
                        <flux:heading>SEO & Meta Information</flux:heading>
                    </div>
                    <div class="space-y-4 p-5">
                        <!-- Meta title -->
                        <flux:input wire:model="form.meta_title" label="Meta Title"
                            placeholder="SEO title for this product" />

                        <!-- Meta description -->
                        <flux:textarea wire:model="form.meta_description" label="Meta Description" rows="3"
                            placeholder="SEO description for this product" />

                        <!-- Meta keywords -->
                        <flux:input wire:model="form.meta_keywords" label="Meta Keywords"
                            placeholder="keyword1, keyword2, keyword3"
                            description:trailing="Separate keywords with commas" />

                        <flux:field>
                            <flux:label>Canonical URL</flux:label>
                            <flux:input.group>
                                <flux:input.group.prefix>{{ config('app.url') }}</flux:input.group.prefix>
                                <flux:input wire:model="form.canonical_url" placeholder="products" />
                            </flux:input.group>

                            <flux:error name="form.canonical_url" />
                        </flux:field>
                    </div>
                </flux:card>
            </div>

            {{-- Right Panel - Status, Images & Organization --}}
            <div class="lg:col-span-1 space-y-6">

                {{-- Status & Visibility Section --}}
                <flux:card class="p-0">
                    <div class="px-3 py-2 border-b">
                        <flux:heading>Status & Visibility</flux:heading>
                    </div>

                    <div class="space-y-4 p-5">
                        {{-- Publication Status --}}

                        <flux:select wire:model="form.status" label="Publication Status">
                            <flux:select.option value="draft">Draft</flux:select.option>
                            <flux:select.option value="published">Published</flux:select.option>
                            <flux:select.option value="archived">Archived</flux:select.option>
                        </flux:select>

                        <flux:separator />

                        {{-- Visibility Options --}}
                        <div class="pt-2 space-y-3 ">
                            <flux:field variant="inline">
                                <flux:checkbox wire:model="form.is_active" />
                                <flux:label class="flex flex-col items-start">
                                    <p>Active</p>
                                    <p class="text-xs text-zinc-500">Display on website</p>
                                </flux:label>
                                <flux:error name="terms" />
                            </flux:field>

                            <flux:field variant="inline">
                                <flux:checkbox wire:model="form.is_featured" />
                                <flux:label class="flex flex-col items-start">
                                    <p> Featured</p>
                                    <p class="text-xs text-zinc-500">Show in featured section</p>
                                </flux:label>
                                <flux:error name="terms" />
                            </flux:field>
                        </div>
                    </div>
                </flux:card>

                {{-- Quotation Settings Section --}}
                <flux:card class="p-0">
                    <div class="px-3 py-2 border-b">
                        <flux:heading>Quotation Settings</flux:heading>
                    </div>

                    <div class="space-y-4 p-5">
                        <flux:field variant="inline">
                            <flux:checkbox wire:model.live="form.requires_quotation" />
                            <flux:label class="flex flex-col items-start">
                                <p>Requires Quotation</p>
                                <p class="text-xs text-zinc-500">Enable if price varies by quantity or
                                    customization
                                </p>
                            </flux:label>
                            <flux:error name="form.requires_quotation" />
                        </flux:field>

                        <div x-show="$wire.form.requires_quotation" x-cloak class="space-y-4 pt-4 border-t">
                            <flux:input wire:model="form.min_order_quantity" type="number"
                                label="Minimum Order Quantity" placeholder="e.g., 10" min="1" step="1"
                                hint="Minimum quantity required for quotation requests" />

                            <flux:textarea wire:model="form.quotation_notes" label="Quotation Notes"
                                placeholder="e.g., Volume discounts available. Contact us for bulk pricing."
                                rows="3" hint="Instructions or notes shown to customers requesting quotes" />
                        </div>
                    </div>
                </flux:card>


                {{-- Primary Image Section --}}
                <flux:card class="p-0">
                    <div class="px-3 py-2 border-b">
                        <flux:heading>Product Images</flux:heading>
                    </div>

                    <div class="p-5">
                        <flux:field>
                            <flux:label>Upload Image</flux:label>
                            <flux:input wire:model="form.image" type="file" accept="image/*" />
                            <flux:error name="form.image" />
                        </flux:field>

                        {{-- Loading State --}}
                        <div wire:loading wire:target="form.image" class="mt-3">
                            <flux:badge variant="warning">Uploading image...</flux:badge>
                        </div>

                        {{-- Primary Image Preview --}}
                        <div class="mt-4">
                            @if ($form->image)
                                <div
                                    class="relative w-full max-w-[250px] aspect-square rounded-lg border-2 border-green-300 overflow-hidden bg-zinc-50">
                                    <img src="{{ $form->image->temporaryUrl() }}" alt="Preview"
                                        class="w-full h-full object-cover">
                                    <span
                                        class="absolute top-2 right-2 bg-green-500 text-white text-xs px-2 py-1 rounded-md font-medium shadow-sm">New</span>
                                    <button type="button" wire:click="removeNewImage('image')"
                                        class="absolute top-2 left-2 p-1.5 bg-red-500 hover:bg-red-600 text-white rounded-md">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            @elseif ($form->existing_image)
                                <div
                                    class="relative w-full max-w-[250px] aspect-square rounded-lg border-2 border-zinc-300 overflow-hidden bg-zinc-50">
                                    <img src="{{ $form->existing_image }}" alt="Current image"
                                        class="w-full h-full object-cover">
                                    <button type="button" wire:click="deleteExistingImage('existing_image')"
                                        class="absolute top-2 left-2 p-1.5 bg-red-500 hover:bg-red-600 text-white rounded-md">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                </flux:card>

                {{-- Gallery Images Section --}}
                <flux:card class="p-0">
                    <div class="px-3 py-2 border-b">
                        <flux:heading>Gallery Images</flux:heading>
                    </div>

                    <div class="p-5 @container/gallery">
                        {{-- Upload Area --}}
                        <flux:field>
                            <flux:label>Upload Gallery Images</flux:label>
                            <flux:input wire:model="form.gallery_images" type="file" accept="image/*" multiple />
                            <flux:error name="form.gallery_images.*" />
                        </flux:field>

                        {{-- Loading State --}}
                        <div wire:loading wire:target="form.gallery_images" class="mt-3">
                            <flux:badge variant="warning">Uploading images...</flux:badge>
                        </div>

                        {{-- Gallery Images Preview --}}
                        @if (!empty($form->gallery_images) || !empty($form->existingGalleryImages))
                            <div class="mt-4">
                                {{-- New images preview --}}
                                @if (!empty($form->gallery_images))
                                    <div class="grid grid-cols-2 gap-3 mb-3">
                                        @foreach ($form->gallery_images as $index => $galleryImage)
                                            <div
                                                class="relative aspect-square rounded-lg border-2 border-green-300 overflow-hidden bg-zinc-50">
                                                <img src="{{ $galleryImage->temporaryUrl() }}" alt="Gallery preview"
                                                    class="w-full h-full object-cover">
                                                <span
                                                    class="absolute top-2 right-2 bg-green-500 text-white text-xs px-2 py-1 rounded-md font-medium shadow-sm">New</span>
                                                <button type="button"
                                                    wire:click="removeNewImage('gallery_images', {{ $index }})"
                                                    class="absolute top-2 left-2 p-1 bg-red-500 hover:bg-red-600 text-white rounded-md">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                {{-- Existing images with drag-and-drop --}}
                                @if (!empty($form->existingGalleryImages))
                                    <div x-data="gallerySort()" x-init="init()" id="sortable-gallery"
                                        class="grid grid-cols-2 @md/gallery:grid-cols-3 gap-3">
                                        @foreach ($form->existingGalleryImages as $index => $existingImage)
                                            <div data-id="{{ $existingImage['id'] }}"
                                                class="sortable-item relative aspect-square group rounded-lg border-2 border-zinc-300 overflow-hidden bg-zinc-50 cursor-move">
                                                <img src="{{ is_array($existingImage) ? $existingImage['url'] : $existingImage->image_url }}"
                                                    alt="Gallery image" class="w-full h-full object-cover">
                                                <div
                                                    class="absolute top-2 left-2 bg-zinc-800 bg-opacity-60 text-white text-xs px-2 py-1 rounded">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M4 8h16M4 16h16M8 4v16m8-16v16" />
                                                    </svg>
                                                </div>
                                                <div
                                                    class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-40 transition-all flex items-center justify-center opacity-0 group-hover:opacity-100">
                                                    <button type="button"
                                                        wire:click="deleteExistingImage('existingGalleryImages', {{ $index }})"
                                                        class="px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white text-sm rounded-lg font-medium shadow-lg">
                                                        Remove
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </flux:card>

                {{-- Organization Section --}}
                <flux:card class="p-0">
                    <div class="px-3 py-2 border-b">
                        <flux:heading>Organization</flux:heading>
                    </div>

                    <section class="space-y-3 p-5">
                        {{-- Brand --}}
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-medium text-zinc-700">
                                    Brand
                                </label>
                                <button type="button" @click="$flux.modal('add-brand').show()"
                                    class="text-xs text-blue-600 hover:text-blue-700 font-medium">
                                    + Add New
                                </button>
                            </div>

                            {{-- <x-my-choices wire:model="form.brand_id" :options="$this->brands" single clearable /> --}}
                        </div>

                        {{-- Categories --}}
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-medium text-zinc-700">
                                    Categories
                                </label>
                                <button type="button" @click="$flux.modal('add-category').show()"
                                    class="text-xs text-blue-600 hover:text-blue-700 font-medium">
                                    + Add New
                                </button>
                            </div>

                            {{-- Selected Categories Display --}}
                            @if (!empty($form->selectedCategories))
                                <div
                                    class="flex flex-wrap gap-2 mb-3 p-2 bg-zinc-50 border border-zinc-200 rounded-lg">
                                    @foreach ($this->categories as $category)
                                        @if (in_array($category->id, $form->selectedCategories))
                                            <span
                                                class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-blue-700 bg-blue-100 rounded">
                                                {{ $category->indent }}{{ $category->name }}
                                                <button type="button"
                                                    wire:click="toggleCategory({{ $category->id }})"
                                                    class="text-blue-700 hover:text-blue-900">×</button>
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                            @endif

                            {{-- Combobox Trigger --}}
                            <div class="relative" x-data="{ open: false, search: '' }" @click.outside="open = false">
                                <button type="button" @click="open = !open"
                                    class="w-full flex items-center justify-between bg-zinc-50 border border-zinc-300 text-zinc-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5 hover:bg-zinc-100 transition-colors">
                                    <span class="text-zinc-600">
                                        @if (is_countable($form->selectedCategories) && count($form->selectedCategories) > 0)
                                            {{ count($form->selectedCategories) }} selected
                                        @else
                                            Select categories...
                                        @endif
                                    </span>
                                    <svg class="w-4 h-4 text-zinc-500 transition-transform"
                                        :class="{ 'rotate-180': open }" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                {{-- Combobox Dropdown --}}
                                <div x-show="open" x-transition
                                    class="absolute z-10 w-full mt-1 bg-white border border-zinc-300 rounded-lg shadow-lg max-h-96">
                                    {{-- Search Input --}}
                                    <div class="p-2 border-b border-zinc-200">
                                        <input type="text" x-model="search" placeholder="Search categories..."
                                            class="bg-zinc-50 border border-zinc-300 text-zinc-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2">
                                    </div>

                                    {{-- Categories List --}}
                                    <div class="max-h-48 overflow-y-auto p-2">
                                        @if ($this->categories->isEmpty())
                                            <p class="text-sm text-zinc-500 text-center py-4">No categories
                                                found</p>
                                        @else
                                            <div class="space-y-1">
                                                @foreach ($this->categories as $category)
                                                    <label
                                                        x-show="search === '' || '{{ strtolower($category->name) }}'.includes(search.toLowerCase())"
                                                        class="flex items-center p-2 hover:bg-zinc-50 rounded cursor-pointer transition-colors">
                                                        <input type="checkbox"
                                                            wire:click="toggleCategory({{ $category->id }})"
                                                            @if (in_array($category->id, $form->selectedCategories)) checked @endif
                                                            class="w-4 h-4 text-blue-600 bg-zinc-100 border-zinc-300 rounded focus:ring-blue-500">
                                                        <span class="ml-2 text-sm text-zinc-900">
                                                            {{ $category->indent }}{{ $category->name }}
                                                        </span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @error('selectedCategories')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Tags --}}
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-medium text-zinc-700">
                                    Tags
                                </label>
                                <button type="button" @click="$flux.modal('add-tag').show()"
                                    class="text-xs text-blue-600 hover:text-blue-700 font-medium">
                                    + Add New
                                </button>
                            </div>

                            {{-- Selected Tags Display --}}
                            @if (!empty($form->selectedTags))
                                <div
                                    class="flex flex-wrap gap-2 mb-3 p-2 bg-zinc-50 border border-zinc-200 rounded-lg">
                                    @foreach ($form->selectedTags as $selectedTagId)
                                        @php
                                            $selectedTag = $this->tags->firstWhere('id', $selectedTagId);
                                        @endphp
                                        @if ($selectedTag)
                                            <span
                                                class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-blue-700 bg-blue-100 rounded">
                                                {{ $selectedTag->name }}
                                                <button type="button" wire:click="toggleTag({{ $selectedTagId }})"
                                                    class="text-blue-700 hover:text-blue-900">×</button>
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                            @endif

                            {{-- Combobox Trigger --}}

                            {{-- <x-my-choices wire:model="form.selectedTags" :options="$this->tags" clearable /> --}}

                        </div>
                    </section>
                </flux:card>
            </div>
        </div>

    </form>

    {{-- Attribute Modal --}}
    {{-- @if ($showAttributeModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
            wire:click.self="$set('showAttributeModal', false)">
            <div class="bg-white rounded-lg p-6 w-full max-w-md">
                <h3 class="text-lg font-semibold text-zinc-900 mb-4">Create New Attribute</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 mb-2">Attribute Name</label>
                        <input type="text" wire:model="newAttributeName" placeholder="e.g., Size, Color"
                            class="bg-zinc-50 border border-zinc-300 text-zinc-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                        @error('newAttributeName')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex gap-3">
                        <button type="button" wire:click="addNewAttribute"
                            class="flex-1 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                            Create
                        </button>
                        <button type="button" wire:click="$set('showAttributeModal', false)"
                            class="flex-1 px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-50">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif --}}

    {{-- Bulk Pricing Modal --}}
    <flux:modal name="bulk-pricing" class="md:w-96">
        <form wire:submit="applyBulkPricing">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Set Bulk Pricing</flux:heading>
                    <flux:subheading>Apply pricing to all variations at once</flux:subheading>
                </div>

                <fieldset class="space-y-4">
                    <flux:input wire:model="bulkRegularPrice" type="number" step="0.01" label="Regular Price"
                        placeholder="0.00" />

                    <flux:input wire:model="bulkSalePrice" type="number" step="0.01" label="Sale Price"
                        placeholder="0.00" />

                    <flux:input wire:model="bulkCostPrice" type="number" step="0.01" label="Cost Price"
                        placeholder="0.00" />
                </fieldset>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:button type="button" variant="ghost" @click="$flux.modal('bulk-pricing').close()">
                        Cancel
                    </flux:button>

                    <flux:button type="submit" variant="primary">Apply to All</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    {{-- Bulk Stock Modal --}}
    <flux:modal name="bulk-stock" class="md:w-96">
        <form wire:submit="applyBulkStock">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Set Bulk Stock</flux:heading>
                    <flux:subheading>Apply stock quantity to all variations</flux:subheading>
                </div>

                <fieldset class="space-y-4">
                    <flux:input wire:model="bulkQuantity" type="number" label="Stock Quantity" placeholder="0" />

                    <flux:input wire:model="bulkLowStockThreshold" type="number" label="Low Stock Threshold"
                        placeholder="10" />
                </fieldset>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:button type="button" variant="ghost" @click="$flux.modal('bulk-stock').close()">
                        Cancel
                    </flux:button>

                    <flux:button type="submit" variant="primary">Apply to All</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    {{-- Bulk Dimensions Modal --}}
    <flux:modal name="bulk-dimensions" class="md:w-96">
        <form wire:submit="applyBulkDimensions">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Set Bulk Dimensions</flux:heading>
                    <flux:subheading>Apply dimensions to all variations</flux:subheading>
                </div>

                <fieldset class="space-y-4">
                    <flux:input wire:model="bulkWeight" type="number" step="0.01" label="Weight (kg)"
                        placeholder="0.00" />

                    <flux:field>
                        <flux:label>Dimensions (cm)</flux:label>
                        <flux:input.group>
                            <flux:input wire:model="bulkLength" type="number" step="0.01"
                                placeholder="Length" />
                            <flux:input wire:model="bulkWidth" type="number" step="0.01" placeholder="Width" />
                            <flux:input wire:model="bulkHeight" type="number" step="0.01"
                                placeholder="Height" />
                        </flux:input.group>
                    </flux:field>
                </fieldset>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:button type="button" variant="ghost" @click="$flux.modal('bulk-dimensions').close()">
                        Cancel
                    </flux:button>

                    <flux:button type="submit" variant="primary">Apply to All</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    {{-- Add Brand Modal --}}
    <flux:modal name="add-brand" class="md:w-96">
        <form wire:submit="addNewBrand">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Create New Brand</flux:heading>
                    <flux:subheading>Add a new brand to your catalog</flux:subheading>
                </div>

                <fieldset class="space-y-4">
                    <flux:input wire:model="newBrandName" label="Brand Name *" placeholder="e.g., Nike, Apple"
                        required />
                </fieldset>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:button type="button" variant="ghost" @click="$flux.modal('add-brand').close()">
                        Cancel
                    </flux:button>

                    <flux:button type="submit" variant="primary">Create Brand</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    {{-- Add Category Modal --}}
    <flux:modal name="add-category" class="md:w-96">
        <form wire:submit="addNewCategory">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Create New Category</flux:heading>
                    <flux:subheading>Add a new category to your catalog</flux:subheading>
                </div>

                <fieldset class="space-y-4">
                    <flux:input wire:model="newCategoryName" label="Category Name *"
                        placeholder="e.g., Electronics, Clothing" required />
                </fieldset>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:button type="button" variant="ghost" @click="$flux.modal('add-category').close()">
                        Cancel
                    </flux:button>

                    <flux:button type="submit" variant="primary">Create Category</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    {{-- Add Tag Modal --}}
    <flux:modal name="add-tag" class="md:w-96">
        <form wire:submit="addNewTag">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Create New Tag</flux:heading>
                    <flux:subheading>Add a new tag to your catalog</flux:subheading>
                </div>

                <fieldset class="space-y-4">
                    <flux:input wire:model="newTagName" label="Tag Name *" placeholder="e.g., Featured, Sale"
                        required />
                </fieldset>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:button type="button" variant="ghost" @click="$flux.modal('add-tag').close()">
                        Cancel
                    </flux:button>

                    <flux:button type="submit" variant="primary">Create Tag</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
</div>
