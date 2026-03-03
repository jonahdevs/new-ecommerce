<form wire:submit="save" class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-5" id="product-form">
    <div class="col-span-3 space-y-5">

        {{-- Basic Information --}}
        @include('pages.admin.catalog.products.partials._basic-information')

        {{-- Product Data --}}
        <flux:card class="p-0" x-data="{ expanded: true }">
            <div class="border-b dark:border-zinc-600 px-3 py-2 flex items-center justify-between"
                :class="{ 'border-b': expanded }">
                <div class="flex items-center gap-3">
                    <flux:heading>Product Data</flux:heading>
                    <flux:select size="sm" class="w-fit" wire:model.live="form.type">
                        <flux:select.option value="simple">Simple</flux:select.option>
                        <flux:select.option value="variable">Variable Product</flux:select.option>
                    </flux:select>
                </div>
                <flux:button icon="chevron-down" size="xs" variant="ghost"
                    class="cursor-pointer transition-transform duration-300" x-bind:class="{ 'rotate-180': expanded }"
                    @click="expanded = !expanded" />
            </div>

            <div x-show="expanded" x-cloak x-collapse class="grid grid-cols-4">

                {{-- Tab Sidebar --}}
                <div
                    class="col-span-1 bg-zinc-100 dark:bg-zinc-900/90 border-r dark:border-zinc-600 flex flex-col divide-y dark:divide-zinc-600 overflow-hidden rounded-bl-xl">

                    <flux:button class="w-full rounded-none! cursor-pointer justify-start!" variant="ghost"
                        x-bind:class="{ 'bg-zinc-200! dark:bg-zinc-800!': $wire.activeTab === 'general' }"
                        icon="truck" icon-variant="outline" @click="$wire.activeTab = 'general'">
                        General
                        @if ($this->hasGeneralErrors())
                            <x-slot name="iconTrailing">
                                <flux:icon.exclamation-circle class="w-4 h-4 text-red-500" variant="outline" />
                            </x-slot>
                        @endif
                    </flux:button>

                    <flux:button class="w-full rounded-none! cursor-pointer justify-start!" variant="ghost"
                        x-bind:class="{ 'bg-zinc-200! dark:bg-zinc-800!': $wire.activeTab === 'inventory' }"
                        icon="archive-box" icon-variant="outline" @click="$wire.activeTab = 'inventory'">
                        Inventory
                        @if ($this->hasInventoryErrors())
                            <x-slot name="iconTrailing">
                                <flux:icon.exclamation-circle class="w-4 h-4 text-red-500" variant="outline" />
                            </x-slot>
                        @endif
                    </flux:button>

                    <flux:button class="w-full rounded-none! cursor-pointer justify-start!" variant="ghost"
                        x-bind:class="{ 'bg-zinc-200! dark:bg-zinc-800!': $wire.activeTab === 'shipping' }"
                        icon="truck" icon-variant="outline" @click="$wire.activeTab = 'shipping'">
                        Shipping
                        @if ($this->hasShippingErrors())
                            <x-slot name="iconTrailing">
                                <flux:icon.exclamation-circle class="w-4 h-4 text-red-500" variant="outline" />
                            </x-slot>
                        @endif
                    </flux:button>

                    <flux:button class="w-full rounded-none! cursor-pointer justify-start!" variant="ghost"
                        x-bind:class="{ 'bg-zinc-200! dark:bg-zinc-800!': $wire.activeTab === 'linked-products' }"
                        icon="link" icon-variant="outline" @click="$wire.activeTab = 'linked-products'">
                        Linked Products
                        @if ($this->hasLinkedProductsErrors())
                            <x-slot name="iconTrailing">
                                <flux:icon.exclamation-circle class="w-4 h-4 text-red-500" variant="outline" />
                            </x-slot>
                        @endif
                    </flux:button>

                    <flux:button class="w-full rounded-none! cursor-pointer justify-start!" variant="ghost"
                        x-bind:class="{ 'bg-zinc-200! dark:bg-zinc-800!': $wire.activeTab === 'attributes' }"
                        icon="tag" icon-variant="outline" @click="$wire.activeTab = 'attributes'">
                        Attributes
                        @if ($this->hasAttributesErrors())
                            <x-slot name="iconTrailing">
                                <flux:icon.exclamation-circle class="w-4 h-4 text-red-500" variant="outline" />
                            </x-slot>
                        @endif
                    </flux:button>

                    <flux:button wire:cloak wire:show="form.type === 'variable'"
                        class="w-full rounded-none! cursor-pointer justify-start!" variant="ghost"
                        x-bind:class="{ 'bg-zinc-200! dark:bg-zinc-800!': $wire.activeTab === 'variations' }"
                        icon="squares-2x2" icon-variant="outline" @click="$wire.activeTab = 'variations'">
                        Variations
                        @if ($this->hasVariationsErrors())
                            <x-slot name="iconTrailing">
                                <flux:icon.exclamation-circle class="w-4 h-4 text-red-500" variant="outline" />
                            </x-slot>
                        @endif
                    </flux:button>

                    <flux:button class="w-full rounded-none! cursor-pointer justify-start!" variant="ghost"
                        x-bind:class="{ 'bg-zinc-200! dark:bg-zinc-800!': $wire.activeTab === 'advanced' }"
                        icon="cog" icon-variant="outline" @click="$wire.activeTab = 'advanced'">
                        Advanced
                        @if ($this->hasAdvancedErrors())
                            <x-slot name="iconTrailing">
                                <flux:icon.exclamation-circle class="w-4 h-4 text-red-500" variant="outline" />
                            </x-slot>
                        @endif
                    </flux:button>

                </div>

                {{-- Tab Content --}}
                <div class="col-span-3 p-5">
                    @include('pages.admin.catalog.products.partials._general')
                    @include('pages.admin.catalog.products.partials._inventory')
                    @include('pages.admin.catalog.products.partials._shipping')
                    @include('pages.admin.catalog.products.partials._linked-products')

                    {{-- Attributes --}}
                    <div wire:cloak wire:show="activeTab == 'attributes'">
                        <livewire:pages::admin.catalog.products.partials._attributes-manager :product="$product ?? null" />
                    </div>

                    {{-- Variations --}}
                    <div wire:cloak wire:show="activeTab == 'variations'">
                        <livewire:pages::admin.catalog.products.partials._variations-manager :product="$product ?? null" />
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

    {{-- Sidebar --}}
    <div class="col-span-1 space-y-5">
        @include('pages.admin.catalog.products.partials._sidebar')
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
