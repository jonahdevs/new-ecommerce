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
        <flux:card class="p-0" x-data="{ open: true, tab: 'general' }"
            x-effect="
                if (tab === 'shipping' && ($wire.form.is_virtual || $wire.form.type === 'grouped')) { tab = 'general'; }
                if (tab === 'downloads' && !$wire.form.is_downloadable) { tab = 'general'; }
                if (tab === 'variations' && $wire.form.type !== 'variable') { tab = 'general'; }
                if (tab === 'inventory' && $wire.form.type === 'grouped') { tab = 'general'; }
                if (tab === 'attributes' && ($wire.form.type === 'grouped' || $wire.form.type === 'bundle')) { tab = 'general'; }
            ">

            {{-- Card header --}}
            <div class="flex items-center justify-between px-3 py-2 border-b dark:border-zinc-600">
                <div class="flex items-center gap-3 flex-wrap">
                    <flux:heading class="shrink-0">Product Data</flux:heading>
                    <flux:select wire:model.live="form.type" size="xs" class="w-fit">
                        @foreach (App\Enums\ProductType::cases() as $t)
                            <flux:select.option value="{{ $t->value }}">{{ $t->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    {{-- Virtual/Downloadable only for simple and variable products --}}
                    <div x-show="$wire.form.type === 'simple' || $wire.form.type === 'variable'" x-cloak
                        class="flex items-center gap-4 border-l pl-3 dark:border-zinc-600">
                        <flux:checkbox wire:model.live="form.is_virtual" label="Virtual" />
                        <flux:checkbox wire:model.live="form.is_downloadable" label="Downloadable" />
                    </div>
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
                                ['id' => 'downloads', 'label' => 'Downloadable Files', 'icon' => 'arrow-down-tray'],
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
                                    'text-zinc-500 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 hover:text-zinc-700 dark:hover:text-zinc-300'"
                                @if ($t['id'] === 'shipping') x-show="!$wire.form.is_virtual && $wire.form.type !== 'grouped'" @endif
                                @if ($t['id'] === 'downloads') x-show="$wire.form.is_downloadable" @endif
                                @if ($t['id'] === 'variations') x-show="$wire.form.type === 'variable'" @endif
                                @if ($t['id'] === 'inventory') x-show="$wire.form.type !== 'grouped'" @endif
                                @if ($t['id'] === 'attributes') x-show="$wire.form.type !== 'grouped' && $wire.form.type !== 'bundle'" @endif>
                                <flux:icon :name="$t['icon']" variant="outline" class="size-4 shrink-0" />
                                {{ $t['label'] }}
                            </button>
                        @endforeach
                    </nav>

                    {{-- ── Tab panels ── --}}
                    <div class="flex-1 p-5">

                        {{-- ── General ── --}}
                        <div x-show="tab === 'general'" x-cloak class="space-y-5">

                            {{-- Grouped Product Notice --}}
                            <div x-show="$wire.form.type === 'grouped'" x-cloak
                                class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                                <div class="flex items-start gap-3">
                                    <flux:icon.information-circle variant="outline"
                                        class="size-5 text-blue-500 shrink-0 mt-0.5" />
                                    <div class="text-sm text-blue-700 dark:text-blue-300">
                                        <p class="font-medium">Grouped products have no price</p>
                                        <p class="mt-1 text-blue-600 dark:text-blue-400">
                                            Each child product has its own price. Customers select which items to
                                            purchase
                                            from the Linked Products tab.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {{-- Bundle Pricing --}}
                            <div x-show="$wire.form.type === 'bundle'" x-cloak class="space-y-5">
                                <div
                                    class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                                    <div class="flex items-start gap-3">
                                        <flux:icon.gift variant="outline"
                                            class="size-5 text-green-500 shrink-0 mt-0.5" />
                                        <div class="text-sm text-green-700 dark:text-green-300">
                                            <p class="font-medium">Bundle pricing</p>
                                            <p class="mt-1 text-green-600 dark:text-green-400">
                                                Set a bundle price below the combined value of items to offer a
                                                discount.
                                                Add items in the Linked Products tab.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-5">
                                    <flux:field>
                                        <div class="flex items-center gap-1.5 mb-1">
                                            <flux:label>Bundle Price ({{ get_currency_symbol() }})</flux:label>
                                            <flux:tooltip content="The price customers pay for the entire bundle.">
                                                <flux:icon.information-circle variant="outline"
                                                    class="size-3.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 cursor-help" />
                                            </flux:tooltip>
                                        </div>
                                        <flux:input wire:model.live="form.price" type="number" step="0.01"
                                            min="0" placeholder="0.00" />
                                        <flux:error name="form.price" />
                                    </flux:field>

                                    <flux:field>
                                        <div class="flex items-center gap-1.5 mb-1">
                                            <flux:label>Sale Price ({{ get_currency_symbol() }})</flux:label>
                                            <flux:tooltip content="Optional discounted bundle price.">
                                                <flux:icon.information-circle variant="outline"
                                                    class="size-3.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 cursor-help" />
                                            </flux:tooltip>
                                        </div>
                                        <flux:input wire:model.live="form.sale_price" type="number" step="0.01"
                                            min="0" placeholder="0.00" />
                                        <flux:error name="form.sale_price" />
                                    </flux:field>
                                </div>

                                {{-- Bundle value comparison --}}
                                @if (!empty($form->bundle_products))
                                    <div
                                        class="p-3 bg-zinc-50 dark:bg-zinc-800/60 rounded-lg border dark:border-zinc-700">
                                        <div class="flex justify-between items-center text-sm">
                                            <span class="text-zinc-500">Items value (if bought separately):</span>
                                            <span
                                                class="font-medium text-zinc-700 dark:text-zinc-300">{{ format_currency($this->bundleValue) }}</span>
                                        </div>
                                        @if ($this->bundleSavingsPercent)
                                            <div
                                                class="flex justify-between items-center text-sm mt-2 pt-2 border-t dark:border-zinc-700">
                                                <span class="text-zinc-500">Customer saves:</span>
                                                <span class="font-medium text-green-600 dark:text-green-400">
                                                    {{ format_currency($this->bundleValue - ($form->sale_price !== '' ? $form->sale_price : $form->price)) }}
                                                    ({{ $this->bundleSavingsPercent }}%)
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            {{-- Simple/Variable Pricing --}}
                            <div x-show="$wire.form.type === 'simple' || $wire.form.type === 'variable'" x-cloak>
                                <div class="grid grid-cols-2 gap-5">
                                    <flux:field>
                                        <div class="flex items-center gap-1.5 mb-1">
                                            <flux:label>Regular Price ({{ get_currency_symbol() }})</flux:label>
                                            <flux:tooltip content="The standard selling price before any discounts.">
                                                <flux:icon.information-circle variant="outline"
                                                    class="size-3.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 cursor-help" />
                                            </flux:tooltip>
                                        </div>
                                        <flux:input wire:model="form.price" type="number" step="0.01"
                                            min="0" placeholder="0.00" />
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
                            </div>

                            {{-- Tax Class - for all except grouped --}}
                            <div x-show="$wire.form.type !== 'grouped'" x-cloak>
                                <flux:field>
                                    <div class="flex items-center gap-1.5 mb-1">
                                        <flux:label>Tax Class</flux:label>
                                        <flux:tooltip content="The tax class that applies to this product.">
                                            <flux:icon.information-circle variant="outline"
                                                class="size-3.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 cursor-help" />
                                        </flux:tooltip>
                                    </div>

                                    <flux:select wire:model="form.tax_class_id">
                                        <flux:select.option value="">— None —</flux:select.option>
                                        @foreach ($this->taxClasses as $tc)
                                            <flux:select.option value="{{ $tc->id }}">{{ $tc->name }}
                                                ({{ $tc->rateLabel() }})
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>

                                    <flux:error name="form.tax_class_id" />
                                </flux:field>
                            </div>
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

                        {{-- ── Downloadable Files ── --}}
                        <div x-show="tab === 'downloads'" x-cloak class="space-y-5">

                            <div class="grid grid-cols-2 gap-5">
                                <flux:field>
                                    <div class="flex items-center gap-1.5 mb-1">
                                        <flux:label>Download Limit</flux:label>
                                        <flux:tooltip
                                            content="Number of times each customer can download. Leave blank for unlimited.">
                                            <flux:icon.information-circle variant="outline"
                                                class="size-3.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 cursor-help" />
                                        </flux:tooltip>
                                    </div>
                                    <flux:input wire:model="form.download_limit" type="number" min="0"
                                        placeholder="Unlimited" />
                                    <flux:error name="form.download_limit" />
                                </flux:field>

                                <flux:field>
                                    <div class="flex items-center gap-1.5 mb-1">
                                        <flux:label>Download Expiry (days)</flux:label>
                                        <flux:tooltip
                                            content="Days after purchase before the download link expires. Leave blank for no expiry.">
                                            <flux:icon.information-circle variant="outline"
                                                class="size-3.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 cursor-help" />
                                        </flux:tooltip>
                                    </div>
                                    <flux:input wire:model="form.download_expiry" type="number" min="0"
                                        placeholder="Never expires" />
                                    <flux:error name="form.download_expiry" />
                                </flux:field>
                            </div>

                            {{-- Existing downloads --}}
                            @if (!empty($form->existing_downloads))
                                <div class="space-y-2">
                                    <flux:label>Uploaded Files</flux:label>
                                    @foreach ($form->existing_downloads as $dIdx => $dl)
                                        <div class="flex items-center gap-3 p-3 bg-zinc-50 dark:bg-zinc-800/60 rounded-md border dark:border-zinc-700"
                                            wire:key="dl-{{ $dl['id'] }}">
                                            <flux:icon.document-text variant="outline"
                                                class="size-5 text-zinc-400 shrink-0" />
                                            <div class="flex-1 min-w-0">
                                                <flux:input
                                                    wire:model="form.existing_downloads.{{ $dIdx }}.name"
                                                    size="sm" />
                                                <p class="text-xs text-zinc-400 mt-0.5">
                                                    {{ $dl['file_name'] }}
                                                    @if ($dl['formatted_file_size'])
                                                        &bull; {{ $dl['formatted_file_size'] }}
                                                    @endif
                                                </p>
                                            </div>
                                            <flux:button type="button" icon="trash" variant="ghost"
                                                size="xs" class="text-red-500! cursor-pointer shrink-0"
                                                wire:click="removeDownload({{ $dl['id'] }})"
                                                wire:confirm="Remove this download file?" />
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Pending new uploads --}}
                            @if (!empty($form->new_download_files))
                                <div class="space-y-2">
                                    <flux:label>Pending Uploads</flux:label>
                                    @foreach ($form->new_download_files as $nIdx => $nFile)
                                        <div class="flex items-center gap-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-md border border-blue-200 dark:border-blue-800"
                                            wire:key="ndl-{{ $nIdx }}">
                                            <flux:icon.document variant="outline"
                                                class="size-5 text-blue-400 shrink-0" />
                                            <div class="flex-1 min-w-0">
                                                <flux:input wire:model="form.new_download_names.{{ $nIdx }}"
                                                    size="sm"
                                                    placeholder="{{ $nFile->getClientOriginalName() }}" />
                                                <p class="text-xs text-zinc-400 mt-0.5">
                                                    {{ $nFile->getClientOriginalName() }}
                                                </p>
                                            </div>
                                            <flux:button type="button" icon="x-mark" variant="ghost"
                                                size="xs" class="text-red-500! cursor-pointer shrink-0"
                                                wire:click="removeNewDownload({{ $nIdx }})" />
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Upload button --}}
                            <input type="file" id="download-files-input" class="hidden"
                                wire:model="form.new_download_files" multiple />

                            <div wire:loading wire:target="form.new_download_files"
                                class="flex items-center gap-2 text-sm text-zinc-500">
                                <flux:icon.loading class="size-4" /> Uploading...
                            </div>

                            <flux:button wire:loading.remove wire:target="form.new_download_files" type="button"
                                icon="paper-clip" variant="ghost"
                                class="cursor-pointer border border-dashed dark:border-zinc-600"
                                @click="document.getElementById('download-files-input').click()">
                                Add Downloadable File
                            </flux:button>

                            <flux:error name="form.new_download_files.*" />
                        </div>

                        {{-- ── Linked Products ── --}}
                        <div x-show="tab === 'linked'" x-cloak class="space-y-6">

                            {{-- Grouped Products Section - Only for grouped product type --}}
                            <div x-show="$wire.form.type === 'grouped'" x-cloak>
                                <flux:heading size="sm">Grouped Products</flux:heading>
                                <flux:text class="text-xs text-zinc-500 mb-2">
                                    Products that make up this grouped product. Customers can select which items to
                                    purchase.
                                </flux:text>

                                {{-- Search input --}}
                                <div class="relative mb-2" x-data="{ open: false }" x-on:click.outside="open = false">
                                    <flux:input wire:model.live.debounce.300ms="groupedQuery"
                                        placeholder="Search simple products by name or SKU..." icon="magnifying-glass"
                                        @focus="open = true" @keydown.enter.prevent />

                                    @if (count($this->groupedResults) > 0)
                                        <div x-show="open"
                                            class="absolute z-20 w-full mt-1 bg-white dark:bg-zinc-800 border dark:border-zinc-700 rounded-lg shadow-lg overflow-hidden">
                                            @foreach ($this->groupedResults as $p)
                                                <button type="button"
                                                    wire:click="addGroupedProduct({{ $p['id'] }})"
                                                    @click="open = false"
                                                    class="flex items-center gap-2 w-full px-3 py-2 text-sm hover:bg-zinc-50 dark:hover:bg-zinc-700 text-left cursor-pointer">
                                                    <span
                                                        class="font-medium text-zinc-900 dark:text-zinc-100 flex-1">{{ $p['name'] }}</span>
                                                    @if ($p['sku'] ?? null)
                                                        <span class="text-xs text-zinc-400">{{ $p['sku'] }}</span>
                                                    @endif
                                                    @if ($p['price'] ?? null)
                                                        <span
                                                            class="text-xs text-zinc-500">{{ format_currency($p['price']) }}</span>
                                                    @endif
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                {{-- Selected grouped products --}}
                                @if (!empty($form->grouped_products))
                                    <div class="space-y-1">
                                        @foreach ($form->grouped_products as $idx => $p)
                                            <div class="flex items-center justify-between px-3 py-2 bg-zinc-50 dark:bg-zinc-800/60 rounded-md border dark:border-zinc-700"
                                                wire:key="grouped-{{ $p['id'] }}">
                                                <div class="flex-1 text-sm">
                                                    <span
                                                        class="font-medium text-zinc-800 dark:text-zinc-200">{{ $p['name'] }}</span>
                                                    @if ($p['sku'] ?? null)
                                                        <span class="text-zinc-400 ms-2">{{ $p['sku'] }}</span>
                                                    @endif
                                                    @if ($p['price'] ?? null)
                                                        <span
                                                            class="text-zinc-500 ms-2">{{ format_currency($p['price']) }}</span>
                                                    @endif
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <div class="flex items-center gap-1">
                                                        <span class="text-xs text-zinc-500">Qty:</span>
                                                        <flux:input type="number" min="1"
                                                            wire:model.live="form.grouped_products.{{ $idx }}.quantity"
                                                            class="w-16 text-center" size="xs"
                                                            @keydown.enter.prevent />
                                                    </div>
                                                    <flux:button type="button" icon="x-mark" variant="ghost"
                                                        size="xs"
                                                        class="text-zinc-400 hover:text-red-500 cursor-pointer"
                                                        wire:click="removeGroupedProduct({{ $idx }})" />
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    {{-- Total summary --}}
                                    @php
                                        $groupedTotal = collect($form->grouped_products)->sum(
                                            fn($p) => ($p['price'] ?? 0) * ($p['quantity'] ?? 1),
                                        );
                                    @endphp
                                    <div
                                        class="mt-3 pt-3 border-t dark:border-zinc-700 flex justify-between items-center">
                                        <span class="text-sm text-zinc-500">{{ count($form->grouped_products) }}
                                            product(s)</span>
                                        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                            Kit Total: {{ format_currency($groupedTotal) }}
                                        </span>
                                    </div>
                                @else
                                    <p class="text-xs text-zinc-400">No products added yet. Search and add simple
                                        products above.</p>
                                @endif
                            </div>

                            {{-- Bundle Products Section - Only for bundle product type --}}
                            <div x-show="$wire.form.type === 'bundle'" x-cloak>
                                <flux:heading size="sm">Bundle Contents</flux:heading>
                                <flux:text class="text-xs text-zinc-500 mb-2">
                                    Products included in this bundle. The bundle is sold as one item at the bundle
                                    price.
                                </flux:text>

                                {{-- Search input --}}
                                <div class="relative mb-2" x-data="{ open: false }" x-on:click.outside="open = false">
                                    <flux:input wire:model.live.debounce.300ms="bundleQuery"
                                        placeholder="Search simple products by name or SKU..." icon="magnifying-glass"
                                        @focus="open = true" @keydown.enter.prevent />

                                    @if (count($this->bundleResults) > 0)
                                        <div x-show="open"
                                            class="absolute z-20 w-full mt-1 bg-white dark:bg-zinc-800 border dark:border-zinc-700 rounded-lg shadow-lg overflow-hidden">
                                            @foreach ($this->bundleResults as $p)
                                                <button type="button"
                                                    wire:click="addBundleProduct({{ $p['id'] }})"
                                                    @click="open = false"
                                                    class="flex items-center gap-2 w-full px-3 py-2 text-sm hover:bg-zinc-50 dark:hover:bg-zinc-700 text-left cursor-pointer">
                                                    <span
                                                        class="font-medium text-zinc-900 dark:text-zinc-100 flex-1">{{ $p['name'] }}</span>
                                                    @if ($p['sku'] ?? null)
                                                        <span class="text-xs text-zinc-400">{{ $p['sku'] }}</span>
                                                    @endif
                                                    @if ($p['price'] ?? null)
                                                        <span
                                                            class="text-xs text-zinc-500">{{ format_currency($p['price']) }}</span>
                                                    @endif
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                {{-- Selected bundle products --}}
                                @if (!empty($form->bundle_products))
                                    <div class="space-y-1">
                                        @foreach ($form->bundle_products as $idx => $p)
                                            <div class="flex items-center justify-between px-3 py-2 bg-zinc-50 dark:bg-zinc-800/60 rounded-md border dark:border-zinc-700"
                                                wire:key="bundle-{{ $p['id'] }}">
                                                <div class="flex-1 text-sm">
                                                    <span
                                                        class="font-medium text-zinc-800 dark:text-zinc-200">{{ $p['name'] }}</span>
                                                    @if ($p['sku'] ?? null)
                                                        <span class="text-zinc-400 ms-2">{{ $p['sku'] }}</span>
                                                    @endif
                                                    @if ($p['price'] ?? null)
                                                        <span
                                                            class="text-zinc-500 ms-2">{{ format_currency($p['price']) }}</span>
                                                    @endif
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <div class="flex items-center gap-1">
                                                        <span class="text-xs text-zinc-500">Qty:</span>
                                                        <flux:input type="number" min="1"
                                                            wire:model.live="form.bundle_products.{{ $idx }}.quantity"
                                                            class="w-16 text-center" size="xs"
                                                            @keydown.enter.prevent />
                                                    </div>
                                                    <flux:button type="button" icon="x-mark" variant="ghost"
                                                        size="xs"
                                                        class="text-zinc-400 hover:text-red-500 cursor-pointer"
                                                        wire:click="removeBundleProduct({{ $idx }})" />
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    {{-- Bundle value and savings summary --}}
                                    <div class="mt-3 pt-3 border-t dark:border-zinc-700 space-y-2">
                                        <div class="flex justify-between items-center text-sm">
                                            <span class="text-zinc-500">{{ count($form->bundle_products) }}
                                                product(s)</span>
                                            <span class="text-zinc-500">
                                                Items Value: <span
                                                    class="font-medium text-zinc-700 dark:text-zinc-300">{{ format_currency($this->bundleValue) }}</span>
                                            </span>
                                        </div>
                                        @if ($this->bundleSavingsPercent)
                                            <div class="flex justify-between items-center text-sm">
                                                <span class="text-zinc-500">Bundle Price:</span>
                                                <span class="font-medium text-green-600 dark:text-green-400">
                                                    {{ format_currency($form->sale_price !== '' ? $form->sale_price : $form->price) }}
                                                    <span class="text-xs">(Save
                                                        {{ $this->bundleSavingsPercent }}%)</span>
                                                </span>
                                            </div>
                                        @elseif ($form->price !== '')
                                            <div class="flex justify-between items-center text-sm">
                                                <span class="text-zinc-500">Bundle Price:</span>
                                                <span class="font-medium text-zinc-700 dark:text-zinc-300">
                                                    {{ format_currency($form->sale_price !== '' ? $form->sale_price : $form->price) }}
                                                </span>
                                            </div>
                                        @else
                                            <p class="text-xs text-amber-600 dark:text-amber-400">
                                                <flux:icon.exclamation-triangle variant="mini"
                                                    class="size-3.5 inline" />
                                                Set a bundle price in the General tab to show savings.
                                            </p>
                                        @endif
                                    </div>
                                @else
                                    <p class="text-xs text-zinc-400">No products added yet. Search and add simple
                                        products above.</p>
                                @endif
                            </div>

                            {{-- Upsells, Cross-sells, Accessories - Hidden for grouped and bundle products --}}
                            <div x-show="$wire.form.type !== 'grouped' && $wire.form.type !== 'bundle'" x-cloak
                                class="space-y-6">
                                @foreach ([['type' => 'upsell', 'label' => 'Upsells', 'query' => 'upsellQuery', 'results' => 'upsellResults', 'list' => 'upsell_products', 'desc' => 'Premium or complementary products shown on the product page.'], ['type' => 'cross_sell', 'label' => 'Cross-sells', 'query' => 'crossSellQuery', 'results' => 'crossSellResults', 'list' => 'cross_sell_products', 'desc' => 'Products promoted on the cart page alongside this item.'], ['type' => 'accessory', 'label' => 'Accessories', 'query' => 'accessoryQuery', 'results' => 'accessoryResults', 'list' => 'accessory_products', 'desc' => 'Optional add-ons shown on the product detail page.']] as $lp)
                                    <div>
                                        <flux:heading size="sm">{{ $lp['label'] }}</flux:heading>
                                        <flux:text class="text-xs text-zinc-500 mb-2">{{ $lp['desc'] }}</flux:text>

                                        {{-- Search input --}}
                                        <div class="relative mb-2" x-data="{ open: false }"
                                            x-on:click.outside="open = false">
                                            <flux:input wire:model.live.debounce.300ms="{{ $lp['query'] }}"
                                                placeholder="Search by name or SKU..." icon="magnifying-glass"
                                                @focus="open = true" @keydown.enter.prevent />

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
                                        class="{{ $selectClass }} py-1.5! text-sm!">
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

                                {{-- Default Variant Selector --}}
                                @if (!empty($form->variations) && !empty($this->variationAttributesForSelector))
                                    <div class="flex items-center gap-3 flex-wrap" x-data="{
                                        selectedValues: @js($this->defaultVariantAttributeValues),
                                        updateDefault() {
                                            const values = Object.values(this.selectedValues).filter(v => v);
                                            $wire.setDefaultVariantByAttributes(values);
                                        }
                                    }">
                                        <div class="flex items-center gap-1.5">
                                            <flux:label class="text-sm whitespace-nowrap">Default Form Values:
                                            </flux:label>
                                            <flux:tooltip
                                                content="The variant that will be pre-selected when customers view this product">
                                                <flux:icon.information-circle variant="outline"
                                                    class="size-4 text-zinc-400 cursor-help" />
                                            </flux:tooltip>
                                        </div>

                                        @foreach ($this->variationAttributesForSelector as $attr)
                                            <flux:select x-model="selectedValues[{{ $attr['id'] }}]"
                                                @change="updateDefault()" size="sm" class="w-fit">
                                                <flux:select.option value="">— {{ $attr['name'] }} —
                                                </flux:select.option>
                                                @foreach ($attr['values'] as $val)
                                                    <flux:select.option value="{{ $val['id'] }}"
                                                        :selected="in_array($val['id'], $this->defaultVariantAttributeValues)">
                                                        {{ $val['label'] }}
                                                    </flux:select.option>
                                                @endforeach
                                            </flux:select>
                                        @endforeach
                                    </div>
                                @endif

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
                                            <flux:menu class="min-w-48 max-h-96 overflow-y-auto">
                                                <flux:menu.group heading="Status">
                                                    <flux:menu.item wire:click="activateAllVariants"
                                                        class="cursor-pointer">
                                                        Activate All
                                                    </flux:menu.item>
                                                    <flux:menu.item wire:click="deactivateAllVariants"
                                                        wire:confirm="This will hide all variants from customers. Continue?"
                                                        class="cursor-pointer">
                                                        Deactivate All
                                                    </flux:menu.item>
                                                    <flux:menu.item wire:click="setFirstActiveAsDefault"
                                                        class="cursor-pointer">
                                                        Set First Active as Default
                                                    </flux:menu.item>
                                                </flux:menu.group>

                                                <flux:menu.group heading="Pricing">
                                                    <flux:menu.item
                                                        x-on:click="$flux.modal('open-bulk-price-modal').show()"
                                                        class="cursor-pointer">
                                                        Set Price...
                                                    </flux:menu.item>
                                                    <flux:menu.item
                                                        x-on:click="$flux.modal('open-bulk-sale-price-modal').show()"
                                                        class="cursor-pointer">
                                                        Set Sale Price...
                                                    </flux:menu.item>
                                                    <flux:menu.item
                                                        x-on:click="$flux.modal('open-bulk-cost-price-modal').show()"
                                                        class="cursor-pointer">
                                                        Set Cost Price...
                                                    </flux:menu.item>
                                                    <flux:menu.item wire:click="bulkClearSalePrice"
                                                        class="cursor-pointer">
                                                        Clear Sale Prices
                                                    </flux:menu.item>
                                                    <flux:menu.item
                                                        x-on:click="$flux.modal('open-bulk-adjust-price-modal').show()"
                                                        class="cursor-pointer">
                                                        Adjust Prices by %...
                                                    </flux:menu.item>
                                                </flux:menu.group>

                                                <flux:menu.group heading="Inventory">
                                                    <flux:menu.item wire:click="enableAllVariantsStockManagement"
                                                        class="cursor-pointer">
                                                        Enable Stock Management
                                                    </flux:menu.item>
                                                    <flux:menu.item wire:click="disableAllVariantsStockManagement"
                                                        class="cursor-pointer">
                                                        Disable Stock Management
                                                    </flux:menu.item>
                                                    <flux:menu.item
                                                        x-on:click="$flux.modal('open-bulk-stock-quantity-modal').show()"
                                                        class="cursor-pointer">
                                                        Set Stock Quantity...
                                                    </flux:menu.item>
                                                    <flux:menu.item wire:click="setAllVariantsStockStatus('in_stock')"
                                                        class="cursor-pointer">
                                                        Set Status → In Stock
                                                    </flux:menu.item>
                                                    <flux:menu.item
                                                        wire:click="setAllVariantsStockStatus('out_of_stock')"
                                                        class="cursor-pointer">
                                                        Set Status → Out of Stock
                                                    </flux:menu.item>
                                                    <flux:menu.item wire:click="setAllVariantsStockStatus('backorder')"
                                                        class="cursor-pointer">
                                                        Set Status → Backorder
                                                    </flux:menu.item>
                                                    <flux:menu.item wire:click="bulkEnableBackorders"
                                                        class="cursor-pointer">
                                                        Enable Backorders
                                                    </flux:menu.item>
                                                    <flux:menu.item wire:click="bulkDisableBackorders"
                                                        class="cursor-pointer">
                                                        Disable Backorders
                                                    </flux:menu.item>
                                                </flux:menu.group>

                                                <flux:menu.group heading="Attributes">
                                                    <flux:menu.item
                                                        x-on:click="$flux.modal('open-bulk-sku-modal').show()"
                                                        class="cursor-pointer">
                                                        Generate SKUs...
                                                    </flux:menu.item>
                                                    <flux:menu.item wire:click="bulkClearSKUs"
                                                        wire:confirm="Clear all variant SKUs?" class="cursor-pointer">
                                                        Clear SKUs
                                                    </flux:menu.item>
                                                    <flux:menu.item
                                                        x-on:click="$flux.modal('open-bulk-weight-modal').show()"
                                                        class="cursor-pointer">
                                                        Set Weight...
                                                    </flux:menu.item>
                                                    <flux:menu.item
                                                        x-on:click="$flux.modal('open-bulk-dimensions-modal').show()"
                                                        class="cursor-pointer">
                                                        Set Dimensions...
                                                    </flux:menu.item>
                                                    <flux:menu.item wire:click="bulkCopyDimensionsFromParent"
                                                        class="cursor-pointer">
                                                        Copy from Parent Product
                                                    </flux:menu.item>
                                                </flux:menu.group>

                                                <flux:menu.group heading="Danger">
                                                    <flux:menu.item wire:click="clearAllVariants"
                                                        wire:confirm="Delete all variations? This cannot be undone."
                                                        variant="danger" class="cursor-pointer">
                                                        Delete All Variations
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

                                {{-- Default Variant Selector --}}
                                @if (!empty($form->variations) && !empty($this->variationAttributesForSelector))
                                    <div
                                        class="bg-zinc-50 dark:bg-zinc-800/50 rounded-lg p-4 border border-zinc-200 dark:border-zinc-700">
                                        <div class="flex items-start gap-4 flex-wrap">
                                            <div class="shrink-0">
                                                <flux:label class="text-sm font-medium">Default Variant</flux:label>
                                                <p class="text-xs text-zinc-500 mt-0.5">Select the combination that
                                                    will be pre-selected on the storefront</p>
                                            </div>
                                            <div class="flex-1 flex items-end gap-3 flex-wrap"
                                                x-data="{
                                                    selectedValues: @js($this->defaultVariantAttributeValues),
                                                    attributes: @js($this->variationAttributesForSelector),
                                                    updateDefault() {
                                                        // Collect all selected values
                                                        const values = Object.values(this.selectedValues).filter(v => v);
                                                        $wire.setDefaultVariantByAttributes(values);
                                                    }
                                                }">
                                                @foreach ($this->variationAttributesForSelector as $attrIndex => $attr)
                                                    <div class="min-w-32">
                                                        <flux:label class="text-xs mb-1">{{ $attr['name'] }}
                                                        </flux:label>
                                                        <select x-model="selectedValues[{{ $attr['id'] }}]"
                                                            @change="updateDefault()"
                                                            class="{{ $selectClass }} text-sm py-1.5">
                                                            <option value="">— Select —</option>
                                                            @foreach ($attr['values'] as $val)
                                                                <option value="{{ $val['id'] }}"
                                                                    {{ in_array($val['id'], $this->defaultVariantAttributeValues) ? 'selected' : '' }}>
                                                                    {{ $val['label'] }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                @endforeach

                                                @php
                                                    $currentDefault = collect($form->variations)->firstWhere(
                                                        'is_default',
                                                        true,
                                                    );
                                                @endphp
                                                @if ($currentDefault)
                                                    <div
                                                        class="flex items-center gap-2 text-xs text-green-600 dark:text-green-400 pb-1">
                                                        <flux:icon.check-circle class="size-4" />
                                                        <span>{{ $currentDefault['name'] ?: 'Unnamed' }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif

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
                        class="text-red-500 cursor-pointer" wire:click="removeProductImage">
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
                <flux:button size="xs" variant="ghost" class="cursor-pointer"
                    @click="expanded = !expanded">
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


{{-- Bulk Action Modals (Global) --}}
<div x-data="{
    bulkPrice: '',
    bulkCostPrice: '',
    bulkSalePrice: '',
    bulkAdjustPercent: '',
    bulkSkuPrefix: '',
    bulkStockQuantity: '',
    bulkWeight: '',
    bulkLength: '',
    bulkWidth: '',
    bulkHeight: ''
}">
    {{-- Bulk Set Price Modal --}}
    <flux:modal name="open-bulk-price-modal" class="space-y-4">
        <flux:heading size="lg">Set Price for All Variants</flux:heading>
        <flux:text>This will set the same price for all variants.</flux:text>

        <flux:input type="number" step="0.01" min="0" x-model="bulkPrice"
            label="Price ({{ get_currency_symbol() }})" placeholder="0.00"
            @keydown.enter.prevent="$wire.bulkSetPrice(parseFloat(bulkPrice)).then(() => { $flux.modal('open-bulk-price-modal').close(); bulkPrice = ''; })" />

        <div class="flex gap-2 justify-end">
            <flux:modal.close>
                <flux:button variant="ghost">Cancel</flux:button>
            </flux:modal.close>
            <flux:button variant="primary" type="button"
                @click="$wire.bulkSetPrice(parseFloat(bulkPrice)).then(() => { $flux.modal('open-bulk-price-modal').close(); bulkPrice = ''; })">
                Apply
            </flux:button>
        </div>
    </flux:modal>

    {{-- Bulk Set Sale Price Modal --}}
    <flux:modal name="open-bulk-sale-price-modal" class="space-y-4">
        <flux:heading size="lg">Set Sale Price for All Variants</flux:heading>
        <flux:text>This will set the same sale price for all variants.</flux:text>

        <flux:input type="number" step="0.01" min="0" x-model="bulkSalePrice"
            label="Sale Price ({{ get_currency_symbol() }})" placeholder="0.00"
            @keydown.enter.prevent="$wire.bulkSetSalePrice(parseFloat(bulkSalePrice)).then(() => { $flux.modal('open-bulk-sale-price-modal').close(); bulkSalePrice = ''; })" />

        <div class="flex gap-2 justify-end">
            <flux:modal.close>
                <flux:button variant="ghost">Cancel</flux:button>
            </flux:modal.close>
            <flux:button variant="primary" type="button"
                @click="$wire.bulkSetSalePrice(parseFloat(bulkSalePrice)).then(() => { $flux.modal('open-bulk-sale-price-modal').close(); bulkSalePrice = ''; })">
                Apply
            </flux:button>
        </div>
    </flux:modal>

    {{-- Bulk Adjust Price by Percent Modal --}}
    <flux:modal name="open-bulk-adjust-price-modal" class="space-y-4">
        <flux:heading size="lg">Adjust Prices by Percentage</flux:heading>
        <flux:text>Enter a positive number to increase prices or negative to decrease.</flux:text>

        <flux:input type="number" step="0.1" x-model="bulkAdjustPercent" label="Percentage (%)"
            placeholder="e.g., 10 or -15"
            @keydown.enter.prevent="$wire.bulkAdjustPriceByPercent(parseFloat(bulkAdjustPercent)).then(() => { $flux.modal('open-bulk-adjust-price-modal').close(); bulkAdjustPercent = ''; })" />

        <div class="flex gap-2 justify-end">
            <flux:modal.close>
                <flux:button variant="ghost">Cancel</flux:button>
            </flux:modal.close>
            <flux:button variant="primary" type="button"
                @click="$wire.bulkAdjustPriceByPercent(parseFloat(bulkAdjustPercent)).then(() => { $flux.modal('open-bulk-adjust-price-modal').close(); bulkAdjustPercent = ''; })">
                Apply
            </flux:button>
        </div>
    </flux:modal>

    {{-- Bulk Generate SKUs Modal --}}
    <flux:modal name="open-bulk-sku-modal" class="space-y-4">
        <flux:heading size="lg">Generate SKUs for Variants</flux:heading>
        <flux:text>SKUs will be generated for variants that don't have one. Format: PREFIX-SUFFIX</flux:text>

        <flux:input type="text" x-model="bulkSkuPrefix" label="SKU Prefix" placeholder="e.g., PROD"
            @keydown.enter.prevent="$wire.bulkGenerateSKUs(bulkSkuPrefix).then(() => { $flux.modal('open-bulk-sku-modal').close(); bulkSkuPrefix = ''; })" />

        <div class="flex gap-2 justify-end">
            <flux:modal.close>
                <flux:button variant="ghost">Cancel</flux:button>
            </flux:modal.close>
            <flux:button variant="primary" type="button"
                @click="$wire.bulkGenerateSKUs(bulkSkuPrefix).then(() => { $flux.modal('open-bulk-sku-modal').close(); bulkSkuPrefix = ''; })">
                Generate
            </flux:button>
        </div>
    </flux:modal>

    {{-- Bulk Set Weight Modal --}}
    <flux:modal name="open-bulk-weight-modal" class="space-y-4">
        <flux:heading size="lg">Set Weight for All Variants</flux:heading>
        <flux:text>This will set the same weight for all variants.</flux:text>

        <flux:input type="number" step="0.001" min="0" x-model="bulkWeight" label="Weight (kg)"
            placeholder="0.000"
            @keydown.enter.prevent="$wire.bulkSetWeight(parseFloat(bulkWeight)).then(() => { $flux.modal('open-bulk-weight-modal').close(); bulkWeight = ''; })" />

        <div class="flex gap-2 justify-end">
            <flux:modal.close>
                <flux:button variant="ghost">Cancel</flux:button>
            </flux:modal.close>
            <flux:button variant="primary" type="button"
                @click="$wire.bulkSetWeight(parseFloat(bulkWeight)).then(() => { $flux.modal('open-bulk-weight-modal').close(); bulkWeight = ''; })">
                Apply
            </flux:button>
        </div>
    </flux:modal>

    {{-- Bulk Set Dimensions Modal --}}
    <flux:modal name="open-bulk-dimensions-modal" class="space-y-4" @keydown.enter.prevent>
        <flux:heading size="lg">Set Dimensions for All Variants</flux:heading>
        <flux:text>This will set the same dimensions for all variants.</flux:text>

        <div class="grid grid-cols-3 gap-3">
            <flux:input type="number" step="0.01" min="0" x-model="bulkLength" label="Length (cm)"
                placeholder="0.00" />
            <flux:input type="number" step="0.01" min="0" x-model="bulkWidth" label="Width (cm)"
                placeholder="0.00" />
            <flux:input type="number" step="0.01" min="0" x-model="bulkHeight" label="Height (cm)"
                placeholder="0.00" />
        </div>

        <div class="flex gap-2 justify-end">
            <flux:modal.close>
                <flux:button variant="ghost">Cancel</flux:button>
            </flux:modal.close>
            <flux:button variant="primary" type="button"
                @click="$wire.bulkSetDimensions(parseFloat(bulkLength), parseFloat(bulkWidth), parseFloat(bulkHeight)).then(() => { $flux.modal('open-bulk-dimensions-modal').close(); bulkLength = ''; bulkWidth = ''; bulkHeight = ''; })">
                Apply
            </flux:button>
        </div>
    </flux:modal>

    {{-- Bulk Set Cost Price Modal --}}
    <flux:modal name="open-bulk-cost-price-modal" class="space-y-4">
        <flux:heading size="lg">Set Cost Price for All Variants</flux:heading>
        <flux:text>This will set the same cost price for all variants.</flux:text>

        <flux:input type="number" step="0.01" min="0" x-model="bulkCostPrice"
            label="Cost Price ({{ get_currency_symbol() }})" placeholder="0.00"
            @keydown.enter.prevent="$wire.bulkSetCostPrice(parseFloat(bulkCostPrice)).then(() => { $flux.modal('open-bulk-cost-price-modal').close(); bulkCostPrice = ''; })" />

        <div class="flex gap-2 justify-end">
            <flux:modal.close>
                <flux:button variant="ghost">Cancel</flux:button>
            </flux:modal.close>
            <flux:button variant="primary" type="button"
                @click="$wire.bulkSetCostPrice(parseFloat(bulkCostPrice)).then(() => { $flux.modal('open-bulk-cost-price-modal').close(); bulkCostPrice = ''; })">
                Apply
            </flux:button>
        </div>
    </flux:modal>

    {{-- Bulk Set Stock Quantity Modal --}}
    <flux:modal name="open-bulk-stock-quantity-modal" class="space-y-4">
        <flux:heading size="lg">Set Stock Quantity for All Variants</flux:heading>
        <flux:text>This will set the same stock quantity for all variants with stock management enabled.</flux:text>

        <flux:input type="number" min="0" x-model="bulkStockQuantity" label="Stock Quantity"
            placeholder="0"
            @keydown.enter.prevent="$wire.bulkSetStockQuantity(parseInt(bulkStockQuantity)).then(() => { $flux.modal('open-bulk-stock-quantity-modal').close(); bulkStockQuantity = ''; })" />

        <div class="flex gap-2 justify-end">
            <flux:modal.close>
                <flux:button variant="ghost">Cancel</flux:button>
            </flux:modal.close>
            <flux:button variant="primary" type="button"
                @click="$wire.bulkSetStockQuantity(parseInt(bulkStockQuantity)).then(() => { $flux.modal('open-bulk-stock-quantity-modal').close(); bulkStockQuantity = ''; })">
                Apply
            </flux:button>
        </div>
    </flux:modal>
</div>
