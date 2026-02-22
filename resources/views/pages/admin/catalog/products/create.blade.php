<?php

use Livewire\Component;
use Livewire\Attributes\{Title, Computed};
use Livewire\WithFileUploads;
use App\Livewire\Forms\Admin\ProductForm;
use App\Models\{Product, Brand, Category, Tag};
use Illuminate\Validation\ValidationException;

new #[Title('Create Product')] class extends Component {
    use WithFileUploads;
    public ProductForm $form;
    public string $activeTab = 'general';
    public bool $addNewCategory = false;
    public bool $addNewBrand = false;
    public array $selectedModalTags = [];

    #[Computed]
    public function products()
    {
        return Product::active()->orderBy('name')->get();
    }

    #[Computed]
    public function brands()
    {
        return Brand::active()->ordered()->get();
    }

    #[Computed]
    public function categories()
    {
        $categories = Category::active()->ordered()->with('children')->whereNull('parent_id')->get();

        return $this->flattenCategories($categories);
    }

    #[Computed]
    public function allCategories()
    {
        return Category::active()->orderBy('name')->get();
    }

    private function flattenCategories($categories, $depth = 0)
    {
        $result = [];

        foreach ($categories as $category) {
            $result[] = [
                'id' => $category->id,
                'name' => $category->name,
                'depth' => $depth,
            ];

            if ($category->children->isNotEmpty()) {
                $result = array_merge($result, $this->flattenCategories($category->children, $depth + 1));
            }
        }

        return $result;
    }

    #[Computed]
    public function selectedTags()
    {
        return $this->form->getSelectedTags();
    }

    #[Computed]
    public function mostUsedTags()
    {
        return Tag::active()->withCount('products')->orderByDesc('products_count')->limit(20)->get();
    }

    public function addTags()
    {
        $this->form->addTags();
    }

    public function removeTag($tagId)
    {
        $this->form->removeTag($tagId);
    }

    public function openTagModal()
    {
        $this->showTagModal = true;
        $this->selectedModalTags = [];
    }

    public function closeTagModal()
    {
        $this->showTagModal = false;
        $this->selectedModalTags = [];
    }

    public function hasGeneralErrors(): bool
    {
        return $this->getErrorBag()->hasAny(['form.price', 'form.sale_price']);
    }

    public function hasInventoryErrors(): bool
    {
        return $this->getErrorBag()->hasAny(['form.sku', 'form.manage_stock', 'form.stock_quantity', 'form.allow_backorder', 'form.low_stock_threshold', 'form.stock_status', 'form.sold_individually']);
    }

    public function hasShippingErrors(): bool
    {
        return $this->getErrorBag()->hasAny(['form.weight', 'form.length', 'form.width', 'form.height']);
    }

    public function hasLinkedProductsErrors(): bool
    {
        return $this->getErrorBag()->hasAny(['form.selectedUpsells', 'form.selectedCrossSells']);
    }

    public function hasAttributesErrors(): bool
    {
        return $this->getErrorBag()->hasAny([
            // Add your attribute-related error keys here
        ]);
    }

    public function hasVariationsErrors(): bool
    {
        return $this->getErrorBag()->hasAny([
            // Add your variation-related error keys here
        ]);
    }

    public function hasAdvancedErrors(): bool
    {
        return $this->getErrorBag()->hasAny([
            // Add your advanced-related error keys here
        ]);
    }

    public function save()
    {
        try {
            $product = $this->form->store();
            $this->dispatch('notify', variant: 'success', message: 'Product created successfully!');
            return redirect()->route('admin.products.index');
        } catch (ValidationException $e) {
            $this->dispatch('notify', variant: 'warning', message: 'Please correct the highlighted fields and try again.');
            throw $e;
        } catch (\Throwable $th) {
            $this->dispatch('notify', variant: 'danger', message: $th->getMessage());
            \Log::error('Product save failed', [
                'exception' => $th,
                'component' => static::class,
            ]);
        }
    }
}; ?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item href="#" icon="home" icon-variant="outline"></flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('admin.products.index')" wire:navigate>Products</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Create</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between">
        <flux:heading size="xl">Create New Product</flux:heading>

        <flux:button variant="primary" type="submit" form="product-form">Create</flux:button>
    </div>

    <form wire:submit="save" class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-5" id="product-form">
        <div class="col-span-3 space-y-5">
            {{-- Basic Information Section --}}
            @include('pages.admin.catalog.products.partials._basic-information')

            {{-- Product Data --}}
            <flux:card class="p-0">
                <div class="border-b px-3 py-2">
                    <div class="flex items-center gap-3">
                        <flux:heading>Product Data</flux:heading>

                        <flux:select size="sm" class="w-fit" wire:model="form.product_type">
                            <flux:select.option value="simple">Simple</flux:select.option>
                            <flux:select.option value="variable">Variable Product</flux:select.option>
                        </flux:select>
                    </div>
                </div>

                <div class="grid grid-cols-4">
                    <div class="col-span-1 bg-zinc-100 border-r flex flex-col divide-y overflow-hidden rounded-bl-xl">
                        <flux:button class="w-full rounded-none! cursor-pointer justify-start!" variant="ghost"
                            x-bind:class="{ 'bg-zinc-200!': $wire.activeTab === 'general' }" icon="truck"
                            icon-variant="outline" @click="$wire.activeTab = 'general'">
                            General

                            @if ($this->hasGeneralErrors())
                                <x-slot name="iconTrailing">
                                    <flux:icon.exclamation-circle class="w-4 h-4 text-red-500" variant="outline" />
                                </x-slot>
                            @endif
                        </flux:button>

                        <flux:button class="w-full rounded-none! cursor-pointer justify-start!" variant="ghost"
                            x-bind:class="{ 'bg-zinc-200!': $wire.activeTab === 'inventory' }" icon="archive-box"
                            icon-variant="outline" @click="$wire.activeTab = 'inventory'">
                            Inventory

                            @if ($this->hasInventoryErrors())
                                <x-slot name="iconTrailing">
                                    <flux:icon.exclamation-circle class="w-4 h-4 text-red-500" variant="outline" />
                                </x-slot>
                            @endif
                        </flux:button>

                        <flux:button class="w-full rounded-none! cursor-pointer justify-start!" variant="ghost"
                            x-bind:class="{ 'bg-zinc-200!': $wire.activeTab === 'shipping' }" icon="truck"
                            icon-variant="outline" @click="$wire.activeTab = 'shipping'">
                            Shipping

                            @if ($this->hasShippingErrors())
                                <x-slot name="iconTrailing">
                                    <flux:icon.exclamation-circle class="w-4 h-4 text-red-500" variant="outline" />
                                </x-slot>
                            @endif
                        </flux:button>

                        <flux:button class="w-full rounded-none! cursor-pointer justify-start!" variant="ghost"
                            x-bind:class="{ 'bg-zinc-200!': $wire.activeTab === 'linked-products' }" icon="link"
                            icon-variant="outline" @click="$wire.activeTab = 'linked-products'">
                            Linked Products

                            @if ($this->hasLinkedProductsErrors())
                                <x-slot name="iconTrailing">
                                    <flux:icon.exclamation-circle class="w-4 h-4 text-red-500" variant="outline" />
                                </x-slot>
                            @endif
                        </flux:button>

                        <flux:button class="w-full rounded-none! cursor-pointer justify-start!" variant="ghost"
                            x-bind:class="{
                                'bg-zinc-200!': $wire.activeTab === 'attributes',
                            }"
                            icon="tag" icon-variant="outline" @click="$wire.activeTab = 'attributes'">
                            Attributes

                            @if ($this->hasAttributesErrors())
                                <x-slot name="iconTrailing">
                                    <flux:icon.exclamation-circle class="w-4 h-4 text-red-500" variant="outline" />
                                </x-slot>
                            @endif
                        </flux:button>

                        <flux:button wire:cloak wire:show="form.product_type === 'variable'"
                            class="w-full rounded-none! cursor-pointer justify-start!" variant="ghost"
                            x-bind:class="{ 'bg-zinc-200!': $wire.activeTab === 'variations' }" icon="squares-2x2"
                            icon-variant="outline" @click="$wire.activeTab = 'variations'">
                            Variations

                            @if ($this->hasVariationsErrors())
                                <x-slot name="iconTrailing">
                                    <flux:icon.exclamation-circle class="w-4 h-4 text-red-500" variant="outline" />
                                </x-slot>
                            @endif
                        </flux:button>

                        <flux:button class="w-full rounded-none! cursor-pointer justify-start!" variant="ghost"
                            x-bind:class="{ 'bg-zinc-200!': $wire.activeTab === 'advanced' }" icon="cog"
                            icon-variant="outline" @click="$wire.activeTab = 'advanced'">
                            Advanced

                            @if ($this->hasAdvancedErrors())
                                <x-slot name="iconTrailing">
                                    <flux:icon.exclamation-circle class="w-4 h-4 text-red-500" variant="outline" />
                                </x-slot>
                            @endif
                        </flux:button>
                    </div>

                    <div class="col-span-3 p-5">
                        {{-- General --}}
                        @include('pages.admin.catalog.products.partials._general')

                        {{-- Inventory --}}
                        @include('pages.admin.catalog.products.partials._inventory')

                        {{-- Shipping --}}
                        @include('pages.admin.catalog.products.partials._shipping')

                        {{-- Linked Products --}}
                        @include('pages.admin.catalog.products.partials._linked-products')

                        {{-- Attributes --}}

                        {{-- Variations --}}

                        {{-- Advanced --}}
                        @include('pages.admin.catalog.products.partials._advanced')
                    </div>
                </div>

            </flux:card>

            {{-- Product Description --}}
            @include('pages.admin.catalog.products.partials._product-description')

            {{-- Product SEO --}}
            @include('pages.admin.catalog.products.partials._seo')
        </div>

        <div class="col-span-1 space-y-5">
            @include('pages.admin.catalog.products.partials._sidebar')

        </div>
    </form>
</div>
