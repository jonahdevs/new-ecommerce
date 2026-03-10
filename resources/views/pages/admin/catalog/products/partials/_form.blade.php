<form wire:submit="save" class="mt-6 space-y-5 lg:grid lg:grid-cols-12 lg:gap-5 lg:space-y-0" id="product-form">
    {{-- Sidebar --}}
    <div class="lg:col-span-4 xl:col-span-3 lg:order-last space-y-5 @container">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 gap-5">
            @include('pages.admin.catalog.products.partials._sidebar')
        </div>
    </div>

    <div class="lg:col-span-8 xl:col-span-9 space-y-5">

        {{-- Basic Information --}}
        @include('pages.admin.catalog.products.partials._basic-information')

        {{-- Product Data --}}
        <flux:card class="p-0" x-data="{ expanded: true }">
            <div class="border-b dark:border-zinc-600 px-3 py-2" :class="{ 'border-b': expanded }">

                {{-- Row 1 — Title + Chevron --}}
                <div class="flex items-center justify-between">
                    <flux:heading>Product Data</flux:heading>
                    <flux:button icon="chevron-down" size="xs" variant="ghost"
                        class="cursor-pointer transition-transform duration-300"
                        x-bind:class="{ 'rotate-180': expanded }" @click="expanded = !expanded" />
                </div>

                {{-- Row 2 — Type + Checkboxes --}}
                <div class="flex flex-wrap items-center gap-3 mt-2">
                    <flux:select size="sm" class="w-fit" wire:model.live="form.type">
                        @foreach (App\Enums\ProductType::cases() as $type)
                            <flux:select.option value="{{ $type->value }}">
                                {{ $type->label() }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    {{-- Virtual & Downloadable --}}
                    <div wire:cloak wire:show="form.type !== 'grouped'" class="flex items-center gap-4">
                        <flux:field variant="inline">
                            <flux:checkbox wire:model.live="form.is_virtual" />
                            <flux:label size="sm" class="text-xs!">Virtual</flux:label>
                            <flux:error name="form.is_virtual" />
                        </flux:field>

                        <flux:field variant="inline">
                            <flux:checkbox wire:model.live="form.is_downloadable" />
                            <flux:label size="sm" class="text-xs!">Downloadable</flux:label>
                            <flux:error name="form.is_downloadable" />
                        </flux:field>
                    </div>
                </div>

            </div>

            <div x-show="expanded" x-cloak x-collapse class="">

                {{-- ================================================ --}}
                {{-- MOBILE — Horizontal scrollable pills (< md)      --}}
                {{-- ================================================ --}}
                <div
                    class="md:hidden border-b dark:border-zinc-700 overflow-x-auto [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
                    <div class="flex items-center gap-1 p-2 min-w-max">

                        {{-- General --}}
                        <button wire:cloak wire:show="form.type !== 'grouped'" type="button"
                            @click="$wire.activeTab = 'general'"
                            :class="$wire.activeTab === 'general' ?
                                'bg-white dark:bg-zinc-800 text-sheffield-blue shadow-sm border border-zinc-200 dark:border-zinc-600' :
                                'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800'"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm font-medium whitespace-nowrap transition-all">
                            <flux:icon.banknotes class="size-3.5" variant="outline" />
                            General
                            @if ($this->hasGeneralErrors())
                                <span class="size-1.5 rounded-full bg-red-500"></span>
                            @endif
                        </button>

                        {{-- Inventory --}}
                        <button type="button" @click="$wire.activeTab = 'inventory'"
                            :class="$wire.activeTab === 'inventory' ?
                                'bg-white dark:bg-zinc-800 text-sheffield-blue shadow-sm border border-zinc-200 dark:border-zinc-600' :
                                'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800'"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm font-medium whitespace-nowrap transition-all">
                            <flux:icon.archive-box class="size-3.5" variant="outline" />
                            Inventory
                            @if ($this->hasInventoryErrors())
                                <span class="size-1.5 rounded-full bg-red-500"></span>
                            @endif
                        </button>

                        {{-- Shipping --}}
                        <button wire:cloak wire:show="form.type !== 'grouped' && !form.is_virtual" type="button"
                            @click="$wire.activeTab = 'shipping'"
                            :class="$wire.activeTab === 'shipping' ?
                                'bg-white dark:bg-zinc-800 text-sheffield-blue shadow-sm border border-zinc-200 dark:border-zinc-600' :
                                'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800'"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm font-medium whitespace-nowrap transition-all">
                            <flux:icon.truck class="size-3.5" variant="outline" />
                            Shipping
                            @if ($this->hasShippingErrors())
                                <span class="size-1.5 rounded-full bg-red-500"></span>
                            @endif
                        </button>

                        {{-- Linked Products --}}
                        <button type="button" @click="$wire.activeTab = 'linked-products'"
                            :class="$wire.activeTab === 'linked-products' ?
                                'bg-white dark:bg-zinc-800 text-sheffield-blue shadow-sm border border-zinc-200 dark:border-zinc-600' :
                                'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800'"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm font-medium whitespace-nowrap transition-all">
                            <flux:icon.link class="size-3.5" variant="outline" />
                            Linked
                            @if ($this->hasLinkedProductsErrors())
                                <span class="size-1.5 rounded-full bg-red-500"></span>
                            @endif
                        </button>

                        {{-- Attributes --}}
                        <button type="button" @click="$wire.activeTab = 'attributes'"
                            :class="$wire.activeTab === 'attributes' ?
                                'bg-white dark:bg-zinc-800 text-sheffield-blue shadow-sm border border-zinc-200 dark:border-zinc-600' :
                                'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800'"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm font-medium whitespace-nowrap transition-all">
                            <flux:icon.tag class="size-3.5" variant="outline" />
                            Attributes
                            @if ($this->hasAttributesErrors())
                                <span class="size-1.5 rounded-full bg-red-500"></span>
                            @endif
                        </button>

                        {{-- Variations --}}
                        <button wire:cloak wire:show="form.type === 'variable'" type="button"
                            @click="$wire.activeTab = 'variations'"
                            :class="$wire.activeTab === 'variations' ?
                                'bg-white dark:bg-zinc-800 text-sheffield-blue shadow-sm border border-zinc-200 dark:border-zinc-600' :
                                'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800'"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm font-medium whitespace-nowrap transition-all">
                            <flux:icon.squares-2x2 class="size-3.5" variant="outline" />
                            Variations
                            @if ($this->hasVariationsErrors())
                                <span class="size-1.5 rounded-full bg-red-500"></span>
                            @endif
                        </button>

                        {{-- Advanced --}}
                        <button type="button" @click="$wire.activeTab = 'advanced'"
                            :class="$wire.activeTab === 'advanced' ?
                                'bg-white dark:bg-zinc-800 text-sheffield-blue shadow-sm border border-zinc-200 dark:border-zinc-600' :
                                'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800'"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm font-medium whitespace-nowrap transition-all">
                            <flux:icon.cog class="size-3.5" variant="outline" />
                            Advanced
                            @if ($this->hasAdvancedErrors())
                                <span class="size-1.5 rounded-full bg-red-500"></span>
                            @endif
                        </button>

                    </div>
                </div>

                {{-- ================================================ --}}
                {{-- TABLET + DESKTOP — Sidebar layout (md+)          --}}
                {{-- ================================================ --}}
                <div class="hidden md:grid md:grid-cols-12">

                    {{-- ── Sidebar ── --}}
                    <div
                        class="md:col-span-2 lg:col-span-3 bg-zinc-100 dark:bg-zinc-900/90 border-r dark:border-zinc-600 flex flex-col divide-y dark:divide-zinc-600 overflow-hidden rounded-bl-xl">

                        {{-- General --}}
                        <button wire:cloak wire:show="form.type !== 'grouped'" type="button"
                            @click="$wire.activeTab = 'general'"
                            :class="$wire.activeTab === 'general' ?
                                'bg-zinc-200 dark:bg-zinc-800 text-sheffield-blue' :
                                'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200/60 dark:hover:bg-zinc-800/60'"
                            class="group flex items-center lg:justify-start justify-center gap-2.5 px-3 py-3 text-sm font-medium transition-all relative">

                            <flux:icon.banknotes class="size-4 shrink-0" variant="outline" />

                            {{-- Label — visible on lg, hidden on md --}}
                            <span class="hidden lg:block">General</span>

                            {{-- Tooltip — visible on md only --}}
                            <div
                                class="lg:hidden absolute left-full ml-2 px-2 py-1 bg-zinc-800 text-white text-xs rounded-md whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50">
                                General
                            </div>

                            @if ($this->hasGeneralErrors())
                                <span class="size-1.5 rounded-full bg-red-500 lg:ml-auto shrink-0"></span>
                            @endif
                        </button>

                        {{-- Inventory --}}
                        <button type="button" @click="$wire.activeTab = 'inventory'"
                            :class="$wire.activeTab === 'inventory' ?
                                'bg-zinc-200 dark:bg-zinc-800 text-sheffield-blue' :
                                'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200/60 dark:hover:bg-zinc-800/60'"
                            class="group flex items-center lg:justify-start justify-center gap-2.5 px-3 py-3 text-sm font-medium transition-all relative">
                            <flux:icon.archive-box class="size-4 shrink-0" variant="outline" />
                            <span class="hidden lg:block">Inventory</span>
                            <div
                                class="lg:hidden absolute left-full ml-2 px-2 py-1 bg-zinc-800 text-white text-xs rounded-md whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50">
                                Inventory
                            </div>
                            @if ($this->hasInventoryErrors())
                                <span class="size-1.5 rounded-full bg-red-500 lg:ml-auto shrink-0"></span>
                            @endif
                        </button>

                        {{-- Shipping --}}
                        <button wire:cloak wire:show="form.type !== 'grouped' && !form.is_virtual" type="button"
                            @click="$wire.activeTab = 'shipping'"
                            :class="$wire.activeTab === 'shipping' ?
                                'bg-zinc-200 dark:bg-zinc-800 text-sheffield-blue' :
                                'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200/60 dark:hover:bg-zinc-800/60'"
                            class="group flex items-center lg:justify-start justify-center gap-2.5 px-3 py-3 text-sm font-medium transition-all relative">
                            <flux:icon.truck class="size-4 shrink-0" variant="outline" />
                            <span class="hidden lg:block">Shipping</span>
                            <div
                                class="lg:hidden absolute left-full ml-2 px-2 py-1 bg-zinc-800 text-white text-xs rounded-md whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50">
                                Shipping
                            </div>
                            @if ($this->hasShippingErrors())
                                <span class="size-1.5 rounded-full bg-red-500 lg:ml-auto shrink-0"></span>
                            @endif
                        </button>

                        {{-- Linked Products --}}
                        <button type="button" @click="$wire.activeTab = 'linked-products'"
                            :class="$wire.activeTab === 'linked-products' ?
                                'bg-zinc-200 dark:bg-zinc-800 text-sheffield-blue' :
                                'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200/60 dark:hover:bg-zinc-800/60'"
                            class="group flex items-center lg:justify-start justify-center gap-2.5 px-3 py-3 text-sm font-medium transition-all relative">
                            <flux:icon.link class="size-4 shrink-0" variant="outline" />
                            <span class="hidden lg:block">Linked Products</span>
                            <div
                                class="lg:hidden absolute left-full ml-2 px-2 py-1 bg-zinc-800 text-white text-xs rounded-md whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50">
                                Linked Products
                            </div>
                            @if ($this->hasLinkedProductsErrors())
                                <span class="size-1.5 rounded-full bg-red-500 lg:ml-auto shrink-0"></span>
                            @endif
                        </button>

                        {{-- Attributes --}}
                        <button type="button" @click="$wire.activeTab = 'attributes'"
                            :class="$wire.activeTab === 'attributes' ?
                                'bg-zinc-200 dark:bg-zinc-800 text-sheffield-blue' :
                                'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200/60 dark:hover:bg-zinc-800/60'"
                            class="group flex items-center lg:justify-start justify-center gap-2.5 px-3 py-3 text-sm font-medium transition-all relative">
                            <flux:icon.tag class="size-4 shrink-0" variant="outline" />
                            <span class="hidden lg:block">Attributes</span>
                            <div
                                class="lg:hidden absolute left-full ml-2 px-2 py-1 bg-zinc-800 text-white text-xs rounded-md whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50">
                                Attributes
                            </div>
                            @if ($this->hasAttributesErrors())
                                <span class="size-1.5 rounded-full bg-red-500 lg:ml-auto shrink-0"></span>
                            @endif
                        </button>

                        {{-- Variations --}}
                        <button wire:cloak wire:show="form.type === 'variable'" type="button"
                            @click="$wire.activeTab = 'variations'"
                            :class="$wire.activeTab === 'variations' ?
                                'bg-zinc-200 dark:bg-zinc-800 text-sheffield-blue' :
                                'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200/60 dark:hover:bg-zinc-800/60'"
                            class="group flex items-center lg:justify-start justify-center gap-2.5 px-3 py-3 text-sm font-medium transition-all relative">
                            <flux:icon.squares-2x2 class="size-4 shrink-0" variant="outline" />
                            <span class="hidden lg:block">Variations</span>
                            <div
                                class="lg:hidden absolute left-full ml-2 px-2 py-1 bg-zinc-800 text-white text-xs rounded-md whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50">
                                Variations
                            </div>
                            @if ($this->hasVariationsErrors())
                                <span class="size-1.5 rounded-full bg-red-500 lg:ml-auto shrink-0"></span>
                            @endif
                        </button>

                        {{-- Advanced --}}
                        <button type="button" @click="$wire.activeTab = 'advanced'"
                            :class="$wire.activeTab === 'advanced' ?
                                'bg-zinc-200 dark:bg-zinc-800 text-sheffield-blue' :
                                'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200/60 dark:hover:bg-zinc-800/60'"
                            class="group flex items-center lg:justify-start justify-center gap-2.5 px-3 py-3 text-sm font-medium transition-all relative">
                            <flux:icon.cog class="size-4 shrink-0" variant="outline" />
                            <span class="hidden lg:block">Advanced</span>
                            <div
                                class="lg:hidden absolute left-full ml-2 px-2 py-1 bg-zinc-800 text-white text-xs rounded-md whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50">
                                Advanced
                            </div>
                            @if ($this->hasAdvancedErrors())
                                <span class="size-1.5 rounded-full bg-red-500 lg:ml-auto shrink-0"></span>
                            @endif
                        </button>

                    </div>

                    {{-- ── Tab Content ── --}}
                    <div class="md:col-span-10 lg:col-span-9 p-5">
                        @include('pages.admin.catalog.products.partials._general')
                        @include('pages.admin.catalog.products.partials._inventory')
                        @include('pages.admin.catalog.products.partials._shipping')
                        @include('pages.admin.catalog.products.partials._linked-products')

                        <div wire:cloak wire:show="activeTab == 'downloads'">
                            @include('pages.admin.catalog.products.partials._downloads')
                        </div>

                        <div wire:cloak wire:show="activeTab == 'attributes'">
                            @include('pages.admin.catalog.products.partials._attributes')
                        </div>

                        <div wire:cloak wire:show="activeTab == 'variations'">
                            @include('pages.admin.catalog.products.partials._variations')
                        </div>

                        @include('pages.admin.catalog.products.partials._advanced')
                    </div>

                </div>

                {{-- ── Mobile Tab Content ── --}}
                <div class="md:hidden p-4">
                    @include('pages.admin.catalog.products.partials._general')
                    @include('pages.admin.catalog.products.partials._inventory')
                    @include('pages.admin.catalog.products.partials._shipping')
                    @include('pages.admin.catalog.products.partials._linked-products')

                    <div wire:cloak wire:show="activeTab == 'downloads'">
                        @include('pages.admin.catalog.products.partials._downloads')
                    </div>

                    <div wire:cloak wire:show="activeTab == 'attributes'">
                        @include('pages.admin.catalog.products.partials._attributes')
                    </div>

                    <div wire:cloak wire:show="activeTab == 'variations'">
                        @include('pages.admin.catalog.products.partials._variations')
                    </div>

                    @include('pages.admin.catalog.products.partials._advanced')
                </div>
            </div>

        </flux:card>

        {{-- Product Description --}}
        @include('pages.admin.catalog.products.partials._product-description')

        {{-- Product SEO --}}
        @include('pages.admin.catalog.products.partials._seo')
    </div>



    {{-- Type Change Modal --}}
    <flux:modal wire:model="showTypeChangeModal" class="max-w-md space-y-5">
        <div>
            <flux:heading size="lg">Change Product Type?</flux:heading>
            <flux:subheading class="mt-1">
                This product has active variations. Switching to Simple will deactivate all of them.
                Your data will be preserved and can be restored by switching back to Variable.
            </flux:subheading>
        </div>

        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm text-amber-800">
            <div class="flex items-start gap-2">
                <flux:icon.exclamation-triangle class="size-5 shrink-0 mt-0.5 text-amber-500" />
                <div>
                    <p class="font-semibold">What will happen:</p>
                    <ul class="mt-1 space-y-1 list-disc list-inside">
                        <li>All variations will be <strong>deactivated</strong> (not deleted)</li>
                        <li>Product will use <strong>base price & stock</strong></li>
                        <li>Switch back to Variable anytime to <strong>restore variations</strong></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="flex gap-3 justify-end">
            <flux:button wire:click="cancelTypeChange" variant="ghost">Keep as Variable</flux:button>
            <flux:button wire:click="confirmTypeChange" variant="primary">Yes, Switch to Simple</flux:button>
        </div>
    </flux:modal>

</form>
