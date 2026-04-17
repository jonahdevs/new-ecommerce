@php
    $selectClass =
        'w-full rounded-md border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-1 focus:ring-zinc-400 dark:focus:ring-zinc-500';
@endphp


<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- MAIN COLUMN                                               --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="lg:col-span-2 space-y-5" x-data="{ open: true }">

        {{--  Product Name & Identity  --}}
        <flux:card class="p-0">
            <div class="flex items-center justify-between px-3 py-2 dark:border-zinc-600" :class="{ 'border-b ': open }">
                <flux:heading>Basic Information</flux:heading>

                <flux:button icon="chevron-down" size="xs" variant="ghost"
                    class="cursor-pointer transition-transform duration-300" @click="open = !open">
                    <x-slot name="icon">
                        <flux:icon.chevron-down variant="outline" class="size-4 text-zinc-400"
                            x-bind:class="{ 'rotate-180': open }" />
                    </x-slot>
                </flux:button>
            </div>

            <div x-show="open" x-collapse class="p-5 grid grid-cols-1 md:grid-cols-2 gap-5">

                <flux:field class="md:col-span-2">
                    <flux:label class="text-base font-semibold">Product Name <span class="text-red-500 ms-0.5">*</span>
                    </flux:label>
                    <flux:input wire:model.live="form.name" size="lg"
                        placeholder="e.g. Stainless Steel Commercial Stock Pot" autofocus />
                    <flux:error name="form.name" />
                </flux:field>

                <flux:field>
                    <div class="flex items-center gap-1.5 mb-1">
                        <flux:label>Model Number</flux:label>
                        <flux:tooltip content="The manufacturer's model or part number for this product.">
                            <flux:icon.information-circle variant="outline"
                                class="size-3.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 cursor-help" />
                        </flux:tooltip>
                    </div>
                    <flux:input wire:model="form.model_number" placeholder="e.g. SP-40L-SS" />
                    <flux:error name="form.model_number" />
                </flux:field>

                <flux:field>
                    <div class="flex items-center gap-1.5 mb-1">
                        <flux:label>Slug</flux:label>
                        <flux:tooltip content="URL-friendly identifier. Leave blank to auto-generate from the name.">
                            <flux:icon.information-circle variant="outline"
                                class="size-3.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 cursor-help" />
                        </flux:tooltip>
                    </div>
                    <flux:input wire:model="form.slug" placeholder="auto-generated-from-name" />
                    <flux:error name="form.slug" />
                </flux:field>

            </div>
        </flux:card>


        {{--  Short Description  --}}
        <flux:card class="p-0" x-data="{ open: true }">
            <div class="flex items-center justify-between px-3 py-2 dark:border-zinc-600"
                :class="{ 'border-b ': open }">
                <flux:heading>Short Description</flux:heading>

                <flux:button icon="chevron-down" size="xs" variant="ghost"
                    class="cursor-pointer transition-transform duration-300" @click="open = !open">
                    <x-slot name="icon">
                        <flux:icon.chevron-down variant="outline" class="size-4 text-zinc-400"
                            x-bind:class="{ 'rotate-180': open }" />
                    </x-slot>
                </flux:button>
            </div>
            <div x-show="open" x-collapse class="p-5">
                <x-rich-editor model="form.short_description"
                    placeholder="A short summary shown in listings and previews..." :value="$form->short_description" />
                <flux:error name="form.short_description" />
            </div>
        </flux:card>

        {{-- Product Data  --}}
        <flux:card class="p-0" x-data="{ open: true, tab: 'general' }">

            {{-- Card header --}}
            <div class="flex items-center justify-between px-3 py-2 border-b dark:border-zinc-600">
                <div class="flex items-center gap-2">
                    <flux:heading class="shrink-0">Product Data</flux:heading>
                    <flux:select wire:model="form.type" size="xs">
                        @foreach (App\Enums\ProductType::cases() as $t)
                            <flux:select.option value="{{ $t->value }}">{{ $t->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <flux:button size="xs" variant="ghost" class="cursor-pointer" @click="open = !open">
                    <x-slot name="icon">
                        <flux:icon.chevron-down variant="outline"
                            class="size-4 text-zinc-400 transition-transform duration-200"
                            x-bind:class="{ 'rotate-180': open }" />
                    </x-slot>
                </flux:button>
            </div>

            {{-- Tabbed body --}}
            <div x-show="open" x-collapse>
                <div class="flex min-h-64">

                    {{-- ── Vertical tab nav ── --}}
                    <nav class="w-44 shrink-0 border-r dark:border-zinc-600 py-1">
                        @php
                            $tabs = [
                                ['id' => 'general', 'label' => 'General', 'icon' => 'currency-dollar'],
                                ['id' => 'inventory', 'label' => 'Inventory', 'icon' => 'archive-box'],
                                ['id' => 'shipping', 'label' => 'Shipping', 'icon' => 'truck'],
                                ['id' => 'linked', 'label' => 'Linked Products', 'icon' => 'link'],
                                ['id' => 'attributes', 'label' => 'Attributes', 'icon' => 'tag'],
                                ['id' => 'variations', 'label' => 'Variations', 'icon' => 'squares-2x2'],
                                ['id' => 'advanced', 'label' => 'Advanced', 'icon' => 'cog-6-tooth'],
                            ];
                        @endphp

                        @foreach ($tabs as $t)
                            <button type="button" @click="tab = '{{ $t['id'] }}'"
                                class="flex items-center gap-2 w-full px-3 py-2 text-sm text-left transition-colors cursor-pointer"
                                :class="tab === '{{ $t['id'] }}'
                                    ?
                                    'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 font-medium border-r-2 border-zinc-800 dark:border-zinc-200' :
                                    'text-zinc-500 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 hover:text-zinc-700 dark:hover:text-zinc-300'">
                                <flux:icon :name="$t['icon']" variant="outline" class="size-4 shrink-0" />
                                {{ $t['label'] }}
                            </button>
                        @endforeach
                    </nav>

                    {{-- ── Tab panels ── --}}
                    <div class="flex-1 p-5">

                        {{-- ── General ── --}}
                        <div x-show="tab === 'general'" x-cloak class="space-y-5">
                            <div class="grid grid-cols-2 gap-5">
                                <flux:field>
                                    <div class="flex items-center gap-1.5 mb-1">
                                        <flux:label>Regular Price ({{ get_currency_symbol() }})</flux:label>
                                        <flux:tooltip content="The standard selling price before any discounts.">
                                            <flux:icon.information-circle variant="outline"
                                                class="size-3.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 cursor-help" />
                                        </flux:tooltip>
                                    </div>
                                    <flux:input wire:model="form.price" type="number" step="0.01" min="0"
                                        placeholder="0.00" />
                                    <flux:error name="form.price" />
                                </flux:field>

                                <flux:field>
                                    <div class="flex items-center gap-1.5 mb-1">
                                        <flux:label>Sale Price ({{ get_currency_symbol() }})</flux:label>
                                        <flux:tooltip content="Discounted price. Leave blank if no active sale.">
                                            <flux:icon.information-circle variant="outline"
                                                class="size-3.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 cursor-help" />
                                        </flux:tooltip>
                                    </div>
                                    <flux:input wire:model="form.sale_price" type="number" step="0.01"
                                        min="0" placeholder="0.00" />
                                    <flux:error name="form.sale_price" />
                                </flux:field>
                            </div>

                            <flux:field>
                                <div class="flex items-center gap-1.5 mb-1">
                                    <flux:label>Tax Class</flux:label>
                                    <flux:tooltip content="The tax class that applies to this product.">
                                        <flux:icon.information-circle variant="outline"
                                            class="size-3.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 cursor-help" />
                                    </flux:tooltip>
                                </div>

                                <flux:select wire:model="form.tax_class_id">
                                    <flux.select.option value="">— None —</flux.select.option>
                                    @foreach ($this->taxClasses as $tc)
                                        <flux.select.option value="{{ $tc->id }}">{{ $tc->name }}
                                            ({{ $tc->rateLabel() }})
                                        </flux.select.option>
                                    @endforeach
                                </flux:select>

                                <flux:error name="form.tax_class_id" />
                            </flux:field>
                        </div>

                        {{-- ── Inventory ── --}}
                        <div x-show="tab === 'inventory'" x-cloak class="space-y-5">
                            <flux:field>
                                <div class="flex items-center gap-1.5 mb-1">
                                    <flux:label>SKU</flux:label>
                                    <flux:tooltip content="Stock Keeping Unit — a unique identifier for this product.">
                                        <flux:icon.information-circle variant="outline"
                                            class="size-3.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 cursor-help" />
                                    </flux:tooltip>
                                </div>
                                <flux:input wire:model="form.sku" placeholder="e.g. KIT-POT-001" />
                                <flux:error name="form.sku" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Manage Stock</flux:label>
                                <flux:checkbox wire:model="form.manage_stock"
                                    label="Track inventory for this product" />
                            </flux:field>

                            <div x-data x-show="$wire.form.manage_stock" x-cloak>
                                <flux:field>
                                    <div class="flex items-center gap-1.5 mb-1">
                                        <flux:label>Stock Quantity</flux:label>
                                        <flux:tooltip
                                            content="Current units in stock. Updated automatically when orders are placed.">
                                            <flux:icon.information-circle variant="outline"
                                                class="size-3.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 cursor-help" />
                                        </flux:tooltip>
                                    </div>
                                    <flux:input wire:model="form.stock_quantity" type="number" min="0"
                                        placeholder="0" />
                                    <flux:error name="form.stock_quantity" />
                                </flux:field>
                            </div>
                        </div>

                        {{-- ── Shipping ── --}}
                        <div x-show="tab === 'shipping'" x-cloak class="space-y-5">
                            <div class="grid grid-cols-2 gap-5">
                                <flux:field>
                                    <div class="flex items-center gap-1.5 mb-1">
                                        <flux:label>Weight (kg)</flux:label>
                                        <flux:tooltip
                                            content="Product weight in kilograms. Used for shipping rate calculations.">
                                            <flux:icon.information-circle variant="outline"
                                                class="size-3.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 cursor-help" />
                                        </flux:tooltip>
                                    </div>
                                    <flux:input wire:model="form.weight" type="number" step="0.001"
                                        min="0" placeholder="0.000" />
                                    <flux:error name="form.weight" />
                                </flux:field>
                            </div>

                            <div>
                                <flux:label class="block mb-2">Dimensions (cm)</flux:label>
                                <div class="grid grid-cols-3 gap-3">
                                    <flux:field>
                                        <flux:input wire:model="form.height" type="number" step="0.01"
                                            min="0" placeholder="Height" />
                                        <flux:error name="form.height" />
                                    </flux:field>
                                    <flux:field>
                                        <flux:input wire:model="form.width" type="number" step="0.01"
                                            min="0" placeholder="Width" />
                                        <flux:error name="form.width" />
                                    </flux:field>
                                    <flux:field>
                                        <flux:input wire:model="form.length" type="number" step="0.01"
                                            min="0" placeholder="Length" />
                                        <flux:error name="form.length" />
                                    </flux:field>
                                </div>
                            </div>

                            <flux:field>
                                <flux:label>Shipping Information</flux:label>
                                <flux:textarea wire:model="form.shipping_information" rows="3"
                                    placeholder="Delivery times, carriers, special handling notes..." />
                            </flux:field>

                            <flux:field>
                                <flux:label>Warranty Information</flux:label>
                                <flux:textarea wire:model="form.warranty_information" rows="3"
                                    placeholder="Warranty period, coverage, and claim process..." />
                            </flux:field>

                            <flux:field>
                                <flux:label>Return Policy</flux:label>
                                <flux:textarea wire:model="form.return_policy" rows="3"
                                    placeholder="Return window, conditions, and process..." />
                            </flux:field>
                        </div>

                        {{-- ── Linked Products ── --}}
                        <div x-show="tab === 'linked'" x-cloak class="space-y-6">
                            @foreach ([['type' => 'upsell', 'label' => 'Upsells', 'query' => 'upsellQuery', 'results' => 'upsellResults', 'list' => 'upsell_products', 'desc' => 'Premium or complementary products shown on the product page.'], ['type' => 'cross_sell', 'label' => 'Cross-sells', 'query' => 'crossSellQuery', 'results' => 'crossSellResults', 'list' => 'cross_sell_products', 'desc' => 'Products promoted on the cart page alongside this item.'], ['type' => 'accessory', 'label' => 'Accessories', 'query' => 'accessoryQuery', 'results' => 'accessoryResults', 'list' => 'accessory_products', 'desc' => 'Optional add-ons shown on the product detail page.']] as $lp)
                                <div>
                                    <flux:heading size="sm">{{ $lp['label'] }}</flux:heading>
                                    <flux:text class="text-xs text-zinc-500 mb-2">{{ $lp['desc'] }}</flux:text>

                                    {{-- Search input --}}
                                    <div class="relative mb-2" x-data="{ open: false }"
                                        x-on:click.outside="open = false">
                                        <flux:input wire:model.live.debounce.300ms="{{ $lp['query'] }}"
                                            placeholder="Search by name or SKU..." icon="magnifying-glass"
                                            @focus="open = true" />

                                        @if (count($this->{$lp['results']}) > 0)
                                            <div x-show="open"
                                                class="absolute z-20 w-full mt-1 bg-white dark:bg-zinc-800 border dark:border-zinc-700 rounded-lg shadow-lg overflow-hidden">
                                                @foreach ($this->{$lp['results']} as $p)
                                                    <button type="button"
                                                        wire:click="addLinkedProduct({{ $p['id'] }}, '{{ $lp['type'] }}')"
                                                        @click="open = false"
                                                        class="flex items-center gap-2 w-full px-3 py-2 text-sm hover:bg-zinc-50 dark:hover:bg-zinc-700 text-left cursor-pointer">
                                                        <span
                                                            class="font-medium text-zinc-900 dark:text-zinc-100 flex-1">{{ $p['name'] }}</span>
                                                        @if ($p['sku'] ?? null)
                                                            <span
                                                                class="text-xs text-zinc-400">{{ $p['sku'] }}</span>
                                                        @endif
                                                    </button>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Selected products --}}
                                    @if (!empty($form->{$lp['list']}))
                                        <div class="space-y-1">
                                            @foreach ($form->{$lp['list']} as $idx => $p)
                                                <div class="flex items-center justify-between px-3 py-2 bg-zinc-50 dark:bg-zinc-800/60 rounded-md border dark:border-zinc-700"
                                                    wire:key="{{ $lp['type'] }}-{{ $p['id'] }}">
                                                    <div class="text-sm">
                                                        <span
                                                            class="font-medium text-zinc-800 dark:text-zinc-200">{{ $p['name'] }}</span>
                                                        @if ($p['sku'] ?? null)
                                                            <span
                                                                class="text-zinc-400 ms-2">{{ $p['sku'] }}</span>
                                                        @endif
                                                    </div>
                                                    <flux:button type="button" icon="x-mark" variant="ghost"
                                                        size="xs"
                                                        class="text-zinc-400 hover:text-red-500 cursor-pointer"
                                                        wire:click="removeLinkedProduct({{ $idx }}, '{{ $lp['type'] }}')" />
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-xs text-zinc-400">None added yet.</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        {{-- ── Attributes ── --}}
                        <div x-show="tab === 'attributes'" x-cloak class="space-y-4 p-1">

                            <div class="text-sm text-zinc-500">
                                Add descriptive pieces of information that customers can use to search for this product,
                                such as "Material" or "Color".
                            </div>

                            {{-- Toolbar --}}
                            <div class="flex items-center gap-3 flex-wrap">
                                <flux:button type="button" wire:click="addNewAttribute" icon="plus"
                                    size="sm" class="cursor-pointer">
                                    Add New
                                </flux:button>

                                <div x-data>
                                    <select x-ref="attrSelect"
                                        @change="if ($event.target.value) { $wire.addExistingAttribute(parseInt($event.target.value)).then(() => { $refs.attrSelect.value = '' }) }"
                                        class="{{ $selectClass }} !py-1.5 !text-sm">
                                        <option value="">Add existing...</option>
                                        @foreach ($this->selectableAttributes as $selAttr)
                                            <option value="{{ $selAttr->id }}">{{ ucfirst($selAttr->name) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                @if (!empty($form->product_attributes))
                                    <span class="ms-auto text-sm text-zinc-500">
                                        {{ count($form->product_attributes) }} attribute(s)
                                    </span>
                                @endif
                            </div>

                            {{-- Attribute cards --}}
                            @foreach ($form->product_attributes as $attrIndex => $attr)
                                <flux:card class="p-0" wire:key="attr-row-{{ $attrIndex }}"
                                    x-data="{ open: {{ $loop->first ? 'true' : 'false' }} }">

                                    {{-- Card header --}}
                                    <div class="flex items-center gap-4 px-4 py-2"
                                        :class="{ 'border-b dark:border-zinc-700': open }">
                                        <flux:heading>
                                            {{ $attr['name'] ? ucfirst($attr['name']) : 'New Attribute' }}
                                        </flux:heading>

                                        <div class="ms-auto flex items-center gap-1">
                                            <flux:button size="xs" icon="trash" icon-variant="outline"
                                                type="button" variant="ghost"
                                                wire:click="removeSelectedAttribute({{ $attrIndex }})"
                                                wire:confirm="Remove this attribute?"
                                                class="text-red-500! cursor-pointer" />

                                            <flux:button icon="chevron-down" size="xs" variant="ghost"
                                                type="button"
                                                class="transition-transform duration-300 cursor-pointer"
                                                x-bind:class="{ 'rotate-180': open }" @click="open = !open" />
                                        </div>
                                    </div>

                                    {{-- Card body --}}
                                    <div x-show="open" x-collapse class="grid grid-cols-3 gap-5 p-5">

                                        {{-- Left: name + checkboxes --}}
                                        <div class="col-span-1 space-y-4">
                                            @if ($attr['is_new'] ?? false)
                                                <flux:input label="Name"
                                                    wire:model="form.product_attributes.{{ $attrIndex }}.name"
                                                    placeholder="e.g. Size, Material" />
                                            @else
                                                <flux:field>
                                                    <flux:label>Name</flux:label>
                                                    <p class="text-sm font-semibold">{{ ucfirst($attr['name']) }}</p>
                                                </flux:field>
                                            @endif

                                            <flux:checkbox
                                                wire:model="form.product_attributes.{{ $attrIndex }}.is_visible"
                                                label="Visible on the product page" />

                                            <flux:checkbox
                                                wire:model="form.product_attributes.{{ $attrIndex }}.is_variation_attribute"
                                                label="Used for variations" />
                                        </div>

                                        {{-- Right: values --}}
                                        <div class="col-span-2">
                                            @if ($attr['is_new'] ?? false)
                                                <flux:textarea label="Value(s)"
                                                    wire:model="form.product_attributes.{{ $attrIndex }}.values"
                                                    placeholder="Enter values separated by '|'  e.g. Blue | Large | Medium"
                                                    rows="3" />
                                            @else
                                                @php $attrValues = $this->getProductAttributeValues($attr['attribute_id']); @endphp
                                                @if (!empty($attrValues))
                                                    <flux:field>
                                                        <flux:label>Values</flux:label>
                                                        <div
                                                            class="flex flex-wrap gap-x-4 gap-y-2 mt-2 max-h-40 overflow-y-auto">
                                                            @foreach ($attrValues as $val)
                                                                <label
                                                                    class="flex items-center gap-1.5 text-sm cursor-pointer">
                                                                    <flux:checkbox
                                                                        wire:model="form.product_attributes.{{ $attrIndex }}.values"
                                                                        :value="$val['id']" />
                                                                    {{ $val['label'] }}
                                                                </label>
                                                            @endforeach
                                                        </div>
                                                    </flux:field>
                                                @else
                                                    <p class="text-xs text-zinc-400 mt-6">
                                                        This attribute has no values. Add values in the Attributes
                                                        catalogue first.
                                                    </p>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </flux:card>
                            @endforeach

                            @if (empty($form->product_attributes))
                                <div class="text-center py-8 text-zinc-400">
                                    <flux:icon.tag class="size-8 mx-auto mb-2 opacity-40" />
                                    <p class="text-sm">No attributes added yet.</p>
                                </div>
                            @endif
                        </div>

                        {{--  Variations  --}}
                        <div x-show="tab === 'variations'" x-cloak>
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
                            }" class="space-y-4">

                                {{-- Toolbar --}}
                                <div class="flex items-center gap-2 flex-wrap">
                                    @if (!empty($form->variations))
                                        <flux:button size="sm" type="button" icon="arrow-path"
                                            wire:click="regenerateVariations"
                                            wire:confirm="This will add any missing combinations from your current attributes. Existing variations are unchanged. Continue?"
                                            class="cursor-pointer">
                                            Regenerate
                                        </flux:button>
                                    @else
                                        <flux:button size="sm" type="button" wire:click="generateVariations"
                                            icon="sparkles" class="cursor-pointer">
                                            Generate Variations
                                        </flux:button>
                                    @endif

                                    <flux:button size="sm" type="button" wire:click="addVariant"
                                        icon="plus" class="cursor-pointer">
                                        Add Manual
                                    </flux:button>

                                    @if (!empty($form->variations))
                                        <flux:dropdown>
                                            <flux:button size="sm" icon:trailing="chevron-down"
                                                class="cursor-pointer">Bulk Actions</flux:button>
                                            <flux:menu class="min-w-40">
                                                <flux:menu.group heading="Status">
                                                    <flux:menu.item wire:click="toggleAllVariantsActive"
                                                        class="cursor-pointer">
                                                        Toggle "Active"
                                                    </flux:menu.item>
                                                </flux:menu.group>
                                                <flux:menu.group heading="Inventory">
                                                    <flux:menu.item wire:click="toggleAllVariantsManageStock"
                                                        class="cursor-pointer">
                                                        Toggle "Manage stock"
                                                    </flux:menu.item>
                                                    <flux:menu.item wire:click="setAllVariantsStockStatus('in_stock')"
                                                        class="cursor-pointer">
                                                        Set status — In stock
                                                    </flux:menu.item>
                                                    <flux:menu.item
                                                        wire:click="setAllVariantsStockStatus('out_of_stock')"
                                                        class="cursor-pointer">
                                                        Set status — Out of stock
                                                    </flux:menu.item>
                                                    <flux:menu.item wire:click="setAllVariantsStockStatus('backorder')"
                                                        class="cursor-pointer">
                                                        Set status — Backorder
                                                    </flux:menu.item>
                                                </flux:menu.group>
                                                <flux:menu.group heading="Danger">
                                                    <flux:menu.item wire:click="clearAllVariants"
                                                        wire:confirm="Delete all variations? This cannot be undone."
                                                        variant="danger" class="cursor-pointer">
                                                        Delete all variations
                                                    </flux:menu.item>
                                                </flux:menu.group>
                                            </flux:menu>
                                        </flux:dropdown>

                                        <div class="ms-auto flex items-center gap-1.5 text-xs text-zinc-500">
                                            <span>{{ count($form->variations) }} variation(s)</span>
                                            <span>(</span>
                                            <button type="button" @click="allCollapsed = true"
                                                class="text-blue-500 italic cursor-pointer hover:underline">
                                                Expand all
                                            </button>
                                            <span>/</span>
                                            <button type="button" @click="allCollapsed = false"
                                                class="text-blue-500 italic cursor-pointer hover:underline">
                                                Collapse all
                                            </button>
                                            <span>)</span>
                                        </div>
                                    @endif
                                </div>

                                {{-- Unpriced variants warning --}}
                                @if ($this->unpricedVariantsCount > 0)
                                    <flux:callout variant="warning" icon="exclamation-circle" inline>
                                        <flux:callout.heading>
                                            {{ $this->unpricedVariantsCount }}
                                            variation{{ $this->unpricedVariantsCount > 1 ? 's do' : ' does' }} not have
                                            a price set.
                                        </flux:callout.heading>
                                    </flux:callout>
                                @endif

                                {{-- Variation list --}}
                                @if (!empty($form->variations))
                                    <div class="space-y-3">
                                        @foreach ($form->variations as $vIdx => $variation)
                                            <flux:card class="p-0"
                                                wire:key="var-{{ $vIdx }}-{{ implode('-', (array) ($variation['attributes'] ?? [])) ?: $vIdx }}">
                                                <div x-data="{
                                                    collapsed: {{ $loop->first ? 'true' : 'false' }},
                                                    readonlyName: @js(!empty($variation['name']))
                                                }"
                                                    @toggle-all-variants.window="collapsed = $event.detail.collapsed">

                                                    {{-- Variant header --}}
                                                    <div class="flex items-center justify-between px-4 py-2"
                                                        :class="{ 'border-b dark:border-zinc-700': collapsed }">
                                                        <flux:heading>
                                                            {{ $variation['name'] ?: 'Manual Variation #' . ($vIdx + 1) }}
                                                        </flux:heading>
                                                        <div class="flex items-center gap-2">
                                                            <flux:badge size="sm"
                                                                color="{{ $variation['is_active'] ? 'green' : 'zinc' }}">
                                                                {{ $variation['is_active'] ? 'Active' : 'Inactive' }}
                                                            </flux:badge>
                                                            <flux:button size="xs" icon="trash"
                                                                icon-variant="outline" type="button" variant="ghost"
                                                                wire:click="removeVariant({{ $vIdx }})"
                                                                wire:confirm="Remove this variation?"
                                                                class="text-red-500! cursor-pointer" />
                                                            <flux:button icon="chevron-down" size="xs"
                                                                variant="ghost" type="button"
                                                                class="transition-transform duration-300 cursor-pointer"
                                                                x-bind:class="{ 'rotate-180': collapsed }"
                                                                @click="collapsed = !collapsed" />
                                                        </div>
                                                    </div>

                                                    {{-- Variant body --}}
                                                    <div x-cloak x-show="collapsed" x-collapse class="space-y-5 p-5">

                                                        {{-- Image + SKU row --}}
                                                        <div class="grid grid-cols-2 gap-5 items-start">

                                                            {{-- Variant image --}}
                                                            <div class="space-y-2">
                                                                <flux:label>Variation Image</flux:label>
                                                                <input type="file" class="hidden"
                                                                    id="variant-img-{{ $vIdx }}"
                                                                    wire:model="variantImages.{{ $vIdx }}"
                                                                    accept="image/*" />

                                                                <flux:skeleton wire:loading
                                                                    wire:target="variantImages.{{ $vIdx }}"
                                                                    class="w-24 h-24 rounded-md" animate="shimmer" />

                                                                <div wire:loading.remove
                                                                    wire:target="variantImages.{{ $vIdx }}">
                                                                    @if (!empty($variantImages[$vIdx] ?? null))
                                                                        <div class="relative w-24 h-24 rounded-md overflow-hidden group cursor-pointer"
                                                                            @click="document.getElementById('variant-img-{{ $vIdx }}').click()">
                                                                            <img src="{{ $variantImages[$vIdx]->temporaryUrl() }}"
                                                                                class="w-full h-full object-cover"
                                                                                alt="Variant image">
                                                                            <div
                                                                                class="absolute inset-0 group-hover:bg-black/40 transition-all flex items-center justify-center">
                                                                                <flux:icon.pencil
                                                                                    class="opacity-0 group-hover:opacity-100 text-white size-4" />
                                                                            </div>
                                                                        </div>
                                                                    @elseif (!empty($variation['image_path']))
                                                                        <div class="relative w-24 h-24 rounded-md overflow-hidden border-2 border-zinc-200 dark:border-zinc-700 group cursor-pointer"
                                                                            @click="document.getElementById('variant-img-{{ $vIdx }}').click()">
                                                                            <img src="{{ \Storage::url($variation['image_path']) }}"
                                                                                class="w-full h-full object-cover"
                                                                                alt="Variant image">
                                                                            <div
                                                                                class="absolute inset-0 group-hover:bg-black/40 transition-all flex items-center justify-center">
                                                                                <flux:icon.pencil
                                                                                    class="opacity-0 group-hover:opacity-100 text-white size-4" />
                                                                            </div>
                                                                        </div>
                                                                    @else
                                                                        <button type="button"
                                                                            @click="document.getElementById('variant-img-{{ $vIdx }}').click()"
                                                                            class="w-24 h-24 rounded-md border-2 border-dashed border-zinc-300 dark:border-zinc-600 flex flex-col items-center justify-center gap-1 dark:bg-zinc-500 hover:border-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-all cursor-pointer">
                                                                            <flux:icon.photo
                                                                                class="size-6 text-zinc-400" />
                                                                            <span class="text-xs text-zinc-400">Add
                                                                                image</span>
                                                                        </button>
                                                                    @endif

                                                                    @if (!empty($variantImages[$vIdx] ?? null) || !empty($variation['image_path']))
                                                                        <button type="button"
                                                                            wire:click="removeVariantImage({{ $vIdx }})"
                                                                            class="text-xs text-red-500 cursor-pointer mt-1 block hover:underline">
                                                                            Remove image
                                                                        </button>
                                                                    @endif
                                                                </div>
                                                            </div>

                                                            {{-- SKU --}}
                                                            <flux:input
                                                                wire:model="form.variations.{{ $vIdx }}.sku"
                                                                label="SKU"
                                                                placeholder="Leave blank to auto-generate" />
                                                        </div>

                                                        {{-- Flags --}}
                                                        <div
                                                            class="flex items-center gap-5 border-y py-3 dark:border-zinc-700">
                                                            <flux:checkbox
                                                                wire:model="form.variations.{{ $vIdx }}.is_active"
                                                                label="Active" />
                                                            <flux:checkbox
                                                                wire:model="form.variations.{{ $vIdx }}.manage_stock"
                                                                label="Manage Stock" />
                                                            <flux:checkbox
                                                                wire:model="form.variations.{{ $vIdx }}.is_default"
                                                                label="Default variation" />
                                                        </div>

                                                        {{-- Variation name --}}
                                                        <flux:input
                                                            wire:model="form.variations.{{ $vIdx }}.name"
                                                            ::readonly="readonlyName" label="Variation Name">
                                                            <x-slot name="iconTrailing">
                                                                <flux:button size="sm" variant="subtle"
                                                                    @click="readonlyName = !readonlyName"
                                                                    icon="pencil" icon-variant="outline"
                                                                    class="-mr-1 cursor-pointer" />
                                                            </x-slot>
                                                        </flux:input>

                                                        {{-- Pricing --}}
                                                        <div class="grid grid-cols-3 gap-3">
                                                            <flux:input type="number" step="0.01"
                                                                wire:model="form.variations.{{ $vIdx }}.price"
                                                                label="Price ({{ get_currency_symbol() }})"
                                                                placeholder="0.00" />
                                                            <flux:input type="number" step="0.01"
                                                                wire:model="form.variations.{{ $vIdx }}.sale_price"
                                                                label="Sale Price ({{ get_currency_symbol() }})"
                                                                placeholder="0.00" />
                                                            <flux:input type="number" step="0.01"
                                                                wire:model="form.variations.{{ $vIdx }}.cost_price"
                                                                label="Cost Price ({{ get_currency_symbol() }})"
                                                                placeholder="0.00" />
                                                        </div>

                                                        {{-- Stock — when manage_stock ON --}}
                                                        <div x-show="$wire.form.variations[{{ $vIdx }}].manage_stock"
                                                            x-cloak class="space-y-3">
                                                            <div class="grid grid-cols-2 gap-3">
                                                                <flux:input type="number" min="0"
                                                                    wire:model="form.variations.{{ $vIdx }}.stock_quantity"
                                                                    label="Stock Quantity" />
                                                                <flux:input type="number" min="0"
                                                                    wire:model="form.variations.{{ $vIdx }}.low_stock_threshold"
                                                                    label="Low Stock Threshold"
                                                                    placeholder="e.g. 5" />
                                                            </div>

                                                            <flux:field>
                                                                <flux:label>Allow Backorders</flux:label>
                                                                <select
                                                                    wire:model="form.variations.{{ $vIdx }}.allow_backorders"
                                                                    class="{{ $selectClass }}">
                                                                    <option value="0">Do not allow</option>
                                                                    <option value="1">Allow</option>
                                                                </select>
                                                            </flux:field>

                                                            {{-- Backorder details --}}
                                                            <div x-show="$wire.form.variations[{{ $vIdx }}].allow_backorders == '1'"
                                                                x-cloak
                                                                class="space-y-3 border-l-2 border-amber-300 pl-4">
                                                                <flux:textarea
                                                                    wire:model="form.variations.{{ $vIdx }}.backorder_message"
                                                                    label="Backorder Message" rows="2"
                                                                    placeholder="e.g. Ships in 2–3 weeks" />
                                                                <div class="grid grid-cols-2 gap-3">
                                                                    <flux:input type="number" min="1"
                                                                        wire:model="form.variations.{{ $vIdx }}.max_backorder_quantity"
                                                                        label="Max Backorder Qty"
                                                                        placeholder="Unlimited" />
                                                                    <flux:input type="date"
                                                                        wire:model="form.variations.{{ $vIdx }}.expected_restock_date"
                                                                        label="Expected Restock Date" />
                                                                </div>
                                                            </div>
                                                        </div>

                                                        {{-- Stock status — when manage_stock OFF --}}
                                                        <div x-show="!$wire.form.variations[{{ $vIdx }}].manage_stock"
                                                            x-cloak>
                                                            <flux:select label="Stock Status"
                                                                wire:model="form.variations.{{ $vIdx }}.stock_status">
                                                                <flux:select.option value="in_stock">In Stock
                                                                </flux:select.option>
                                                                <flux:select.option value="out_of_stock">Out of Stock
                                                                </flux:select.option>
                                                                <flux:select.option value="backorder">Backorder
                                                                </flux:select.option>
                                                            </flux:select>
                                                        </div>

                                                        {{-- Shipping --}}
                                                        <div class="grid grid-cols-2 gap-3">
                                                            <flux:input label="Weight ({{ $weightUnit }})"
                                                                type="number" step="0.001"
                                                                wire:model="form.variations.{{ $vIdx }}.weight"
                                                                placeholder="0.000" />
                                                            <flux:field>
                                                                <flux:label>Dimensions — L × W × H
                                                                    ({{ $dimensionUnit }})
                                                                </flux:label>
                                                                <flux:input.group>
                                                                    <flux:input placeholder="Length"
                                                                        wire:model="form.variations.{{ $vIdx }}.length" />
                                                                    <flux:input placeholder="Width"
                                                                        wire:model="form.variations.{{ $vIdx }}.width" />
                                                                    <flux:input placeholder="Height"
                                                                        wire:model="form.variations.{{ $vIdx }}.height" />
                                                                </flux:input.group>
                                                            </flux:field>
                                                        </div>

                                                        {{-- Description --}}
                                                        <flux:textarea
                                                            wire:model="form.variations.{{ $vIdx }}.description"
                                                            label="Description" rows="2" />
                                                    </div>
                                                </div>
                                            </flux:card>
                                        @endforeach
                                    </div>
                                @else
                                    {{-- Empty state --}}
                                    <div class="text-center py-10 text-zinc-500">
                                        <flux:icon.cube class="size-12 mx-auto mb-3 opacity-40" />
                                        <p class="font-medium">No variations yet</p>
                                        <p class="text-sm mt-1">Select attributes marked as "Used for variations"
                                            then click Generate, or add manually.</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- ── Advanced ── --}}
                        <div x-show="tab === 'advanced'" x-cloak class="space-y-5">

                            <flux:field>
                                <div class="flex items-center gap-1.5 mb-1">
                                    <flux:label>Purchase Note</flux:label>
                                    <flux:tooltip
                                        content="Sent to customers in their order confirmation email after purchasing this product.">
                                        <flux:icon.information-circle variant="outline"
                                            class="size-3.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 cursor-help" />
                                    </flux:tooltip>
                                </div>
                                <flux:textarea wire:model="form.purchase_note" rows="3"
                                    placeholder="e.g. Thank you for your purchase! Assembly instructions are included in the box." />
                            </flux:field>

                            <flux:field>
                                <div class="flex items-center gap-1.5 mb-1">
                                    <flux:label>Sort Order</flux:label>
                                    <flux:tooltip content="Lower numbers appear first in product listings.">
                                        <flux:icon.information-circle variant="outline"
                                            class="size-3.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 cursor-help" />
                                    </flux:tooltip>
                                </div>
                                <flux:input wire:model="form.sort_order" type="number" min="0"
                                    placeholder="0" class="w-32" />
                            </flux:field>

                            <div class="space-y-3">
                                <flux:field>
                                    <flux:checkbox wire:model="form.requires_quotation"
                                        label="Requires quotation — hide price and show 'Request Quote' button" />
                                </flux:field>

                                <flux:field>
                                    <flux:checkbox wire:model="form.reviews_enabled"
                                        label="Enable customer reviews for this product" />
                                </flux:field>
                            </div>

                        </div>

                    </div>
                </div>
            </div>

        </flux:card>

        {{--  Description  --}}
        <flux:card class="p-0" x-data="{ open: true }">
            <div class="flex items-center justify-between px-3 py-2 dark:border-zinc-600"
                :class="{ 'border-b ': open }">
                <flux:heading>Description</flux:heading>

                <flux:button icon="chevron-down" size="xs" variant="ghost"
                    class="cursor-pointer transition-transform duration-300" @click="open = !open">
                    <x-slot name="icon">
                        <flux:icon.chevron-down variant="outline" class="size-4 text-zinc-400"
                            x-bind:class="{ 'rotate-180': open }" />
                    </x-slot>
                </flux:button>
            </div>
            <div x-show="open" x-collapse class="p-5">
                <x-rich-editor model="form.description" placeholder="Write a full product description..."
                    :value="$form->description" />
                <flux:error name="form.description" />
            </div>
        </flux:card>


        {{-- Specifications  --}}
        <flux:card class="p-0" x-data="{ open: true }">
            <div class="flex items-center justify-between px-3 py-2 dark:border-zinc-600"
                :class="{ 'border-b ': open }">
                <flux:heading>Specifications</flux:heading>

                <flux:button icon="chevron-down" size="xs" variant="ghost"
                    class="cursor-pointer transition-transform duration-300" @click="open = !open">
                    <x-slot name="icon">
                        <flux:icon.chevron-down variant="outline" class="size-4 text-zinc-400"
                            x-bind:class="{ 'rotate-180': open }" />
                    </x-slot>
                </flux:button>
            </div>
            <div x-show="open" x-collapse class="p-5">
                <x-rich-editor model="form.specifications"
                    placeholder="Add technical specifications, features, and details..." :value="$form->specifications" />
                <flux:error name="form.specifications" />
            </div>
        </flux:card>

        {{-- SEO  --}}
        <flux:card class="p-0" x-data="{ open: false }">
            <div class="flex items-center justify-between px-3 py-2 dark:border-zinc-600"
                :class="{ 'border-b ': open }">
                <flux:heading>Search Engine Optimisation</flux:heading>

                <flux:button icon="chevron-down" size="xs" variant="ghost"
                    class="cursor-pointer transition-transform duration-300" @click="open = !open">
                    <x-slot name="icon">
                        <flux:icon.chevron-down variant="outline" class="size-4 text-zinc-400"
                            x-bind:class="{ 'rotate-180': open }" />
                    </x-slot>
                </flux:button>
            </div>

            <div x-show="open" x-collapse class="p-5 space-y-5">
                <flux:field>
                    <div class="flex items-center gap-1.5 mb-1">
                        <flux:label>Meta Title</flux:label>
                        <flux:tooltip
                            content="Overrides the page title in search results. Defaults to the product name if blank.">
                            <flux:icon.information-circle variant="outline"
                                class="size-3.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 cursor-help" />
                        </flux:tooltip>
                    </div>
                    <flux:input wire:model="form.meta_title" placeholder="Defaults to product name" />
                    <flux:error name="form.meta_title" />
                </flux:field>

                <flux:field>
                    <div class="flex items-center gap-1.5 mb-1">
                        <flux:label>Meta Description</flux:label>
                        <flux:tooltip
                            content="A short summary shown below the title in search results. Keep it under 160 characters.">
                            <flux:icon.information-circle variant="outline"
                                class="size-3.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 cursor-help" />
                        </flux:tooltip>
                    </div>
                    <flux:textarea wire:model="form.meta_description" rows="3"
                        placeholder="Brief description for search results (max 160 chars)." />
                    <flux:error name="form.meta_description" />
                </flux:field>

                <flux:field>
                    <div class="flex items-center gap-1.5 mb-1">
                        <flux:label>Meta Keywords</flux:label>
                        <flux:tooltip content="Comma-separated keywords for search engines.">
                            <flux:icon.information-circle variant="outline"
                                class="size-3.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 cursor-help" />
                        </flux:tooltip>
                    </div>
                    <flux:input wire:model="form.meta_keywords" placeholder="e.g. pot, steel, commercial kitchen" />
                    <flux:error name="form.meta_keywords" />
                </flux:field>
            </div>
        </flux:card>

    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- SIDEBAR                                                   --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="space-y-5">

        {{-- ── Publish ─────────────────────────────────────────── --}}
        <flux:card class="p-0" x-data="{ open: true }">
            <div class="flex items-center justify-between px-3 py-2 dark:border-zinc-600"
                :class="{ 'border-b ': open }">
                <flux:heading>Status & Visibility</flux:heading>

                <flux:button icon="chevron-down" size="xs" variant="ghost"
                    class="cursor-pointer transition-transform duration-300" @click="open = !open">
                    <x-slot name="icon">
                        <flux:icon.chevron-down variant="outline" class="size-4 text-zinc-400"
                            x-bind:class="{ 'rotate-180': open }" />
                    </x-slot>
                </flux:button>
            </div>
            <div x-show="open" x-collapse class="p-4 space-y-4">

                <flux:select wire:model="form.status" label="Status">
                    @foreach (App\Enums\ProductStatus::cases() as $s)
                        <flux:select.option value="{{ $s->value }}">{{ $s->label() }}</flux:select.option>
                    @endforeach
                </flux:select>


                <div x-data x-show="$wire.form.status === 'scheduled'" x-cloak>
                    <flux:field>
                        <div class="flex items-center gap-1.5 mb-1">
                            <flux:label>Publish Date & Time <span class="text-red-500">*</span></flux:label>
                            <flux:tooltip content="The product will go live automatically at this date and time.">
                                <flux:icon.information-circle variant="outline"
                                    class="size-3.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 cursor-help" />
                            </flux:tooltip>
                        </div>
                        <flux:input type="datetime-local" wire:model="form.published_at"
                            :min="now()->format('Y-m-d\TH:i')" />
                        <flux:error name="form.published_at" />
                    </flux:field>
                </div>

                <flux:select wire:model="form.visibility" label="Visibility">
                    @foreach (App\Enums\ProductVisibility::cases() as $v)
                        <flux:select.option value="{{ $v->value }}">{{ $v->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </flux:card>

        {{--  Product Image  --}}
        <flux:card class="p-0" x-data="{ open: true }">
            <div class="flex items-center justify-between px-3 py-2 dark:border-zinc-600"
                :class="{ 'border-b ': open }">
                <flux:heading>Product Image</flux:heading>

                <flux:button icon="chevron-down" size="xs" variant="ghost"
                    class="cursor-pointer transition-transform duration-300" @click="open = !open">
                    <x-slot name="icon">
                        <flux:icon.chevron-down variant="outline" class="size-4 text-zinc-400"
                            x-bind:class="{ 'rotate-180': open }" />
                    </x-slot>
                </flux:button>
            </div>
            <div x-show="open" x-collapse class="p-4 space-y-3">

                <input type="file" id="product-image-input" class="hidden" wire:model="form.image"
                    accept="image/*" />

                <div wire:loading wire:target="form.image" class="flex items-center gap-2 text-sm text-zinc-500">
                    <flux:icon.loading class="size-4" /> Uploading...
                </div>

                <div wire:loading.remove wire:target="form.image">
                    @if ($form->image)
                        <div class="max-w-xs mx-auto">
                            <div class="relative group rounded-md overflow-hidden aspect-square border dark:border-zinc-700 cursor-pointer"
                                @click="document.getElementById('product-image-input').click()">
                                <img src="{{ $form->image->temporaryUrl() }}" class="w-full h-full object-cover"
                                    alt="Preview" />
                                <div
                                    class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <flux:text class="text-white text-sm font-medium">Change image</flux:text>
                                </div>
                                <flux:badge color="green" size="sm" class="absolute top-1.5 left-1.5">New
                                </flux:badge>
                            </div>
                        </div>
                    @elseif ($form->existing_image)
                        <div class="max-w-xs mx-auto">
                            <div class="relative group rounded-md overflow-hidden aspect-square border dark:border-zinc-700 cursor-pointer"
                                @click="document.getElementById('product-image-input').click()">
                                <img src="{{ \Storage::url($form->existing_image) }}"
                                    class="w-full h-full object-cover" alt="Product image" />
                                <div
                                    class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <flux:text class="text-white text-sm font-medium">Change image</flux:text>
                                </div>
                            </div>
                        </div>
                    @else
                        <button type="button" @click="document.getElementById('product-image-input').click()"
                            class="flex flex-col items-center justify-center w-full h-48 rounded-md border-2 border-dashed border-zinc-300 dark:border-zinc-600 hover:border-zinc-400 dark:hover:border-zinc-500 transition-colors cursor-pointer gap-2 text-zinc-400">
                            <flux:icon.photo class="size-8" variant="outline" />
                            <span class="text-sm">Upload image</span>
                        </button>
                    @endif
                </div>

                @if ($form->image || $form->existing_image)
                    <flux:button type="button" variant="ghost" size="xs" icon="trash"
                        class="text-red-500 cursor-pointer"
                        wire:click="$set('form.image', null); $set('form.existing_image', null)">
                        Remove image
                    </flux:button>
                @endif

                <flux:error name="form.image" />
            </div>
        </flux:card>

        {{--  Gallery  --}}
        <flux:card class="p-0" x-data="{ open: true }">
            <div class="flex items-center justify-between px-3 py-2 dark:border-zinc-600"
                :class="{ 'border-b ': open }">
                <flux:heading>Gallery</flux:heading>

                <flux:button icon="chevron-down" size="xs" variant="ghost"
                    class="cursor-pointer transition-transform duration-300" @click="open = !open">
                    <x-slot name="icon">
                        <flux:icon.chevron-down variant="outline" class="size-4 text-zinc-400"
                            x-bind:class="{ 'rotate-180': open }" />
                    </x-slot>
                </flux:button>
            </div>
            <div x-show="open" x-collapse class="p-4 space-y-3">

                <input type="file" id="gallery-input" class="hidden" wire:model="form.new_images"
                    accept="image/*" multiple />

                <div>
                    <div class="grid grid-cols-3 gap-2 mb-3">
                        @if (!empty($form->existing_images) || !empty($form->new_images))

                            @foreach ($form->existing_images as $img)
                                <div class="relative group aspect-square rounded-md overflow-hidden border dark:border-zinc-700"
                                    wire:key="existing-img-{{ $img['id'] }}">
                                    <img src="{{ $img['url'] }}" class="w-full h-full object-cover"
                                        alt="{{ $img['alt_text'] ?? '' }}" />
                                    <button type="button" wire:click="removeExistingImage({{ $img['id'] }})"
                                        class="absolute top-1 right-1 bg-red-500 hover:bg-red-600 text-white rounded-full p-0.5 opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
                                        <flux:icon.x-mark variant="micro" class="size-3" />
                                    </button>
                                </div>
                            @endforeach

                            @foreach ($form->new_images as $index => $img)
                                <div class="relative group aspect-square rounded-md overflow-hidden border dark:border-zinc-700"
                                    wire:key="new-img-{{ $index }}">
                                    <img src="{{ $img->temporaryUrl() }}" class="w-full h-full object-cover"
                                        alt="New image" />
                                    <flux:badge color="green" size="sm" class="absolute top-1 left-1 text-xs!">
                                        New</flux:badge>
                                    <button type="button" wire:click="removeNewImage({{ $index }})"
                                        class="absolute top-1 right-1 bg-red-500 hover:bg-red-600 text-white rounded-full p-0.5 opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
                                        <flux:icon.x-mark variant="micro" class="size-3" />
                                    </button>
                                </div>
                            @endforeach
                        @endif

                        <flux:button type="button" icon="photo" variant="ghost"
                            class="aspect-square! rounded-md w-full! h-full! cursor-pointer border border-dashed border-zinc-300 dark:border-zinc-600"
                            @click="document.getElementById('gallery-input').click()">
                            <x-slot name="icon">
                                <flux:icon.photo wire:loading.remove wire:target="form.new_images" variant="outline"
                                    class="size-5 text-zinc-400" />
                                <flux:icon.loading wire:loading wire:target="form.new_images" class="size-4" />
                            </x-slot>
                            <span wire:loading.remove wire:target="form.new_images">Add Images</span>
                            <span wire:loading wire:target="form.new_images">Processing...</span>
                        </flux:button>
                    </div>
                </div>

                <flux:error name="form.new_images" />
            </div>
        </flux:card>

        {{--  Brand  --}}
        <flux:card class="p-0" x-data="{ open: true }">
            <div class="flex items-center justify-between px-3 py-2 dark:border-zinc-600"
                :class="{ 'border-b ': open }">
                <flux:heading>Brand</flux:heading>

                <flux:button icon="chevron-down" size="xs" variant="ghost"
                    class="cursor-pointer transition-transform duration-300" @click="open = !open">
                    <x-slot name="icon">
                        <flux:icon.chevron-down variant="outline" class="size-4 text-zinc-400"
                            x-bind:class="{ 'rotate-180': open }" />
                    </x-slot>
                </flux:button>
            </div>

            <div x-show="open" x-collapse class="p-4">
                <flux:select wire:model="form.brand_id">
                    <flux:select.option value="">— No brand —</flux:select.option>
                    @foreach ($this->brands as $brand)
                        <flux:select.option value="{{ $brand->id }}">{{ $brand->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </flux:card>

        {{--  Categories  --}}
        <flux:card class="p-0" x-data="{ open: true, search: '' }">
            <div class="flex items-center justify-between px-3 py-2 dark:border-zinc-600"
                :class="{ 'border-b ': open }">
                <flux:heading>Categories</flux:heading>

                <flux:button icon="chevron-down" size="xs" variant="ghost"
                    class="cursor-pointer transition-transform duration-300" @click="open = !open">
                    <x-slot name="icon">
                        <flux:icon.chevron-down variant="outline" class="size-4 text-zinc-400"
                            x-bind:class="{ 'rotate-180': open }" />
                    </x-slot>
                </flux:button>
            </div>

            <div x-show="open" x-collapse class="p-4 space-y-3">
                <flux:input x-model="search" placeholder="Search categories..." icon="magnifying-glass"
                    size="sm" clearable />

                <div class="max-h-52 overflow-y-auto space-y-0.5 rounded-md border dark:border-zinc-700">
                    @forelse ($this->categories as $category)
                        <label
                            class="flex items-center gap-2.5 px-3 py-2 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors cursor-pointer"
                            wire:key="cat-{{ $category->id }}"
                            x-show="search === '' || '{{ strtolower($category->name) }}'.includes(search.toLowerCase())">
                            <flux:checkbox wire:model="form.category_ids" :value="$category->id" />
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $category->name }}</span>
                        </label>
                    @empty
                        <div class="px-3 py-4 text-sm text-zinc-400 text-center">No categories found.</div>
                    @endforelse
                </div>

                @if (!empty($form->category_ids))
                    <flux:text class="text-xs text-zinc-500">
                        {{ count($form->category_ids) }} {{ Str::plural('category', count($form->category_ids)) }}
                        selected
                    </flux:text>
                @endif

                <flux:error name="form.category_ids" />
            </div>
        </flux:card>


        {{-- Tags --}}
        <flux:card class="p-0" x-data="{ expanded: true, panelOpen: false }">
            <div class="flex items-center justify-between px-3 py-2 dark:border-zinc-600"
                :class="{ 'border-b': expanded }">
                <flux:heading>Tags</flux:heading>
                <flux:button size="xs" variant="ghost" class="cursor-pointer" @click="expanded = !expanded">
                    <x-slot name="icon">
                        <flux:icon.chevron-down variant="outline"
                            class="size-4 text-zinc-400 transition-transform duration-200"
                            x-bind:class="{ 'rotate-180': expanded }" />
                    </x-slot>
                </flux:button>
            </div>

            <div x-show="expanded" x-collapse class="p-4 space-y-3">

                {{-- Input + Add --}}
                <flux:input.group>
                    <flux:input wire:model="newTagInput" placeholder="Add a tag…"
                        x-on:keydown.enter.prevent="$wire.addTags()" />
                    <flux:button type="button" wire:click="addTags" class="cursor-pointer">
                        Add
                    </flux:button>
                </flux:input.group>

                <flux:text class="text-xs text-zinc-400">Separate multiple tags with commas</flux:text>

                {{-- Selected tag badges --}}
                @if ($this->selectedTags->isNotEmpty())
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($this->selectedTags as $tag)
                            <flux:badge color="zinc" size="sm" class="flex items-center gap-1.5"
                                wire:key="tag-{{ $tag->id }}">
                                <span>{{ $tag->name }}</span>
                                <button type="button" wire:click="removeTag({{ $tag->id }})"
                                    class="hover:text-red-500 transition-colors cursor-pointer leading-none">
                                    <flux:icon.x-mark variant="micro" class="size-3" />
                                </button>
                            </flux:badge>
                        @endforeach
                    </div>
                @endif

                {{-- Toggle panel --}}
                <button type="button"
                    class="text-xs text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 underline underline-offset-2 cursor-pointer transition-colors"
                    @click="panelOpen = !panelOpen">
                    <span x-text="panelOpen ? 'Hide tag suggestions' : 'Choose from the most used tags'"></span>
                </button>

                {{-- Suggested tags panel --}}
                <div x-show="panelOpen" x-collapse class="space-y-2 border-t pt-3 dark:border-zinc-700">
                    <flux:input wire:model.live.debounce.200ms="tagQuery" placeholder="Search tags…"
                        icon="magnifying-glass" size="sm" />

                    <div class="flex flex-wrap gap-1.5 max-h-36 overflow-y-auto">
                        @forelse ($this->availableTags as $tag)
                            <button type="button" wire:click="addTag({{ $tag->id }})"
                                wire:key="avail-{{ $tag->id }}"
                                class="px-2 py-0.5 text-xs rounded-full border border-zinc-200 dark:border-zinc-700
                               text-zinc-500 dark:text-zinc-400 hover:border-zinc-400 hover:text-zinc-700
                               dark:hover:text-zinc-200 transition-colors cursor-pointer">
                                {{ $tag->name }}
                            </button>
                        @empty
                            <p class="text-xs text-zinc-400">No tags found.</p>
                        @endforelse
                    </div>
                </div>

            </div>
        </flux:card>
    </div>
</div>
