<?php
use App\Models\Product;
use App\Livewire\Forms\Admin\ProductForm;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Brand;
use Livewire\Attributes\Computed;
use Illuminate\Validation\ValidationException;

new class extends Component {
    use WithFileUploads;
    public ProductForm $form;
    public string $activeTab = 'general';
    public bool $addNewCategory = false;
    public bool $addNewBrand = false;
    public bool $showTagModal = false;
    public array $selectedModalTags = [];

    public function mount(?Product $product = null)
    {
        if ($product instanceof Product && $product->exists) {
            $this->form->setProduct($product);
        }
    }

    public function save()
    {
        try {
            // Check if we're editing or creating
            if ($this->form->product instanceof Product && $this->form->product->exists) {
                $this->form->update();

                // Optional: Add success message and redirect
                $this->dispatch('notify', variant: 'success', message: 'Product updated successfully!');
                return redirect()->route('admin.products');
            } else {
                $product = $this->form->store();

                // Optional: Add success message and redirect
                $this->dispatch('notify', variant: 'success', message: 'Product created successfully!');
                return redirect()->route('admin.products');
            }
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

    #[Computed]
    public function categories()
    {
        $categories = Category::active()->ordered()->with('children')->whereNull('parent_id')->get();

        return $this->flattenCategories($categories);
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
    public function allCategories()
    {
        return Category::active()->orderBy('name')->get();
    }

    #[Computed]
    public function brands()
    {
        return Brand::active()->ordered()->get();
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

    public function addSelectedTagsFromModal()
    {
        $this->form->addSelectedTags($this->selectedModalTags);
        $this->closeTagModal();
    }

    public function createCategory()
    {
        $category = $this->form->createCategory();

        if ($category) {
            // Close form and force re-render
            $this->addNewCategory = false;
            unset($this->categories);
        }
    }

    public function cancelCategoryCreation()
    {
        $this->form->resetCategoryForm();
        $this->addNewCategory = false;
    }

    public function createBrand()
    {
        $brand = $this->form->createBrand();

        if ($brand) {
            // Close form
            $this->addNewBrand = false;
        }
    }

    public function cancelBrandCreation()
    {
        $this->form->resetBrandForm();
        $this->addNewBrand = false;
    }

    #[Computed]
    public function products()
    {
        return Product::active()->orderBy('name')->get();
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
}; ?>

<div>
    <form wire:submit="save" class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-6" id="product-form">

        <div class="col-span-3 space-y-5">
            {{-- Basic Information Section --}}
            <flux:card class="p-0">
                <div class="border-b px-3 py-2">
                    <flux:heading>Basic Information</flux:heading>
                </div>

                <div class="p-5 space-y-5">
                    {{-- Product Name --}}
                    <flux:input :label="__('Product Name')" wire:model="form.name" />

                    <div class="grid grid-cols-2 gap-5">
                        {{-- Model Number --}}
                        <flux:input :label="__('Model Number')" wire:model="form.model_number" />

                        {{-- Slug --}}
                        <flux:input :label="__('Slug')" wire:model="form.slug" />
                    </div>

                    <flux:field>
                        <flux:label>{{ __('Short Description') }}</flux:label>
                        <x-my-markdown wire:model="form.short_description" />
                        <flux:error name="form.short_description" />
                    </flux:field>
                </div>
            </flux:card>

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
                        <div wire:cloak wire:show="activeTab == 'general'" class="space-y-5">
                            <flux:input :label="__('Regular Price')" type="number" wire:model="form.price" />

                            <flux:input :label="__('Sale Price')" type="number" wire:model="form.sale_price" />
                        </div>

                        {{-- Inventory --}}
                        <div wire:cloak wire:show="activeTab == 'inventory'" class="space-y-5">
                            {{-- SKU --}}
                            <flux:input :label="__('SKU')" wire:model="form.sku" />

                            {{-- Manage stock --}}
                            <flux:field>
                                <flux:label>Manage Stock</flux:label>

                                <flux:checkbox wire:model="form.manage_stock"
                                    :label="__('Enable stock management for this product')" />
                            </flux:field>

                            <div wire:cloak wire:show="form.manage_stock" class="space-y-5">
                                <flux:input wire:model="form.stock_quantity" :label="__('Stock Quantity')"
                                    type="number" />

                                <flux:select :label="__('Allow backorder?')" wire:model="form.allow_backorder">
                                    <flux:select.option value="no">Do not allow</flux:select.option>
                                    <flux:select.option value="notify">Allow, but notify customer</flux:select.option>
                                    <flux:select.option value="yes">Allow</flux:select.option>
                                </flux:select>

                                <flux:input wire:model="form.low_stock_threshold"
                                    wire:model="form.low_stock_threshold" :label="__('Low Stock Threshold')"
                                    type="number" />
                            </div>

                            {{-- Stock Status --}}
                            <div wire:cloak wire:show="!form.manage_stock">
                                <flux:select wire:model="form.stock_status" :label="__('Stock Status *')">
                                    <flux:select.option value="in_stock">
                                        In Stock
                                    </flux:select.option>
                                    <flux:select.option value="out_of_stock">
                                        Out of Stock
                                    </flux:select.option>
                                    <flux:select.option value="backorder">
                                        Backorder
                                    </flux:select.option>
                                </flux:select>
                            </div>

                            <flux:separator />
                            <flux:field>
                                <flux:label>Sold Individually</flux:label>

                                <flux:checkbox wire:model="form.sold_individually"
                                    :label="__('Enable this to only allow one time to be bought in a single order')" />
                            </flux:field>
                        </div>

                        {{-- Shipping --}}
                        <div wire:cloak wire:show="activeTab == 'shipping'" class="space-y-5">
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
                        </div>

                        {{-- Linked Products --}}
                        <div wire:cloak wire:show="activeTab == 'linked-products'" class="space-y-5">

                            <flux:field>
                                <flux:label> Upsells</flux:label>

                                <x-my-choices-offline wire:model="form.selectedUpsells"
                                    placeholder="Select products for upsells" :options="$this->products" option-sub-label="sku"
                                    option-avatar="image_url" clearable searchable />
                                <flux:error name="form.selectedUpsells" />
                            </flux:field>

                            <flux:field>
                                <flux:label> Cross Sells</flux:label>
                                <x-my-choices-offline wire:model="form.selectedCrossSells" :options="$this->products"
                                    placeholder="Select products for cross sells (.e.g. Accessories)"
                                    option-sub-label="sku" option-avatar="image_url" clearable searchable />
                                <flux:error name="form.selectedCrossSells" />
                            </flux:field>
                        </div>

                        {{-- Attributes --}}
                        <div wire:cloak wire:show="activeTab == 'attributes'" class="space-y-5">
                            <flux:input :label="__('Attributes')" />
                        </div>

                        {{-- Variations --}}
                        <div wire:cloak wire:show="activeTab == 'variations'" class="space-y-5">
                            <flux:input :label="__('Variations')" />
                        </div>

                        <div wire:cloak wire:show="activeTab == 'advanced'" class="space-y-5">
                            <div class="flex flex-col items-center justify-center p-10 text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-zinc-400 mb-3"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>

                                <h3 class="text-sm font-semibold text-zinc-800">
                                    Coming Soon
                                </h3>

                                <p class="text-sm text-zinc-500 mt-1">
                                    Advanced product settings will be available in a future update.
                                </p>
                            </div>
                        </div>

                    </div>
                </div>

            </flux:card>

            <flux:card class="p-0">
                <div class="border-b px-3 py-2">
                    <flux:heading>Product Description</flux:heading>
                </div>

                <div class="p-5">
                    <flux:field>
                        <x-my-markdown wire:model="form.description" />
                        <flux:error name="form.description" />
                    </flux:field>
                </div>
            </flux:card>

            <flux:card class="p-0">
                <div class="border-b px-3 py-2">
                    <flux:heading>SEO & Meta Information</flux:heading>
                </div>

                <div class="p-5 space-y-5">
                    <!-- Meta title -->
                    <flux:input wire:model="form.meta_title" :label="__('Meta Title')" wire:model="form.meta_title"
                        placeholder="SEO title for this product" />

                    <!-- Meta description -->
                    <flux:textarea wire:model="form.meta_description" :label="__('Meta Description')"
                        wire:model="form.meta_description" rows="3"
                        placeholder="SEO description for this product" />

                    <!-- Meta keywords -->
                    <flux:input wire:model="form.meta_keywords" :label="__('Meta Keywords')"
                        placeholder="keyword1, keyword2, keyword3"
                        description:trailing="Separate keywords with commas" />

                    <flux:field>
                        <flux:label>{{ __('Canonical URL') }}</flux:label>
                        <flux:input.group>
                            <flux:input.group.prefix>{{ config('app.url') }}</flux:input.group.prefix>
                            <flux:input wire:model="form.canonical_url" placeholder="products" />
                        </flux:input.group>

                        <flux:error name="form.canonical_url" />
                    </flux:field>
                </div>

            </flux:card>
        </div>

        <div class="col-span-1 space-y-5">
            <flux:card class="p-0">
                <div class="border-b px-3 py-2">
                    <flux:heading>Status & Visibility</flux:heading>
                </div>
                <div class="p-5 space-y-5">
                    {{-- Publication Status --}}

                    <flux:select wire:model="form.status" label="Publication Status">
                        <flux:select.option value="draft">Draft</flux:select.option>
                        <flux:select.option value="scheduled">Scheduled</flux:select.option>
                        <flux:select.option value="published">Published</flux:select.option>
                        <flux:select.option value="archived">Archived</flux:select.option>
                    </flux:select>
                    @php
                        $config1 = [
                            'minDate' => now()->addDay()->format('Y-m-d'),
                        ];
                    @endphp

                    <div wire:show="form.status === 'scheduled'" wire:cloak>
                        <x-my-datepicker wire:model="form.published_at" icon="o-calendar" :config="$config1" />
                    </div>

                    <flux:separator />

                    {{-- Visibility Options --}}
                    <div class="pt-2 space-y-3 ">
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

            {{-- BRAND CARD --}}
            <flux:card class="p-0">
                <div class="border-b px-3 py-2">
                    <flux:heading>Product Brand</flux:heading>
                </div>

                <div class="p-5 space-y-5" :class="{ '-mb-5': !$wire.addNewBrand }">
                    {{-- Brand Select --}}
                    <flux:select wire:model.live="form.brand_id" label="Brand" placeholder="-- Select Brand --">
                        <flux:select.option>No Brand</flux:select.option>
                        @foreach ($this->brands as $brand)
                            <flux:select.option :value="$brand->id">{{ $brand->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    {{-- Add New Brand Toggle --}}
                    <flux:button @click="$wire.addNewBrand = !$wire.addNewBrand" type="button" icon="plus"
                        variant="ghost" size="xs" class="underline cursor-pointer">
                        {{ $addNewBrand ? 'Cancel' : 'Add new brand' }}
                    </flux:button>

                    {{-- Add New Brand Form --}}
                    <div wire:show="addNewBrand" wire:cloak class="space-y-5">
                        <flux:input wire:model="form.newBrandName" label="Brand Name"
                            placeholder="Enter brand name" />

                        <flux:input wire:model="form.newBrandWebsite" label="Website (Optional)"
                            placeholder="https://example.com" type="url" />

                        <div class="flex gap-2">
                            <flux:button type="button" wire:click="createBrand" class="flex-1">
                                Create Brand
                            </flux:button>

                            <flux:button type="button" wire:click="cancelBrandCreation" variant="ghost">
                                Cancel
                            </flux:button>
                        </div>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-0">
                <div class="border-b px-3 py-2">
                    <flux:heading>Product Image</flux:heading>
                </div>

                <div class="p-5">
                    <input type="file" class="hidden" id="product-image-input" wire:model="form.image" />

                    @if ($form->image)
                        <div class="space-y-3">
                            <div @click="document.getElementById('product-image-input').click()"
                                class="relative mx-auto w-full aspect-square rounded-sm overflow-hidden border-2 border-green-400 cursor-pointer">
                                <img src="{{ $form->image->temporaryUrl() }}" alt="Product Image preview"
                                    class="w-full h-full object-cover">

                                <div class="absolute top-2 right-2">
                                    <flux:badge color="green" size="sm">New</flux:badge>
                                </div>
                            </div>

                            <flux:text class="text-xs">Click the image to change</flux:text>
                            <flux:link wire:click="$set('form.image', null)"
                                class="text-sm text-red-500 cursor-pointer">Remove Image
                            </flux:link>
                        </div>
                    @elseif ($form->existing_image)
                        <div class="space-y-3">
                            <div @click="document.getElementById('product-image-input').click()"
                                class="relative mx-auto w-full aspect-square rounded-sm overflow-hidden border-2 border-zinc-200 cursor-pointer group">

                                <img src="{{ Storage::url($form->existing_image) }}" alt="Current product image"
                                    class="w-full h-full object-cover">

                                <div
                                    class="absolute inset-0 group-hover:bg-black/40 transition-all duration-200 flex items-center justify-center">
                                    <flux:text class="opacity-0 group-hover:opacity-100 text-white font-semibold">
                                        Click to change
                                    </flux:text>
                                </div>
                            </div>

                            <flux:text class="text-xs">Click the image to update</flux:text>
                        </div>
                    @else
                        <flux:link @click="document.getElementById('product-image-input').click()"
                            class="text-sm text-sheffield-blue cursor-pointer">Set product image
                        </flux:link>
                    @endif

                    <flux:error name="form.image" />
                </div>
            </flux:card>

            <flux:card class="p-0">
                <div class="border-b px-3 py-2">
                    <flux:heading>Product Gallery</flux:heading>
                </div>

                <div class="p-5">
                    <input type="file" class="hidden" id="product-gallery-input" wire:model="form.images"
                        multiple />

                    @if (!empty($form->images) || !empty($form->existingImages))
                        <div class="grid grid-cols-3 gap-3 mb-3">
                            {{-- Display existing images from database --}}
                            @foreach ($form->existingImages as $index => $existingImage)
                                <div class="relative group">
                                    <div
                                        class="relative mx-auto w-full aspect-square rounded-sm overflow-hidden border-2 border-zinc-200">
                                        <img src="{{ Storage::url($existingImage) }}" alt="Gallery image"
                                            class="w-full h-full object-cover">

                                        {{-- Delete button overlay --}}
                                        <div
                                            class="absolute inset-0 group-hover:bg-black/40 transition-all duration-200 flex items-center justify-center">
                                            <button type="button"
                                                wire:click="form.removeGalleryImage('{{ $existingImage }}')"
                                                class="opacity-0 group-hover:opacity-100 cursor-pointer transition-opacity duration-200 bg-red-500 hover:bg-red-600 text-white rounded-full p-2"
                                                wire:confirm="Are you sure you want to remove this image?">
                                                <flux:icon.trash variant="micro" class="size-4" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            {{-- Display newly uploaded images (not yet saved) --}}
                            @foreach ($form->images as $index => $img)
                                <div class="relative group">
                                    <div
                                        class="relative mx-auto w-full aspect-square rounded-sm overflow-hidden border-2 border-green-400">
                                        <img src="{{ $img->temporaryUrl() }}" alt="New gallery image"
                                            class="w-full h-full object-cover">

                                        {{-- Badge to show it's new --}}
                                        <div class="absolute top-2 right-2">
                                            <flux:badge color="green" size="sm">New</flux:badge>
                                        </div>

                                        {{-- Delete button overlay --}}
                                        <div
                                            class="absolute inset-0 group-hover:bg-black/40 transition-all duration-200 flex items-center justify-center">
                                            <button type="button"
                                                wire:click="$set('form.images', {{ json_encode(array_values(array_filter($form->images, fn($key) => $key !== $index, ARRAY_FILTER_USE_KEY))) }})"
                                                class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 bg-red-500 hover:bg-red-600 text-white rounded-full p-2 cursor-pointer">
                                                <flux:icon.trash variant="micro" class="size-4" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <flux:text class="text-xs text-zinc-500 mb-3">
                            {{ count($form->existingImages) }} existing image(s), {{ count($form->images) }} new
                            image(s) to upload
                        </flux:text>
                    @endif

                    <flux:link @click="document.getElementById('product-gallery-input').click()"
                        class="text-sm text-sheffield-blue cursor-pointer">
                        {{ !empty($form->images) || !empty($form->existingImages) ? 'Add more images' : 'Set product gallery images' }}
                    </flux:link>

                    @if (!empty($form->imagesToDelete))
                        <flux:text class="text-xs text-red-500 mt-2">
                            {{ count($form->imagesToDelete) }} image(s) will be deleted when you save
                        </flux:text>
                    @endif

                    <flux:error name="form.images" />
                </div>
            </flux:card>

            <flux:card class="p-0">
                <div class="border-b px-3 py-2">
                    <flux:heading>Product Categories</flux:heading>
                </div>
                <div class="p-5 space-y-5" :class="{ '-mb-5': !$wire.addNewCategory }">
                    <div class="p-2 max-h-96 overflow-y-auto border-2"
                        wire:key="categories-{{ md5(json_encode($form->category_ids)) }}">
                        <div class="space-y-2 ">
                            @foreach ($this->categories as $category)
                                <div class="flex items-center gap-2">
                                    @if ($category['depth'] > 0)
                                        <flux:icon.chevron-right variant="micro" class="text-zinc-400 ms-2" />
                                    @endif

                                    <flux:checkbox wire:model.live="form.category_ids" :value="$category['id']"
                                        :label="$category['name']" />
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <flux:button @click="$wire.addNewCategory = !$wire.addNewCategory" type="button" icon="plus"
                        variant="ghost" size="xs" class="underline cursor-pointer">Add
                        new category
                    </flux:button>

                    <div wire:show="addNewCategory" wire:cloak class="space-y-5">
                        <flux:input wire:model="form.newCategoryName" placeholder="Enter category name" />

                        <flux:select wire:model="form.newCategoryParentId" placeholder="-- Parent Category --">
                            @foreach ($this->allCategories as $category)
                                <flux:select.option :value="$category->id">{{ $category->name }}</flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:button type="button" wire:click="createCategory" size="sm">Add new category
                        </flux:button>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-0">
                <div class="border-b px-3 py-2">
                    <flux:heading>Product Tag</flux:heading>
                </div>

                <div class="p-5 space-y-5">
                    <flux:input.group>
                        <flux:input wire:model="form.newTagInput" />
                        <flux:button type="button" wire:click="addTags" class="cursor-pointer">Add</flux:button>
                    </flux:input.group>

                    <flux:text>Seperate tags with commas</flux:text>

                    {{-- Selected Tags Display --}}
                    @if ($this->selectedTags->isNotEmpty())
                        <div class="flex flex-wrap gap-2">
                            @foreach ($this->selectedTags as $tag)
                                <flux:badge color="zinc" class="flex items-center gap-1.5" size="sm">
                                    <span>{{ $tag->name }}</span>
                                    <button type="button" wire:click="removeTag({{ $tag->id }})"
                                        class="hover:text-red-600 transition-colors cursor-pointer">
                                        <flux:icon.x-mark variant="micro" />
                                    </button>
                                </flux:badge>
                            @endforeach
                        </div>
                    @endif

                    {{-- Most Used Tags Button --}}
                    <flux:button type="button" wire:click="openTagModal" class="cursor-pointer" variant="ghost"
                        size="xs">
                        Choose from the most used tags
                    </flux:button>
                </div>
            </flux:card>
        </div>
    </form>

    {{-- Most Used Tags Modal --}}
    <flux:modal wire:model="showTagModal" class="max-w-md">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Most Used Tags</flux:heading>
                <flux:subheading>Select tags to add to your product</flux:subheading>
            </div>

            <div class="space-y-2 max-h-96 overflow-y-auto">
                @forelse ($this->mostUsedTags as $tag)
                    <div class="flex items-center justify-between p-2 hover:bg-zinc-50 rounded">
                        <flux:checkbox wire:model="selectedModalTags" :value="$tag->id"
                            :label="$tag->name" />
                        <flux:badge size="sm" color="zinc">
                            {{ $tag->products_count }} {{ Str::plural('product', $tag->products_count) }}
                        </flux:badge>
                    </div>
                @empty
                    <div class="text-center py-8 text-zinc-500">
                        <p>No tags available yet</p>
                    </div>
                @endforelse
            </div>

            <div class="flex gap-2 justify-end pt-4 border-t">
                <flux:button variant="ghost" wire:click="closeTagModal">Cancel</flux:button>
                <flux:button wire:click="addSelectedTagsFromModal">Add Selected Tags</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
