@php
    use App\Enums\ProductLinkType;
    use App\Enums\ProductStatus;
    use App\Enums\ProductType;
    use App\Enums\ProductVisibility;
    use App\Enums\StockStatus;

    $tabBtn = 'flex w-full items-center gap-2 border-l-2 px-4 py-2.5 text-sm font-medium text-left transition-colors';
    $tabActive = 'border-brand-500 bg-brand-50 text-brand-600 dark:bg-brand-950/40 dark:text-brand-400';
    $tabInactive = 'border-transparent text-zinc-500 hover:bg-zinc-50 hover:text-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-200';
@endphp

<div>
    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('admin.products.index')" wire:navigate>Products</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $productId ? $name : 'New product' }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <form wire:submit="save">

        {{-- Header --}}
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ $productId ? 'Edit product' : 'New product' }}</flux:heading>
                <flux:subheading>{{ $productId ? 'Update this product\'s details.' : 'Add a new product to your catalog.' }}</flux:subheading>
            </div>
            <div class="flex items-center gap-3">
                <flux:button variant="ghost" :href="route('admin.products.index')" wire:navigate>Cancel</flux:button>
                <flux:button type="submit" variant="primary" icon="check">{{ $productId ? 'Save changes' : 'Add product' }}</flux:button>
            </div>
        </div>

        {{-- Two-column layout --}}
        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- Main column (2 cols) --}}
            <div class="space-y-6 lg:col-span-2">

                {{-- Basic Information --}}
                <div x-data="{ open: true }" class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                    <button type="button" x-on:click="open = !open"
                            class="flex w-full items-center justify-between px-6 py-4"
                            :class="open ? 'border-b border-zinc-200 dark:border-zinc-700' : ''">
                        <flux:heading size="base">Basic information</flux:heading>
                        <span class="inline-flex transition-transform duration-200" :class="open ? 'rotate-180' : ''">
                        <flux:icon.chevron-down variant="micro" class="size-4 text-zinc-400" />
                    </span>
                    </button>
                    <div x-show="open" x-collapse x-cloak>
                        <div class="space-y-4 p-6">
                            <flux:input wire:model.live.debounce.400ms="name" label="Product name" placeholder="e.g. Commercial Wok Range 4-Burner" required />
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <flux:input wire:model.blur="slug" label="Slug" placeholder="auto-generated-from-name" />
                                <flux:input wire:model="model_number" label="Model number" placeholder="e.g. WR-4B-900" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Product Data --}}
                <div
                    x-data="{
                        open: true,
                        tab: 'general',
                        get availableTabs() {
                            const t = $wire.type;
                            const tabs = ['general'];
                            if (['simple','variable','bundled'].includes(t)) tabs.push('inventory');
                            if (['simple','variable','bundled'].includes(t) && !$wire.is_virtual) tabs.push('shipping');
                            if (t === 'variable') { tabs.push('attributes'); tabs.push('variations'); }
                            if ($wire.is_downloadable) tabs.push('files');
                            tabs.push('linked');
                            tabs.push('advanced');
                            return tabs;
                        },
                        ensureValidTab() {
                            if (!this.availableTabs.includes(this.tab)) this.tab = 'general';
                        }
                    }"
                    x-effect="ensureValidTab()"
                    class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">

                    {{-- Header with type selector --}}
                    <div class="flex items-center justify-between gap-4 px-6 py-4"
                         :class="open ? 'border-b border-zinc-200 dark:border-zinc-700' : ''">
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                            <button type="button" x-on:click="open = !open">
                                <flux:heading size="base">Product data</flux:heading>
                            </button>
                            <flux:select wire:model.live="type" size="sm" class="w-auto">
                                @foreach (ProductType::cases() as $t)
                                    <flux:select.option :value="$t->value">{{ ucfirst($t->value) }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            {{-- Fulfilment flags — only meaningful for simple & variable products --}}
                            <div class="flex items-center gap-3" x-show="['simple','variable'].includes($wire.type)" x-cloak>
                                <label class="flex cursor-pointer items-center gap-1.5 text-sm text-zinc-600 dark:text-zinc-300">
                                    <flux:checkbox wire:model.live="is_virtual" />
                                    Virtual
                                </label>
                                <label class="flex cursor-pointer items-center gap-1.5 text-sm text-zinc-600 dark:text-zinc-300">
                                    <flux:checkbox wire:model.live="is_downloadable" />
                                    Downloadable
                                </label>
                            </div>
                        </div>
                        <button type="button" x-on:click="open = !open"
                                class="inline-flex transition-transform duration-200" :class="open ? 'rotate-180' : ''">
                            <flux:icon.chevron-down variant="micro" class="size-4 text-zinc-400" />
                        </button>
                    </div>

                    <div x-show="open" x-collapse x-cloak>
                    <div class="flex min-h-72 flex-col md:flex-row">

                        {{-- Subnav: full-width stacked above content on mobile, vertical rail on md+ --}}
                        <nav class="w-full shrink-0 border-b border-zinc-200 py-2 md:w-44 md:border-b-0 md:border-r dark:border-zinc-700">
                            <button type="button" :class="tab === 'general' ? '{{ $tabActive }}' : '{{ $tabInactive }}'" class="{{ $tabBtn }}" x-on:click="tab = 'general'">
                                <flux:icon.adjustments-horizontal variant="micro" class="size-4 shrink-0" />General
                            </button>
                            <button type="button" :class="tab === 'inventory' ? '{{ $tabActive }}' : '{{ $tabInactive }}'" class="{{ $tabBtn }}" x-show="['simple','variable','bundled'].includes($wire.type)" x-on:click="tab = 'inventory'">
                                <flux:icon.archive-box variant="micro" class="size-4 shrink-0" />Inventory
                            </button>
                            <button type="button" :class="tab === 'shipping' ? '{{ $tabActive }}' : '{{ $tabInactive }}'" class="{{ $tabBtn }}" x-show="['simple','variable','bundled'].includes($wire.type) && !$wire.is_virtual" x-on:click="tab = 'shipping'">
                                <flux:icon.truck variant="micro" class="size-4 shrink-0" />Shipping
                            </button>
                            <button type="button" :class="tab === 'attributes' ? '{{ $tabActive }}' : '{{ $tabInactive }}'" class="{{ $tabBtn }}" x-show="$wire.type === 'variable'" x-on:click="tab = 'attributes'">
                                <flux:icon.tag variant="micro" class="size-4 shrink-0" />Attributes
                            </button>
                            <button type="button" :class="tab === 'variations' ? '{{ $tabActive }}' : '{{ $tabInactive }}'" class="{{ $tabBtn }}" x-show="$wire.type === 'variable'" x-on:click="tab = 'variations'">
                                <flux:icon.squares-2x2 variant="micro" class="size-4 shrink-0" />Variations
                            </button>
                            <button type="button" :class="tab === 'files' ? '{{ $tabActive }}' : '{{ $tabInactive }}'" class="{{ $tabBtn }}" x-show="$wire.is_downloadable" x-on:click="tab = 'files'">
                                <flux:icon.arrow-down-tray variant="micro" class="size-4 shrink-0" />Files
                            </button>
                            <button type="button" :class="tab === 'linked' ? '{{ $tabActive }}' : '{{ $tabInactive }}'" class="{{ $tabBtn }}" x-on:click="tab = 'linked'">
                                <flux:icon.link variant="micro" class="size-4 shrink-0" />Linked products
                            </button>
                            <button type="button" :class="tab === 'advanced' ? '{{ $tabActive }}' : '{{ $tabInactive }}'" class="{{ $tabBtn }}" x-on:click="tab = 'advanced'">
                                <flux:icon.cog-6-tooth variant="micro" class="size-4 shrink-0" />Advanced
                            </button>
                        </nav>

                        {{-- Tab content --}}
                        <div class="min-w-0 flex-1">

                            {{-- General --}}
                            <div x-show="tab === 'general'" class="space-y-4 p-6">
                                <div x-show="$wire.type !== 'grouped'" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <flux:input wire:model="price" label="Regular price (KES)" type="number" min="0" step="0.01" placeholder="0.00" />
                                    <flux:input wire:model="sale_price" label="Sale price (KES)" type="number" min="0" step="0.01" placeholder="0.00" />
                                </div>
                                <div x-show="$wire.type !== 'grouped'">
                                    <flux:input wire:model="cost_price" label="Cost price (KES)" type="number" min="0" step="0.01" placeholder="0.00" />
                                </div>
                                <div x-show="$wire.type !== 'grouped'">
                                    <flux:select wire:model="tax_class_id" label="Tax class">
                                        <flux:select.option value="">Store default</flux:select.option>
                                        @foreach ($this->taxClasses as $taxClass)
                                            <flux:select.option :value="$taxClass->id">{{ $taxClass->name }} ({{ rtrim(rtrim(number_format((float) $taxClass->rate, 2), '0'), '.') }}%)</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </div>
                                <div x-show="$wire.type === 'grouped'" class="py-4 text-center text-sm text-zinc-400">
                                    Grouped products have no direct price — customers purchase each item individually.
                                </div>
                            </div>

                            {{-- Inventory --}}
                            <div x-show="tab === 'inventory'" class="space-y-4 p-6">
                                <flux:input wire:model="sku" label="SKU" placeholder="e.g. WR-4B" />
                                <flux:select wire:model="stock_status" label="Stock status">
                                    @foreach (StockStatus::cases() as $s)
                                        <flux:select.option :value="$s->value">{{ $s->label() }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <flux:input wire:model="stock_quantity" label="Stock quantity" type="number" min="0" placeholder="Leave blank if untracked" />
                                    <flux:input wire:model="low_stock_threshold" label="Low stock alert" type="number" min="0" placeholder="e.g. 5" />
                                </div>
                                <flux:input wire:model="min_order_quantity" label="Minimum order quantity" type="number" min="1" placeholder="1" />
                                <div class="flex items-center justify-between rounded-md bg-zinc-50 px-3 py-2.5 dark:bg-zinc-800">
                                    <flux:label>Allow backorder</flux:label>
                                    <flux:switch wire:model="allow_backorder" />
                                </div>
                            </div>

                            {{-- Shipping --}}
                            <div x-show="tab === 'shipping'" class="space-y-4 p-6">
                                <flux:input wire:model="weight" :label="'Weight (' . $weight_unit . ')'" type="number" min="0" step="0.001" placeholder="0.000" />
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <flux:input wire:model="length" :label="'Length (' . $dimension_unit . ')'" type="number" min="0" step="0.01" placeholder="0.00" />
                                    <flux:input wire:model="width" :label="'Width (' . $dimension_unit . ')'" type="number" min="0" step="0.01" placeholder="0.00" />
                                    <flux:input wire:model="height" :label="'Height (' . $dimension_unit . ')'" type="number" min="0" step="0.01" placeholder="0.00" />
                                </div>
                            </div>

                            {{-- Attributes --}}
                            <div x-show="tab === 'attributes'">
                                @if (! $productId)
                                    <div class="p-6 text-center text-sm text-zinc-400">Save the product first to manage attributes.</div>
                                @else
                                    <div class="space-y-4 p-6">
                                        <flux:text size="sm" class="text-zinc-500">
                                            Add descriptive information customers can use to search for this product, such as "Material" or "Color".
                                        </flux:text>

                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <flux:button size="sm" icon="plus" type="button" wire:click="addNewAttribute">
                                                    Add new
                                                </flux:button>
                                                @if ($this->allAttributes->isNotEmpty())
                                                    <flux:select wire:change="addExistingAttribute($event.target.value)" size="sm" class="w-auto">
                                                        <flux:select.option value="">Add existing…</flux:select.option>
                                                        @foreach ($this->allAttributes as $attr)
                                                            <flux:select.option :value="$attr->id">{{ $attr->name }}</flux:select.option>
                                                        @endforeach
                                                    </flux:select>
                                                @endif
                                            </div>
                                            @if (!empty($selectedAttributes))
                                                <flux:text size="sm" class="text-zinc-400">{{ count($selectedAttributes) }} attribute(s)</flux:text>
                                            @endif
                                        </div>

                                        @foreach ($selectedAttributes as $index => $attr)
                                            <div class="overflow-hidden rounded-md border border-zinc-200 dark:border-zinc-700">
                                                <div @class(['flex items-center justify-between bg-zinc-50 px-4 py-3 dark:bg-zinc-800/60', 'border-b border-zinc-200 dark:border-zinc-700' => ! $attr['collapsed']])>
                                                    <span class="text-sm font-medium dark:text-white">
                                                        {{ $attr['name'] ?: 'New Attribute' }}
                                                    </span>
                                                    <div class="flex items-center gap-1">
                                                        <flux:button size="xs" variant="ghost" icon="trash-2" type="button"
                                                            wire:click="removeAttribute({{ $index }})"
                                                            class="text-red-500! hover:text-red-600!" />
                                                        <button type="button" wire:click="toggleAttributeCollapsed({{ $index }})"
                                                            class="rounded p-1 text-zinc-400 hover:bg-zinc-200 hover:text-zinc-700 dark:hover:bg-zinc-700">
                                                            @if ($attr['collapsed'])
                                                                <flux:icon.chevron-down variant="micro" class="size-4" />
                                                            @else
                                                                <flux:icon.chevron-up variant="micro" class="size-4" />
                                                            @endif
                                                        </button>
                                                    </div>
                                                </div>

                                                @if (! $attr['collapsed'])
                                                    <div class="space-y-4 p-4">
                                                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                                            <flux:input
                                                                wire:model="selectedAttributes.{{ $index }}.name"
                                                                label="Name"
                                                                placeholder="e.g. Size, Material" />
                                                            <flux:textarea
                                                                wire:model="selectedAttributes.{{ $index }}.values_string"
                                                                label="Value(s)"
                                                                rows="3"
                                                                placeholder="Enter values separated by '|'  e.g. Blue | Large | Medium" />
                                                        </div>
                                                        <div class="space-y-2">
                                                            <label class="flex cursor-pointer items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                                                                <flux:checkbox wire:model="selectedAttributes.{{ $index }}.is_visible" />
                                                                Visible on the product page
                                                            </label>
                                                            <label class="flex cursor-pointer items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                                                                <flux:checkbox wire:model="selectedAttributes.{{ $index }}.is_variation_attribute" />
                                                                Used for variations
                                                            </label>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            {{-- Variations --}}
                            <div x-show="tab === 'variations'">
                                @if (! $productId)
                                    <div class="p-6 text-center text-sm text-zinc-400">Save the product first to manage variations.</div>
                                @else
                                    <div class="space-y-4 p-6">

                                        {{-- Default Form Values row --}}
                                        @php
                                            $variationAttrs = collect($selectedAttributes)
                                                ->filter(fn ($a) => $a['is_variation_attribute'] && trim($a['values_string']) !== '');
                                        @endphp
                                        @if ($variationAttrs->isNotEmpty())
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Default Form Values:</span>
                                                @foreach ($variationAttrs as $attr)
                                                    @php
                                                        $attrValues = array_values(array_filter(array_map('trim', explode('|', $attr['values_string']))));
                                                    @endphp
                                                    <flux:select
                                                        wire:model="defaultVariantFormValues.{{ $attr['name'] }}"
                                                        size="sm"
                                                        class="w-auto">
                                                        <flux:select.option value="">— {{ $attr['name'] }} —</flux:select.option>
                                                        @foreach ($attrValues as $val)
                                                            <flux:select.option :value="$val">{{ $val }}</flux:select.option>
                                                        @endforeach
                                                    </flux:select>
                                                @endforeach
                                            </div>
                                        @endif

                                        {{-- Toolbar --}}
                                        <div class="flex flex-wrap items-center justify-between gap-3">
                                            <div class="flex items-center gap-2">
                                                @if (!empty($variants))
                                                    <flux:checkbox
                                                        wire:click="toggleSelectAllVariants"
                                                        :checked="count($selectedVariantIndexes) > 0 && count($selectedVariantIndexes) === count($variants)" />
                                                @endif
                                                <flux:button size="sm" icon="arrow-path" type="button" wire:click="generateVariants">
                                                    Regenerate
                                                </flux:button>
                                                <flux:button size="sm" icon="plus" type="button" wire:click="addManualVariant">
                                                    Add Manual
                                                </flux:button>
                                                @if (!empty($variants))
                                                    <flux:dropdown>
                                                        <flux:button size="sm" icon:trailing="chevron-down" type="button">
                                                            Bulk Actions
                                                        </flux:button>
                                                        <flux:menu>
                                                            <flux:menu.item icon="check-circle" wire:click="bulkActivateVariants">Activate selected</flux:menu.item>
                                                            <flux:menu.item icon="x-circle" wire:click="bulkDeactivateVariants">Deactivate selected</flux:menu.item>
                                                            <flux:menu.separator />
                                                            <flux:menu.item icon="currency-dollar" wire:click="openBulkEdit('price')">Set price</flux:menu.item>
                                                            <flux:menu.item icon="tag" wire:click="openBulkEdit('compare_at_price')">Set sale price</flux:menu.item>
                                                            <flux:menu.item icon="calculator" wire:click="openBulkEdit('cost_price')">Set cost price</flux:menu.item>
                                                            <flux:menu.separator />
                                                            <flux:menu.item icon="archive-box" wire:click="openBulkEdit('stock_status')">Set stock status</flux:menu.item>
                                                            <flux:menu.item icon="hashtag" wire:click="openBulkEdit('stock_quantity')">Set stock quantity</flux:menu.item>
                                                            <flux:menu.separator />
                                                            <flux:menu.item icon="trash-2" variant="danger" wire:click="bulkDeleteVariants"
                                                                wire:confirm="Delete {{ count($selectedVariantIndexes) }} selected variation(s)?">
                                                                Delete selected
                                                            </flux:menu.item>
                                                        </flux:menu>
                                                    </flux:dropdown>
                                                    @if (!empty($selectedVariantIndexes))
                                                        <span class="text-sm text-zinc-500">{{ count($selectedVariantIndexes) }} selected</span>
                                                    @endif
                                                @endif
                                            </div>
                                            @if (!empty($variants))
                                                <div class="flex items-center gap-1 text-sm text-zinc-400">
                                                    <span>{{ count($variants) }} variation(s)</span>
                                                    <span class="px-1">·</span>
                                                    <button type="button" wire:click="expandAllVariants" class="text-brand-500 hover:underline">Expand all</button>
                                                    <span>/</span>
                                                    <button type="button" wire:click="collapseAllVariants" class="text-brand-500 hover:underline">Collapse all</button>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Warning: variants without price --}}
                                        @php $unpricedCount = collect($variants)->filter(fn ($v) => $v['price'] === null)->count(); @endphp
                                        @if ($unpricedCount > 0)
                                            <div class="flex items-center gap-2 rounded-md border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800 dark:border-yellow-700/40 dark:bg-yellow-900/20 dark:text-yellow-300">
                                                <flux:icon.exclamation-circle variant="micro" class="size-4 shrink-0" />
                                                {{ $unpricedCount }} variation(s) do not have a price set.
                                            </div>
                                        @endif

                                        {{-- Empty state --}}
                                        @if (empty($variants))
                                            <div class="flex flex-col items-center justify-center py-10 text-center">
                                                <flux:icon.cube class="size-10 text-zinc-300 dark:text-zinc-600" />
                                                <flux:text class="mt-3 font-medium">No variations yet</flux:text>
                                                <flux:text size="sm" class="mt-1 max-w-xs text-zinc-400">
                                                    Select attributes marked as "Used for variations" then click Regenerate, or add manually.
                                                </flux:text>
                                            </div>
                                        @else
                                            {{-- Accordion cards --}}
                                            <div class="space-y-3">
                                                @foreach ($variants as $i => $variant)
                                                    <div class="overflow-hidden rounded-md border border-zinc-200 dark:border-zinc-700">

                                                        {{-- Card header --}}
                                                        <div @class(['flex items-center justify-between bg-zinc-50 px-4 py-3 dark:bg-zinc-800/60', 'border-b border-zinc-200 dark:border-zinc-700' => ! $variant['collapsed']])>
                                                            <div class="flex items-center gap-3">
                                                                <flux:checkbox
                                                                    wire:model="selectedVariantIndexes"
                                                                    value="{{ $i }}" />
                                                                <span class="text-sm font-medium dark:text-white">
                                                                    {{ $variant['label'] ?: 'Variant '.($i + 1) }}
                                                                </span>
                                                            </div>
                                                            <div class="flex items-center gap-2">
                                                                @if ($variant['is_active'])
                                                                    <flux:badge size="sm" color="green">Active</flux:badge>
                                                                @else
                                                                    <flux:badge size="sm" color="zinc">Inactive</flux:badge>
                                                                @endif
                                                                <flux:button size="xs" variant="ghost" icon="trash-2" type="button"
                                                                    wire:click="removeVariant({{ $i }})"
                                                                    class="text-red-500! hover:text-red-600!" />
                                                                <button type="button" wire:click="toggleVariantCollapsed({{ $i }})"
                                                                    class="rounded p-1 text-zinc-400 hover:bg-zinc-200 hover:text-zinc-700 dark:hover:bg-zinc-700">
                                                                    @if ($variant['collapsed'])
                                                                        <flux:icon.chevron-down variant="micro" class="size-4" />
                                                                    @else
                                                                        <flux:icon.chevron-up variant="micro" class="size-4" />
                                                                    @endif
                                                                </button>
                                                            </div>
                                                        </div>

                                                        {{-- Card body --}}
                                                        @if (! $variant['collapsed'])
                                                            <div class="space-y-4 p-4">

                                                                {{-- Image + SKU --}}
                                                                <div class="grid grid-cols-[96px_1fr] items-start gap-4">
                                                                    <div>
                                                                        @if (!empty($pendingVariantImages[$i]))
                                                                            <div class="group relative h-24 w-24 overflow-hidden rounded-md border border-zinc-200 dark:border-zinc-700">
                                                                                <img src="{{ $pendingVariantImages[$i]->temporaryUrl() }}" class="h-full w-full object-cover" alt="Preview" />
                                                                                <button type="button" wire:click="removeVariantImage({{ $i }})"
                                                                                    class="absolute right-1 top-1 rounded-full bg-white/90 p-0.5 shadow hover:bg-white dark:bg-zinc-900/90">
                                                                                    <flux:icon.x-mark variant="micro" class="size-3 text-zinc-600" />
                                                                                </button>
                                                                            </div>
                                                                        @elseif ($variant['image_url'])
                                                                            <div class="group relative h-24 w-24 overflow-hidden rounded-md border border-zinc-200 dark:border-zinc-700">
                                                                                <img src="{{ $variant['image_url'] }}" class="h-full w-full object-cover" alt="" />
                                                                                <button type="button" wire:click="removeVariantImage({{ $i }})"
                                                                                    class="absolute right-1 top-1 rounded-full bg-white/90 p-0.5 shadow hover:bg-white dark:bg-zinc-900/90">
                                                                                    <flux:icon.x-mark variant="micro" class="size-3 text-zinc-600" />
                                                                                </button>
                                                                            </div>
                                                                        @else
                                                                            <label class="flex h-24 w-24 cursor-pointer flex-col items-center justify-center gap-1 rounded-md border-2 border-dashed border-zinc-300 text-zinc-400 transition hover:border-zinc-400 dark:border-zinc-600 dark:hover:border-zinc-500">
                                                                                <flux:icon.photo class="size-6" />
                                                                                <span class="text-xs">Add image</span>
                                                                                <input type="file" wire:model="pendingVariantImages.{{ $i }}" accept="image/*" class="sr-only" />
                                                                            </label>
                                                                        @endif
                                                                        <div wire:loading wire:target="pendingVariantImages.{{ $i }}" class="mt-1 text-xs text-zinc-400">
                                                                            Uploading…
                                                                        </div>
                                                                    </div>
                                                                    <flux:input wire:model="variants.{{ $i }}.sku" label="SKU" placeholder="Leave blank to auto-generate" />
                                                                </div>

                                                                {{-- Checkboxes --}}
                                                                <div class="flex flex-wrap items-center gap-5 border-t border-b border-zinc-100 py-3 dark:border-zinc-800">
                                                                    <label class="flex cursor-pointer items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                                                                        <flux:checkbox wire:model.live="variants.{{ $i }}.is_active" />
                                                                        Active
                                                                    </label>
                                                                    <label class="flex cursor-pointer items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                                                                        <flux:checkbox wire:model.live="variants.{{ $i }}.manage_stock" />
                                                                        Manage Stock
                                                                    </label>
                                                                    <label class="flex cursor-pointer items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                                                                        <flux:checkbox
                                                                            wire:click="setDefaultVariant({{ $i }})"
                                                                            :checked="$variant['is_default']" />
                                                                        Default
                                                                    </label>
                                                                </div>

                                                                {{-- Variation name --}}
                                                                <div x-data="{ editing: false }">
                                                                    <flux:label>Variation Name</flux:label>
                                                                    <div class="mt-1">
                                                                        <div x-show="!editing"
                                                                             class="flex items-center justify-between rounded-md border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                                                                            <span class="dark:text-white">{{ $variant['label'] ?: '—' }}</span>
                                                                            <button type="button"
                                                                                    x-on:click="editing = true"
                                                                                    class="ml-2 shrink-0 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                                                                                <flux:icon.pencil variant="micro" class="size-4" />
                                                                            </button>
                                                                        </div>
                                                                        <div x-show="editing" class="flex items-center gap-1.5">
                                                                            <flux:input
                                                                                wire:model="variants.{{ $i }}.label"
                                                                                placeholder="e.g. Black / Large"
                                                                                x-on:keydown.enter="editing = false"
                                                                                x-on:keydown.escape="editing = false" />
                                                                            <button type="button"
                                                                                    x-on:click="editing = false"
                                                                                    class="shrink-0 rounded p-1.5 text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20">
                                                                                <flux:icon.check variant="micro" class="size-4" />
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                {{-- Prices --}}
                                                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                                                    <flux:input wire:model="variants.{{ $i }}.price" label="Price (Ksh)" type="number" min="0" step="0.01" placeholder="0.00" />
                                                                    <flux:input wire:model="variants.{{ $i }}.compare_at_price" label="Sale Price (Ksh)" type="number" min="0" step="0.01" placeholder="0.00" />
                                                                    <flux:input wire:model="variants.{{ $i }}.cost_price" label="Cost Price (Ksh)" type="number" min="0" step="0.01" placeholder="0.00" />
                                                                </div>

                                                                {{-- Stock status + quantity --}}
                                                                <flux:select wire:model="variants.{{ $i }}.stock_status" label="Stock Status">
                                                                    @foreach (StockStatus::cases() as $s)
                                                                        <flux:select.option :value="$s->value">{{ $s->label() }}</flux:select.option>
                                                                    @endforeach
                                                                </flux:select>

                                                                @if ($variant['manage_stock'])
                                                                    <flux:input wire:model="variants.{{ $i }}.stock_quantity" label="Stock Quantity" type="number" min="0" placeholder="0" />
                                                                @endif

                                                                {{-- Weight + Dimensions --}}
                                                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                                                    <flux:input wire:model="variants.{{ $i }}.weight" :label="'Weight (' . $weight_unit . ')'" type="number" min="0" step="0.001" placeholder="0.000" />
                                                                    <div>
                                                                        <flux:label>Dimensions — L × W × H ({{ $dimension_unit }})</flux:label>
                                                                        <div class="mt-1 grid grid-cols-3 gap-2">
                                                                            <flux:input wire:model="variants.{{ $i }}.length" type="number" min="0" step="0.01" placeholder="Length" />
                                                                            <flux:input wire:model="variants.{{ $i }}.width" type="number" min="0" step="0.01" placeholder="Width" />
                                                                            <flux:input wire:model="variants.{{ $i }}.height" type="number" min="0" step="0.01" placeholder="Height" />
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                {{-- Description --}}
                                                                <flux:textarea
                                                                    wire:model="variants.{{ $i }}.description"
                                                                    label="Description"
                                                                    rows="3"
                                                                    placeholder="Overrides the product short description for this variant. Leave blank to inherit." />

                                                            </div>
                                                        @endif

                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif

                                    </div>

                                    {{-- Bulk edit modal --}}
                                    <flux:modal name="bulk-variant-edit" class="md:w-[420px]" :dismissible="false">
                                        @php
                                            $bulkFieldLabels = [
                                                'price'            => ['label' => 'Set Price',          'unit' => 'Ksh'],
                                                'compare_at_price' => ['label' => 'Set Sale Price',     'unit' => 'Ksh'],
                                                'cost_price'       => ['label' => 'Set Cost Price',     'unit' => 'Ksh'],
                                                'stock_quantity'   => ['label' => 'Set Stock Quantity', 'unit' => null],
                                                'stock_status'     => ['label' => 'Set Stock Status',   'unit' => null],
                                            ];
                                            $current = $bulkFieldLabels[$bulkEditField] ?? ['label' => 'Bulk Edit', 'unit' => null];
                                        @endphp
                                        <div class="space-y-5">
                                            <div>
                                                <flux:heading size="lg">{{ $current['label'] }}</flux:heading>
                                                <flux:text class="mt-1 text-zinc-500">
                                                    Applies to {{ count($selectedVariantIndexes) }} selected variation(s).
                                                </flux:text>
                                            </div>

                                            @if (in_array($bulkEditField, ['price', 'compare_at_price', 'cost_price']))
                                                <flux:input
                                                    wire:model="bulkEditNumericValue"
                                                    :label="$current['label'].' ('.$current['unit'].')'"
                                                    type="number"
                                                    min="0"
                                                    step="0.01"
                                                    placeholder="0.00"
                                                    autofocus />
                                            @elseif ($bulkEditField === 'stock_quantity')
                                                <flux:input
                                                    wire:model="bulkEditNumericValue"
                                                    label="Stock Quantity"
                                                    type="number"
                                                    min="0"
                                                    placeholder="0"
                                                    autofocus />
                                            @elseif ($bulkEditField === 'stock_status')
                                                <flux:select wire:model="bulkEditSelectValue" label="Stock Status">
                                                    <flux:select.option value="">— Select —</flux:select.option>
                                                    @foreach (StockStatus::cases() as $s)
                                                        <flux:select.option :value="$s->value">{{ $s->label() }}</flux:select.option>
                                                    @endforeach
                                                </flux:select>
                                            @endif

                                            @error('bulkEditNumericValue') <flux:error>{{ $message }}</flux:error> @enderror
                                            @error('bulkEditSelectValue')  <flux:error>{{ $message }}</flux:error> @enderror

                                            <div class="flex justify-end gap-2">
                                                <flux:modal.close>
                                                    <flux:button variant="ghost" type="button">Cancel</flux:button>
                                                </flux:modal.close>
                                                <flux:button variant="primary" type="button" wire:click="applyBulkEdit">
                                                    Apply to selected
                                                </flux:button>
                                            </div>
                                        </div>
                                    </flux:modal>

                                @endif
                            </div>

                            {{-- Files --}}
                            <div x-show="tab === 'files'">
                                @if (! $productId)
                                    <div class="p-6 text-center text-sm text-zinc-400">Save the product first to manage downloadable files.</div>
                                @else
                                    <div class="space-y-4 p-6">
                                        @forelse ($downloadableFiles as $index => $file)
                                            <div class="overflow-hidden rounded-md border border-zinc-200 dark:border-zinc-700">
                                                <div @class(['flex items-center justify-between bg-zinc-50 px-4 py-3 dark:bg-zinc-800/60', 'border-b border-zinc-200 dark:border-zinc-700' => ! $file['collapsed']])>
                                                    <span class="text-sm font-medium dark:text-white">
                                                        {{ $file['name'] ?: 'New file' }}
                                                    </span>
                                                    <div class="flex items-center gap-1">
                                                        <flux:button size="xs" variant="ghost" icon="trash-2" type="button"
                                                            wire:click="removeFile({{ $index }})"
                                                            class="text-red-500! hover:text-red-600!" />
                                                        <button type="button" wire:click="toggleFileCollapsed({{ $index }})"
                                                            class="rounded p-1 text-zinc-400 hover:bg-zinc-200 hover:text-zinc-700 dark:hover:bg-zinc-700">
                                                            @if ($file['collapsed'])
                                                                <flux:icon.chevron-down variant="micro" class="size-4" />
                                                            @else
                                                                <flux:icon.chevron-up variant="micro" class="size-4" />
                                                            @endif
                                                        </button>
                                                    </div>
                                                </div>

                                                @if (! $file['collapsed'])
                                                    <div class="space-y-4 p-4">
                                                        <flux:input wire:model="downloadableFiles.{{ $index }}.name" label="File name" placeholder="e.g. User Manual v2" />
                                                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                                            <flux:input wire:model="downloadableFiles.{{ $index }}.download_limit" label="Download limit" type="number" min="1" placeholder="Unlimited" />
                                                            <flux:input wire:model="downloadableFiles.{{ $index }}.download_expiry_days" label="Expiry (days)" type="number" min="1" placeholder="Never" />
                                                            <flux:input wire:model="downloadableFiles.{{ $index }}.version" label="Version" placeholder="e.g. 1.0.0" />
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        @empty
                                            <p class="text-center text-sm text-zinc-400">No files added yet.</p>
                                        @endforelse
                                        <flux:button icon="plus" type="button" wire:click="addFile">Add file</flux:button>
                                    </div>
                                @endif
                            </div>

                            {{-- Linked Products --}}
                            <div x-show="tab === 'linked'">
                                @if (! $productId)
                                    <div class="p-6 text-center text-sm text-zinc-400">Save the product first to link products.</div>
                                @else
                                    <div class="space-y-6 p-6">

                                        {{-- Components: grouped children / bundle items --}}
                                        @if (in_array($type, ['grouped', 'bundled']))
                                            <div class="space-y-3">
                                                <div class="flex items-center justify-between gap-3">
                                                    <flux:heading size="sm">{{ $type === 'grouped' ? 'Grouped products' : 'Bundle components' }}</flux:heading>
                                                    <flux:button size="xs" icon="plus" type="button" wire:click="openLinkPicker('component')">Add</flux:button>
                                                </div>

                                                @forelse ($linkedProducts as $index => $linked)
                                                    <div wire:key="comp-{{ $linked['product_id'] }}" class="flex flex-wrap items-center gap-3 rounded-md border border-zinc-200 p-3 dark:border-zinc-700">
                                                        <div class="min-w-0 flex-1">
                                                            <div class="text-sm font-medium dark:text-white">{{ $linked['name'] }}</div>
                                                            @if ($linked['sku'])
                                                                <div class="font-mono text-xs text-zinc-400">{{ $linked['sku'] }}</div>
                                                            @endif
                                                        </div>
                                                        @if ($type === 'bundled')
                                                            <flux:input wire:model="linkedProducts.{{ $index }}.quantity" type="number" min="1" placeholder="Qty" class="w-16 text-right" />
                                                            <label class="flex items-center gap-1.5 text-xs text-zinc-500">
                                                                <flux:checkbox wire:model="linkedProducts.{{ $index }}.is_optional" />
                                                                Optional
                                                            </label>
                                                            <flux:input wire:model="linkedProducts.{{ $index }}.price_override" type="number" min="0" step="0.01" placeholder="Price override" class="w-32" />
                                                        @endif
                                                        <flux:button size="xs" variant="ghost" icon="trash-2" type="button"
                                                            wire:click="removeLinkedProduct({{ $index }})"
                                                            class="text-red-500! hover:text-red-600!" />
                                                    </div>
                                                @empty
                                                    <p class="text-sm text-zinc-400">
                                                        {{ $type === 'grouped' ? 'No products linked to this group yet.' : 'No components added to this bundle yet.' }}
                                                    </p>
                                                @endforelse
                                            </div>
                                        @endif

                                        {{-- Recommendations: upsells / cross-sells / accessories / spare parts --}}
                                        @if (in_array($type, ['simple', 'variable', 'bundled']))
                                            @if ($type === 'bundled')
                                                <flux:separator />
                                            @endif
                                            @foreach (ProductLinkType::cases() as $linkType)
                                                @php $tKey = $linkType->value; @endphp
                                                <div class="space-y-3">
                                                    <div class="flex items-center justify-between gap-3">
                                                        <div class="flex items-center gap-2">
                                                            <flux:icon :icon="$linkType->icon()" variant="micro" class="size-4 text-zinc-400" />
                                                            <flux:heading size="sm">{{ $linkType->label() }}</flux:heading>
                                                        </div>
                                                        <flux:button size="xs" icon="plus" type="button" wire:click="openLinkPicker('{{ $tKey }}')">Add</flux:button>
                                                    </div>

                                                    @forelse ($productLinks[$tKey] as $index => $linked)
                                                        <div wire:key="link-{{ $tKey }}-{{ $linked['product_id'] }}" class="flex flex-wrap items-center gap-3 rounded-md border border-zinc-200 p-3 dark:border-zinc-700">
                                                            <div class="min-w-0 flex-1">
                                                                <div class="text-sm font-medium dark:text-white">{{ $linked['name'] }}</div>
                                                                @if ($linked['sku'])
                                                                    <div class="font-mono text-xs text-zinc-400">{{ $linked['sku'] }}</div>
                                                                @endif
                                                            </div>
                                                            <flux:button size="xs" variant="ghost" icon="trash-2" type="button"
                                                                wire:click="removeProductLink('{{ $tKey }}', {{ $index }})"
                                                                class="text-red-500! hover:text-red-600!" />
                                                        </div>
                                                    @empty
                                                        <p class="text-sm text-zinc-400">No {{ strtolower($linkType->label()) }} added yet.</p>
                                                    @endforelse
                                                </div>

                                                @if (! $loop->last)
                                                    <flux:separator />
                                                @endif
                                            @endforeach
                                        @endif

                                        {{-- Shared product picker modal --}}
                                        <flux:modal wire:model.self="showLinkPicker" class="md:w-180 lg:w-215 md:max-w-none">
                                            @php
                                                $pickerTitles = [
                                                    'component' => $type === 'grouped' ? 'Add grouped products' : 'Add bundle components',
                                                    'upsell' => 'Add upsells',
                                                    'cross_sell' => 'Add cross-sells',
                                                    'accessory' => 'Add accessories',
                                                    'spare_part' => 'Add spare parts',
                                                ];
                                            @endphp
                                            <flux:heading size="lg">{{ $pickerTitles[$linkPickerTarget] ?? 'Add products' }}</flux:heading>
                                            <flux:subheading>Search the catalog and add as many as you need.</flux:subheading>

                                            <div class="mt-4 space-y-4">
                                                <flux:input
                                                    wire:model.live.debounce.300ms="linkPickerSearch"
                                                    icon="magnifying-glass"
                                                    placeholder="Search by name, SKU or model number…"
                                                    autofocus
                                                    clearable />

                                                <div class="@container max-h-96 overflow-y-auto scrollbar-thin">
                                                    @if ($this->linkPickerResults->isEmpty())
                                                        <div class="py-12 text-center text-sm text-zinc-400">
                                                            {{ strlen(trim($linkPickerSearch)) >= 2 ? 'No matching products.' : 'No products available to add.' }}
                                                        </div>
                                                    @else
                                                        <div class="grid grid-cols-1 gap-3 @xs:grid-cols-2 @lg:grid-cols-3 @2xl:grid-cols-4">
                                                            @foreach ($this->linkPickerResults as $result)
                                                                <div wire:key="pick-{{ $result->id }}"
                                                                    class="group flex flex-col overflow-hidden rounded-md border border-zinc-200 bg-white transition hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900">
                                                                    <div class="relative aspect-square overflow-hidden bg-zinc-50 p-2 dark:bg-zinc-800">
                                                                        @if ($result->cover_url)
                                                                            <img src="{{ $result->cover_url }}" alt="{{ $result->name }}" class="size-full object-contain" loading="lazy" />
                                                                        @else
                                                                            <div class="flex size-full items-center justify-center text-zinc-300 dark:text-zinc-600">
                                                                                <flux:icon.photo class="size-7" />
                                                                            </div>
                                                                        @endif
                                                                        <flux:tooltip content="Add">
                                                                            <button type="button" wire:click="pickLink({{ $result->id }})"
                                                                                aria-label="Add {{ $result->name }}"
                                                                                class="absolute right-2 bottom-2 inline-flex size-8 cursor-pointer items-center justify-center rounded-full bg-brand-500 text-white shadow-md transition hover:bg-brand-600">
                                                                                <flux:icon.plus variant="micro" class="size-4" />
                                                                            </button>
                                                                        </flux:tooltip>
                                                                    </div>
                                                                    <div class="flex flex-1 flex-col border-t border-zinc-100 px-3 py-2.5 dark:border-zinc-800">
                                                                        @if ($result->brand)
                                                                            <div class="truncate text-[9.5px] font-bold tracking-[0.08em] text-brand-600 uppercase dark:text-brand-400">{{ $result->brand->name }}</div>
                                                                        @endif
                                                                        <div class="mt-0.5 line-clamp-2 min-h-8 text-[12px] font-medium leading-snug dark:text-white">{{ $result->name }}</div>
                                                                        @if ($result->sku)
                                                                            <div class="mt-1 truncate font-mono text-[11px] text-zinc-400">{{ $result->sku }}</div>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>

                                                        @if ($this->linkPickerResults->hasMorePages())
                                                            <div wire:intersect="loadMoreLinks" class="flex justify-center py-4">
                                                                <flux:icon.loading class="size-5 text-zinc-400" />
                                                            </div>
                                                        @endif
                                                    @endif
                                                </div>

                                                <div class="flex items-center justify-between border-t border-zinc-200 pt-4 dark:border-zinc-700">
                                                    <span class="text-xs text-zinc-400">{{ $this->linkPickerResults->total() }} product(s)</span>
                                                    <flux:button variant="primary" type="button" wire:click="$set('showLinkPicker', false)">Done</flux:button>
                                                </div>
                                            </div>
                                        </flux:modal>
                                    </div>
                                @endif
                            </div>

                            {{-- Advanced --}}
                            <div x-show="tab === 'advanced'" class="space-y-4 p-6">
                                <div class="flex items-center justify-between rounded-md bg-zinc-50 px-3 py-2.5 dark:bg-zinc-800">
                                    <div>
                                        <flux:label>Requires quotation</flux:label>
                                        <flux:text size="sm" class="text-xs">Hide price and show "Request a quote" instead.</flux:text>
                                    </div>
                                    <flux:switch wire:model.live="requires_quotation" />
                                </div>
                                @if ($requires_quotation)
                                    <flux:textarea wire:model="quotation_notes" label="Quotation notes" rows="2"
                                        placeholder="Internal notes shown to the sales team…" />
                                @endif
                                <flux:input wire:model="sort_order" label="Sort order" type="number" min="0" />
                            </div>

                        </div>{{-- end tab content --}}
                    </div>{{-- end flex row --}}
                    </div>{{-- end x-collapse --}}

                </div>

                {{-- Description --}}
                <div x-data="{ open: {{ ($short_description || $description || $technical_specification) ? 'true' : 'false' }} }"
                     class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                    <button type="button" x-on:click="open = !open"
                            class="flex w-full items-center justify-between px-6 py-4"
                            :class="open ? 'border-b border-zinc-200 dark:border-zinc-700' : ''">
                        <flux:heading size="base">Description</flux:heading>
                        <span class="inline-flex transition-transform duration-200" :class="open ? 'rotate-180' : ''">
                        <flux:icon.chevron-down variant="micro" class="size-4 text-zinc-400" />
                    </span>
                    </button>
                    <div x-show="open" x-collapse x-cloak>
                        <div class="space-y-4 p-6">
                            <x-admin.rich-editor
                                wire:model="short_description"
                                label="Short description"
                                placeholder="One or two sentences summarising the product…"
                                rows="sm"
                            />
                            <x-admin.rich-editor
                                wire:model="description"
                                label="Full description"
                                placeholder="Detailed product description…"
                                rows="lg"
                            />
                            <x-admin.rich-editor
                                wire:model="technical_specification"
                                label="Technical specification"
                                placeholder="Add a table with specs — dimensions, voltage, capacity, certifications…"
                                rows="md"
                                :with-table="true"
                            />
                        </div>
                    </div>
                </div>

                {{-- SEO --}}
                <div x-data="{ open: {{ ($meta_title || $meta_description || $canonical_url) ? 'true' : 'false' }} }"
                     class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                    <button type="button" x-on:click="open = !open"
                            class="flex w-full items-center justify-between px-6 py-4"
                            :class="open ? 'border-b border-zinc-200 dark:border-zinc-700' : ''">
                        <flux:heading size="base">SEO</flux:heading>
                        <span class="inline-flex transition-transform duration-200" :class="open ? 'rotate-180' : ''">
                        <flux:icon.chevron-down variant="micro" class="size-4 text-zinc-400" />
                    </span>
                    </button>
                    <div x-show="open" x-collapse x-cloak>
                        <div class="space-y-4 p-6">
                            <flux:input wire:model="meta_title" label="Meta title" placeholder="Defaults to product name" />
                            <flux:textarea wire:model="meta_description" label="Meta description" rows="3"
                                placeholder="Brief description for search engine results…" />
                            <flux:input wire:model="canonical_url" label="Canonical URL" placeholder="https://…" />
                        </div>
                    </div>
                </div>

            </div>

            {{-- Sidebar (1 col) --}}
            <div class="space-y-6">

                {{-- Status & Visibility --}}
                <flux:card x-data="{ open: true }" class="p-0 overflow-hidden">
                    <div class="flex items-center justify-between px-6 py-4"
                         :class="open ? 'border-b border-zinc-200 dark:border-zinc-700' : ''">
                        <div class="flex items-center gap-3">
                            <button type="button" x-on:click="open = !open">
                                <flux:heading size="sm">Status & visibility</flux:heading>
                            </button>
                            <flux:badge size="sm" :color="ProductStatus::from($status)->badgeColor()">
                                {{ ProductStatus::from($status)->label() }}
                            </flux:badge>
                        </div>
                        <button type="button" x-on:click="open = !open"
                                class="inline-flex transition-transform duration-200" :class="open ? 'rotate-180' : ''">
                            <flux:icon.chevron-down variant="micro" class="size-4 text-zinc-400" />
                        </button>
                    </div>
                    <div x-show="open" x-collapse x-cloak>
                        <div class="space-y-4 p-6">
                            <flux:select wire:model.live="status" label="Status">
                                @foreach (ProductStatus::cases() as $s)
                                    <flux:select.option :value="$s->value">{{ $s->label() }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            @if ($status === 'scheduled')
                                <flux:input wire:model="published_at" type="datetime-local" label="Publish on"
                                    min="{{ now()->format('Y-m-d\TH:i') }}"
                                    description="The product goes live automatically at this time."
                                    required />
                            @elseif ($status === 'published' && $published_at)
                                <flux:text size="sm" class="text-zinc-500">
                                    Published {{ \Illuminate\Support\Carbon::parse($published_at)->format('M j, Y · g:i A') }}
                                </flux:text>
                            @endif
                            <flux:select wire:model="visibility" label="Visibility">
                                @foreach (ProductVisibility::cases() as $v)
                                    <flux:select.option :value="$v->value">{{ $v->label() }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>
                    </div>
                </flux:card>

                {{-- Product Image --}}
                <flux:card x-data="{ open: true }" class="p-0 overflow-hidden">
                    <button type="button" x-on:click="open = !open"
                            class="flex w-full items-center justify-between px-6 py-4"
                            :class="open ? 'border-b border-zinc-200 dark:border-zinc-700' : ''">
                        <flux:heading size="sm">Product image</flux:heading>
                        <span class="inline-flex transition-transform duration-200" :class="open ? 'rotate-180' : ''">
                        <flux:icon.chevron-down variant="micro" class="size-4 text-zinc-400" />
                    </span>
                    </button>
                    <div x-show="open" x-collapse x-cloak>
                    <div class="p-6">
                        @if ($pendingCoverImage)
                            <div class="group relative overflow-hidden rounded-md border border-zinc-200 dark:border-zinc-700">
                                <img src="{{ $pendingCoverImage->temporaryUrl() }}" class="h-48 w-full object-cover" alt="Preview" />
                                <button type="button" wire:click="removeCoverImage"
                                    class="absolute right-2 top-2 rounded-full bg-white/90 p-1 shadow hover:bg-white dark:bg-zinc-900/90">
                                    <flux:icon.x-mark variant="micro" class="size-4 text-zinc-600" />
                                </button>
                            </div>
                        @elseif ($coverImage)
                            <div class="group relative overflow-hidden rounded-md border border-zinc-200 dark:border-zinc-700">
                                <img src="{{ $coverImage['url'] }}" class="h-48 w-full object-cover" alt="{{ $coverImage['alt'] }}" />
                                <button type="button" wire:click="removeCoverImage"
                                    class="absolute right-2 top-2 rounded-full bg-white/90 p-1 shadow hover:bg-white dark:bg-zinc-900/90">
                                    <flux:icon.x-mark variant="micro" class="size-4 text-zinc-600" />
                                </button>
                            </div>
                        @else
                            <label class="flex h-36 cursor-pointer flex-col items-center justify-center gap-2 rounded-md border-2 border-dashed border-zinc-300 transition hover:border-zinc-400 dark:border-zinc-700 dark:hover:border-zinc-600">
                                <flux:icon.arrow-up-tray class="size-6 text-zinc-400" />
                                <flux:text size="sm" class="text-zinc-400">Click to upload cover image</flux:text>
                                <input type="file" wire:model="pendingCoverImage" accept="image/*" class="sr-only" />
                            </label>
                        @endif

                        <div wire:loading wire:target="pendingCoverImage" class="mt-2 text-xs text-zinc-400">
                            Uploading…
                        </div>
                    </div>
                    </div>{{-- end x-collapse --}}
                </flux:card>

                {{-- Product Gallery --}}
                <flux:card x-data="{ open: {{ (!empty($galleryImages) || !empty($pendingGalleryImages)) ? 'true' : 'false' }} }" class="p-0 overflow-hidden">
                    <button type="button" x-on:click="open = !open"
                            class="flex w-full items-center justify-between px-6 py-4"
                            :class="open ? 'border-b border-zinc-200 dark:border-zinc-700' : ''">
                        <flux:heading size="sm">Product gallery</flux:heading>
                        <span class="inline-flex transition-transform duration-200" :class="open ? 'rotate-180' : ''">
                        <flux:icon.chevron-down variant="micro" class="size-4 text-zinc-400" />
                    </span>
                    </button>
                    <div x-show="open" x-collapse x-cloak>
                    <div class="p-6 space-y-3">
                        @if (!empty($galleryImages) || !empty($pendingGalleryImages))
                            <div class="grid grid-cols-3 gap-2">
                                @foreach ($galleryImages as $i => $img)
                                    <div class="group relative overflow-hidden rounded-md border border-zinc-200 dark:border-zinc-700">
                                        <img src="{{ $img['url'] }}" class="h-20 w-full object-cover" alt="{{ $img['alt'] }}" />
                                        <button type="button" wire:click="removeGalleryImage({{ $i }})"
                                            class="absolute right-1 top-1 rounded-full bg-white/90 p-0.5 shadow hover:bg-white dark:bg-zinc-900/90">
                                            <flux:icon.x-mark variant="micro" class="size-3 text-zinc-600" />
                                        </button>
                                    </div>
                                @endforeach

                                @foreach ($pendingGalleryImages as $img)
                                    <div class="relative overflow-hidden rounded-md border border-zinc-200 dark:border-zinc-700">
                                        <img src="{{ $img->temporaryUrl() }}" class="h-20 w-full object-cover" alt="Preview" />
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <label class="flex cursor-pointer items-center justify-center gap-2 rounded-md border border-dashed border-zinc-300 py-3 text-sm text-zinc-400 transition hover:border-zinc-400 dark:border-zinc-700 dark:hover:border-zinc-600">
                            <flux:icon.plus variant="micro" class="size-4" />
                            Add images
                            <input type="file" wire:model="pendingGalleryImages" accept="image/*" multiple class="sr-only" />
                        </label>

                        <div wire:loading wire:target="pendingGalleryImages" class="text-xs text-zinc-400">
                            Uploading…
                        </div>
                    </div>
                    </div>{{-- end x-collapse --}}
                </flux:card>

                {{-- Brand --}}
                <flux:card x-data="{ open: true }" class="p-0 overflow-hidden">
                    <button type="button" x-on:click="open = !open"
                            class="flex w-full items-center justify-between px-6 py-4"
                            :class="open ? 'border-b border-zinc-200 dark:border-zinc-700' : ''">
                        <flux:heading size="sm">Brand</flux:heading>
                        <span class="inline-flex transition-transform duration-200" :class="open ? 'rotate-180' : ''">
                        <flux:icon.chevron-down variant="micro" class="size-4 text-zinc-400" />
                    </span>
                    </button>
                    <div x-show="open" x-collapse x-cloak>
                        <div class="p-6">
                            <flux:select wire:model="brand_id">
                                <flux:select.option value="">No brand</flux:select.option>
                                @foreach ($this->brands as $brand)
                                    <flux:select.option :value="$brand->id">{{ $brand->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>
                    </div>
                </flux:card>

                {{-- Category --}}
                <flux:card x-data="{ open: true }" class="p-0 overflow-hidden">
                    <button type="button" x-on:click="open = !open"
                            class="flex w-full items-center justify-between px-6 py-4"
                            :class="open ? 'border-b border-zinc-200 dark:border-zinc-700' : ''">
                        <flux:heading size="sm">Category</flux:heading>
                        <span class="inline-flex transition-transform duration-200" :class="open ? 'rotate-180' : ''">
                        <flux:icon.chevron-down variant="micro" class="size-4 text-zinc-400" />
                    </span>
                    </button>
                    <div x-show="open" x-collapse x-cloak>
                        <div class="p-6">
                            <flux:select wire:model="primary_category_id">
                                <flux:select.option value="">No category</flux:select.option>
                                @foreach ($this->categories as $cat)
                                    <flux:select.option :value="$cat->id">{{ $cat->name }}</flux:select.option>
                                    @foreach ($cat->children as $child)
                                        <flux:select.option :value="$child->id">&nbsp;&nbsp;&nbsp;{{ $child->name }}</flux:select.option>
                                    @endforeach
                                @endforeach
                            </flux:select>
                        </div>
                    </div>
                </flux:card>

                {{-- Tags --}}
                <flux:card x-data="{ open: {{ !empty($selectedTags) ? 'true' : 'false' }} }" class="p-0 overflow-hidden">
                    <button type="button" x-on:click="open = !open"
                            class="flex w-full items-center justify-between px-6 py-4"
                            :class="open ? 'border-b border-zinc-200 dark:border-zinc-700' : ''">
                        <flux:heading size="sm">Tags</flux:heading>
                        <span class="inline-flex transition-transform duration-200" :class="open ? 'rotate-180' : ''">
                        <flux:icon.chevron-down variant="micro" class="size-4 text-zinc-400" />
                    </span>
                    </button>
                    <div x-show="open" x-collapse x-cloak>
                        <div class="space-y-3 p-6">
                            @if (!empty($selectedTags))
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ($selectedTags as $index => $tag)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                            {{ $tag['name'] }}
                                            <button type="button" wire:click="removeTag({{ $index }})"
                                                class="ml-0.5 text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200">
                                                <flux:icon.x-mark variant="micro" class="size-3" />
                                            </button>
                                        </span>
                                    @endforeach
                                </div>
                            @endif

                            <div class="relative">
                                <flux:input
                                    wire:model.live.debounce.300ms="tagSearch"
                                    placeholder="Search tags…"
                                    clearable
                                    size="sm" />
                                @if ($this->tagResults->isNotEmpty())
                                    <div class="absolute z-10 mt-1 w-full overflow-hidden rounded-md border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-800">
                                        @foreach ($this->tagResults as $tag)
                                            <button type="button"
                                                wire:click="addTag({{ $tag->id }}, '{{ addslashes($tag->name) }}')"
                                                class="flex w-full items-center px-3 py-2 text-left text-sm hover:bg-zinc-100 dark:hover:bg-zinc-700">
                                                {{ $tag->name }}
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </flux:card>

            </div>
        </div>
    </form>
</div>
