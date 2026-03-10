<?php

namespace App\Livewire\Admin;

use App\Livewire\Forms\Admin\ProductForm;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use App\Services\Product\ProductAttributeService;
use App\Services\Product\ProductDownloadService;
use App\Services\Product\ProductVariationService;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

abstract class BaseProductComponent extends Component
{
    use WithFileUploads;

    // ===================================================
    // CORE STATE
    // ===================================================

    public ProductForm $form;
    public string $activeTab = 'general';
    public bool $addNewCategory = false;
    public bool $addNewBrand = false;
    public bool $showTypeChangeModal = false;
    public string $pendingProductType = '';

    // ===================================================
    // ATTRIBUTES STATE
    // ===================================================

    public array $selectedAttributes = [];
    public ?int $selectedExistingAttribute = null;

    // ===================================================
    // VARIATIONS STATE
    // ===================================================

    public array $variants = [];
    public array $variantsToDelete = [];
    public array $availableAttributes = [];
    public array $variantImages = [];

    public ?float $bulkPrice = null;
    public ?float $bulkSalePrice = null;
    public ?int $bulkStockQuantity = null;
    public ?float $bulkWeight = null;
    public ?float $bulkLength = null;
    public ?float $bulkWidth = null;
    public ?float $bulkHeight = null;

    // ===================================================
    // GROUPED PRODUCTS STATE
    // ===================================================

    public array $groupedProducts = [];
    public array $selectedGroupedProducts = [];

    // ===================================================
    // DOWNLOADS STATE
    // ===================================================

    public array $downloadsToDelete = [];

    // ===================================================
    // SAVE
    // ===================================================

    public function save(): void
    {
        try {
            $this->form->validate();
        } catch (ValidationException $e) {
            $this->dispatch('notify', variant: 'warning', message: 'Please correct the highlighted fields.');
            throw $e;
        }

        $this->executeSave();
    }

    abstract protected function executeSave(): void;

    protected function persistProduct(Product $product): void
    {
        // Merge variant images before saving
        foreach ($this->variantImages as $index => $image) {
            if (!empty($image) && isset($this->variants[$index])) {
                $this->variants[$index]['image'] = $image;
            }
        }

        // Sync grouped products to form before saving
        $this->form->grouped_products = collect($this->groupedProducts)
            ->map(fn($item) => [
                'id'       => $item['id'],
                'quantity' => $item['quantity'] ?? 1,
            ])
            ->toArray();

        // Save attributes and variations for non-grouped products
        if ($product->type !== 'grouped') {
            app(ProductAttributeService::class)->save(
                $product,
                $this->selectedAttributes
            );

            app(ProductVariationService::class)->save(
                $product,
                $this->variants,
                $this->variantsToDelete
            );
        }

        // Save downloads for downloadable products
        if ($this->form->is_downloadable) {
            app(ProductDownloadService::class)->sync(
                $product,
                $this->form->downloads,
                $this->downloadsToDelete
            );
        }
    }

    // ===================================================
    // TYPE SWITCHING
    // ===================================================

    public function updatedFormType(string $value): void
    {
        $productId = $this->form->getProductId();

        // Reset virtual/downloadable when switching to grouped
        if ($value === 'grouped') {
            $this->form->is_virtual = false;
            $this->form->is_downloadable = false;
        }

        // Redirect away from hidden tabs when switching to grouped
        if ($value === 'grouped' && in_array($this->activeTab, [
            'general',
            'inventory',
            'shipping',
            'variations',
            'downloads'
        ])) {
            $this->activeTab = 'linked-products';
        }

        // Clear grouped state when switching away from grouped
        if ($value !== 'grouped') {
            $this->groupedProducts = [];
            $this->selectedGroupedProducts = [];
        }

        // Switching to simple — deactivate variants if any exist
        if (
            $value === 'simple' && $productId &&
            app(ProductVariationService::class)->hasActiveVariants($productId)
        ) {
            $this->pendingProductType = $value;
            $this->form->type = 'variable';
            $this->showTypeChangeModal = true;
            return;
        }

        // Switching back to variable — reactivate variants
        if ($value === 'variable' && $productId) {
            app(ProductVariationService::class)->reactivateAll($productId);
            foreach ($this->variants as $index => $variant) {
                $this->variants[$index]['is_active'] = true;
            }
            $this->dispatch('notify', variant: 'success', message: 'Variations restored.');
        }
    }

    public function updatedFormIsVirtual(bool $value): void
    {
        // Redirect away from shipping when virtual is checked
        if ($value && $this->activeTab === 'shipping') {
            $this->activeTab = 'general';
        }
    }

    public function updatedFormIsDownloadable(bool $value): void
    {
        // Redirect away from downloads when downloadable is unchecked
        if (!$value && $this->activeTab === 'downloads') {
            $this->activeTab = 'general';
        }
    }

    public function confirmTypeChange(): void
    {
        $productId = $this->form->getProductId();

        if ($productId) {
            app(ProductVariationService::class)->deactivateAll($productId);
        }

        foreach ($this->variants as $index => $variant) {
            $this->variants[$index]['is_active'] = false;
        }

        $this->form->type = $this->pendingProductType;
        $this->pendingProductType = '';
        $this->showTypeChangeModal = false;
        $this->dispatch('notify', variant: 'warning', message: 'Switched to Simple. All variations deactivated.');
    }

    public function cancelTypeChange(): void
    {
        $this->form->type = 'variable';
        $this->pendingProductType = '';
        $this->showTypeChangeModal = false;
    }

    // ===================================================
    // ATTRIBUTES
    // ===================================================

    protected function loadProductAttributes(Product $product): void
    {
        $this->selectedAttributes = $product
            ->attributes()
            ->withPivot(['is_visible', 'is_variation_attribute', 'sort_order', 'values'])
            ->get()
            ->map(fn($attr) => [
                'attribute_id'           => $attr->id,
                'name'                   => $attr->name,
                'is_new'                 => false,
                'is_visible'             => (bool) $attr->pivot->is_visible,
                'is_variation_attribute' => (bool) $attr->pivot->is_variation_attribute,
                'sort_order'             => $attr->pivot->sort_order,
                'values'                 => json_decode($attr->pivot->values ?? '[]', true) ?? [],
            ])
            ->toArray();

        $this->syncAvailableAttributes();
    }

    public function addNewAttribute(): void
    {
        $this->selectedAttributes[] = [
            'attribute_id'           => null,
            'name'                   => '',
            'is_new'                 => true,
            'is_visible'             => true,
            'is_variation_attribute' => false,
            'sort_order'             => count($this->selectedAttributes),
            'values'                 => '',
        ];
    }

    public function addExistingAttribute($attributeId): void
    {
        if (!$attributeId) return;

        $already = collect($this->selectedAttributes)
            ->pluck('attribute_id')
            ->contains((int) $attributeId);

        if ($already) {
            $this->dispatch('notify', variant: 'warning', message: 'Attribute already added.');
            return;
        }

        $attribute = Attribute::find($attributeId);
        if (!$attribute) return;

        $this->selectedAttributes[] = [
            'attribute_id'           => $attribute->id,
            'name'                   => $attribute->name,
            'is_new'                 => false,
            'is_visible'             => true,
            'is_variation_attribute' => false,
            'sort_order'             => count($this->selectedAttributes),
            'values'                 => [],
        ];

        $this->syncAvailableAttributes();
    }

    public function removeSelectedAttribute(int $index): void
    {
        array_splice($this->selectedAttributes, $index, 1);
        $this->selectedAttributes = array_values($this->selectedAttributes);
        $this->syncAvailableAttributes();
    }

    public function updatedSelectedAttributes(): void
    {
        $this->syncAvailableAttributes();
    }

    public function getProductAttributeValues(int $attributeId): array
    {
        return AttributeValue::where('attribute_id', $attributeId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn($v) => ['id' => $v->id, 'name' => $v->label ?: $v->value])
            ->toArray();
    }

    public function saveAttributes(): void
    {
        $productId = $this->form->getProductId();

        if (!$productId) {
            $this->dispatch('notify', variant: 'info', message: 'Attributes will be saved when you create the product.');
            return;
        }

        foreach ($this->selectedAttributes as $attr) {
            if (empty(trim($attr['name']))) {
                $this->dispatch('notify', variant: 'warning', message: 'All attributes must have a name.');
                return;
            }

            $hasValues = is_array($attr['values'])
                ? !empty($attr['values'])
                : !empty(trim($attr['values']));

            if (!$hasValues) {
                $this->dispatch('notify', variant: 'warning', message: "Attribute \"{$attr['name']}\" must have at least one value.");
                return;
            }
        }

        app(ProductAttributeService::class)->save(
            Product::findOrFail($productId),
            $this->selectedAttributes
        );

        $this->syncAvailableAttributes();
        $this->dispatch('notify', variant: 'success', message: 'Attributes saved.');
    }

    private function syncAvailableAttributes(): void
    {
        $this->availableAttributes = collect($this->selectedAttributes)
            ->filter(fn($a) => $a['is_variation_attribute'])
            ->values()
            ->toArray();
    }

    // ===================================================
    // VARIATIONS
    // ===================================================

    protected function loadProductVariants(Product $product): void
    {
        $this->variants = $product
            ->variants()
            ->with([
                'attributeValues:id,attribute_id,value',
                'attributeValues.attribute:id,name',
            ])
            ->get()
            ->map(fn($variant) => [
                'id'                  => $variant->id,
                'name'                => $variant->name,
                'sku'                 => $variant->sku,
                'image_path'          => $variant->image_path,
                'image'               => null,
                'price'               => $variant->price,
                'sale_price'          => $variant->sale_price,
                'manage_stock'        => $variant->manage_stock,
                'stock_quantity'      => $variant->stock_quantity,
                'stock_status'        => $variant->stock_status,
                'allow_backorders'    => $variant->allow_backorders,
                'low_stock_threshold' => $variant->low_stock_threshold,
                'weight'              => $variant->weight,
                'length'              => $variant->length,
                'width'               => $variant->width,
                'height'              => $variant->height,
                'description'         => $variant->description,
                'is_active'           => $variant->is_active,
                'is_default'          => $variant->is_default,
                'attributes'          => $variant->attributeValues
                    ->mapWithKeys(fn($av) => [$av->attribute->name => $av->value])
                    ->toArray(),
                'attribute_value_ids' => $variant->attributeValues->pluck('id')->toArray(),
                'attribute_hash'      => md5(implode('-', $variant->attributeValues->pluck('id')->sort()->toArray())),
            ])
            ->toArray();
    }

    public function generateVariations(): void
    {
        $variationAttributes = collect($this->availableAttributes)
            ->filter(fn($a) => $a['is_variation_attribute'])
            ->values();

        if ($variationAttributes->isEmpty()) {
            $this->dispatch('notify', variant: 'warning', message: 'No attributes marked as "Used for variations".');
            return;
        }

        $attributeValueGroups = [];

        foreach ($variationAttributes as $attr) {
            $valueIds = is_array($attr['values']) ? $attr['values'] : [];
            if (empty($valueIds)) continue;

            $values = AttributeValue::whereIn('id', $valueIds)->get();
            if ($values->isEmpty()) continue;

            $attributeValueGroups[] = $values->map(fn($v) => [
                'attribute_name' => $attr['name'],
                'value'          => $v->value,
                'value_id'       => $v->id,
            ])->toArray();
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

            if (in_array($hash, $existingHashes)) continue;

            $attributes = collect($combination)
                ->mapWithKeys(fn($c) => [$c['attribute_name'] => $c['value']])
                ->toArray();

            $this->variants[] = [
                'id'                  => null,
                'name'                => implode(' - ', array_values($attributes)),
                'sku'                 => '',
                'image'               => null,
                'image_path'          => null,
                'price'               => null,
                'sale_price'          => null,
                'manage_stock'        => true,
                'stock_quantity'      => 0,
                'stock_status'        => 'in_stock',
                'allow_backorders'    => false,
                'low_stock_threshold' => null,
                'weight'              => null,
                'length'              => null,
                'width'               => null,
                'height'              => null,
                'description'         => null,
                'is_active'           => true,
                'is_default'          => false,
                'attributes'          => $attributes,
                'attribute_value_ids' => $valueIds,
                'attribute_hash'      => $hash,
            ];

            $newCount++;
        }

        $this->dispatch('notify', variant: 'success', message: "{$newCount} new variation(s) generated.");
    }

    public function addVariant(): void
    {
        $this->variants[] = [
            'id'                  => null,
            'name'                => null,
            'sku'                 => '',
            'image'               => null,
            'image_path'          => null,
            'price'               => null,
            'sale_price'          => null,
            'manage_stock'        => true,
            'stock_quantity'      => 0,
            'stock_status'        => 'in_stock',
            'allow_backorders'    => false,
            'low_stock_threshold' => null,
            'weight'              => null,
            'length'              => null,
            'width'               => null,
            'height'              => null,
            'description'         => null,
            'is_active'           => true,
            'is_default'          => false,
            'attributes'          => [],
            'attribute_value_ids' => [],
            'attribute_hash'      => Str::uuid(),
        ];
    }

    public function removeVariant(int $index): void
    {
        $variant = $this->variants[$index];

        if (!empty($variant['id'])) {
            $this->variantsToDelete[] = $variant['id'];
        }

        array_splice($this->variants, $index, 1);
        $this->variants = array_values($this->variants);
    }

    public function clearAllVariants(): void
    {
        foreach ($this->variants as $variant) {
            if (!empty($variant['id'])) {
                $this->variantsToDelete[] = $variant['id'];
            }
        }

        $this->variants = [];
        $this->dispatch('notify', variant: 'success', message: 'All variations removed. Save to apply.');
    }

    public function removeVariantImage(int $index): void
    {
        $this->variantImages[$index] = null;
        $this->variants[$index]['image_path'] = null;
        $this->variants[$index]['image'] = null;
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
            if ($this->bulkPrice !== null)
                $this->variants[$index]['price'] = $this->bulkPrice;
            if ($this->bulkSalePrice !== null)
                $this->variants[$index]['sale_price'] = $this->bulkSalePrice;
        }

        $this->bulkPrice = $this->bulkSalePrice = null;
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
        $this->dispatch('notify', variant: 'success', message: 'Stock applied to all variations.');
        $this->dispatch('close-modal', name: 'bulk-stock');
    }

    public function applyBulkDimensions(): void
    {
        foreach ($this->variants as $index => $variant) {
            if ($this->bulkWeight !== null)
                $this->variants[$index]['weight'] = $this->bulkWeight;
            if ($this->bulkLength !== null)
                $this->variants[$index]['length'] = $this->bulkLength;
            if ($this->bulkWidth !== null)
                $this->variants[$index]['width'] = $this->bulkWidth;
            if ($this->bulkHeight !== null)
                $this->variants[$index]['height'] = $this->bulkHeight;
        }

        $this->bulkWeight = $this->bulkLength = $this->bulkWidth = $this->bulkHeight = null;
        $this->dispatch('notify', variant: 'success', message: 'Dimensions applied to all variations.');
        $this->dispatch('close-modal', name: 'bulk-dimensions');
    }

    // ===================================================
    // GROUPED PRODUCTS
    // ===================================================

    protected function loadGroupedProducts(Product $product): void
    {
        $this->groupedProducts = $product
            ->groupedProducts()
            ->get()
            ->map(fn($p) => [
                'id'       => $p->id,
                'name'     => $p->name,
                'sku'      => $p->sku,
                'price'    => $p->price,
                'quantity' => $p->pivot->quantity,
            ])
            ->toArray();
    }

    public function addGroupedProducts(): void
    {
        if (empty($this->selectedGroupedProducts)) return;

        $existingIds = collect($this->groupedProducts)->pluck('id')->toArray();

        $newIds = array_filter(
            $this->selectedGroupedProducts,
            fn($id) => !in_array($id, $existingIds)
        );

        if (empty($newIds)) {
            $this->dispatch('notify', variant: 'warning', message: 'Selected products are already in the kit.');
            return;
        }

        $products = Product::whereIn('id', $newIds)
            ->select('id', 'name', 'sku', 'price')
            ->get();

        foreach ($products as $product) {
            $this->groupedProducts[] = [
                'id'       => $product->id,
                'name'     => $product->name,
                'sku'      => $product->sku,
                'price'    => $product->price,
                'quantity' => 1,
            ];
        }

        $added = count($products);
        $this->selectedGroupedProducts = [];
        $this->dispatch('notify', variant: 'success', message: "{$added} product(s) added to kit.");
    }

    public function removeGroupedProduct(int $index): void
    {
        array_splice($this->groupedProducts, $index, 1);
        $this->groupedProducts = array_values($this->groupedProducts);
    }

    public function getGroupedTotal(): float
    {
        return collect($this->groupedProducts)
            ->sum(fn($item) => ($item['price'] ?? 0) * ($item['quantity'] ?? 1));
    }

    // ===================================================
    // DOWNLOADS
    // ===================================================

    protected function loadProductDownloads(Product $product): void
    {
        $this->form->downloads = $product->downloads
            ->map(fn($download) => [
                'id'                  => $download->id,
                'name'                => $download->name,
                'file'                => null,
                'file_path'           => $download->file_path,
                'file_name'           => $download->file_name,
                'file_type'           => $download->file_type,
                'file_size'           => $download->file_size,
                'formatted_file_size' => $download->formatted_file_size,
            ])
            ->toArray();
    }

    public function addDownloadFile(): void
    {
        $this->form->downloads[] = [
            'id'        => null,
            'name'      => '',
            'file'      => null,
            'file_path' => null,
            'file_name' => null,
            'file_type' => null,
            'file_size' => null,
        ];
    }

    public function removeDownloadFile(int $index): void
    {
        if (!empty($this->form->downloads[$index]['id'])) {
            $this->downloadsToDelete[] = $this->form->downloads[$index]['id'];
        }

        array_splice($this->form->downloads, $index, 1);
        $this->form->downloads = array_values($this->form->downloads);
    }

    public function clearDownloadFile(int $index): void
    {
        $this->form->downloads[$index]['file'] = null;
    }

    // ===================================================
    // IMAGES
    // ===================================================

    public function removeGalleryImage(string $imagePath): void
    {
        if (!in_array($imagePath, $this->form->imagesToDelete)) {
            $this->form->imagesToDelete[] = $imagePath;
        }

        $this->form->existingImages = array_values(
            array_filter(
                $this->form->existingImages,
                fn($path) => $path !== $imagePath
            )
        );
    }

    public function removeNewImage(int $index): void
    {
        unset($this->form->images[$index]);
        $this->form->images = array_values($this->form->images);
    }

    // ===================================================
    // TAGS
    // ===================================================

    public function addTags(): void
    {
        $this->form->addTags();
        unset($this->selectedTags);
    }

    public function removeTag(int $tagId): void
    {
        $this->form->removeTag($tagId);
        unset($this->selectedTags);
    }

    // ===================================================
    // CATEGORIES
    // ===================================================

    public function createCategory(): void
    {
        $this->form->createCategory();
        $this->addNewCategory = false;
        unset($this->categories);
    }

    public function cancelCategoryCreation(): void
    {
        $this->form->resetCategoryForm();
        $this->addNewCategory = false;
    }

    // ===================================================
    // BRANDS
    // ===================================================

    public function createBrand(): void
    {
        $this->form->createBrand();
        $this->addNewBrand = false;
        unset($this->brands);
    }

    public function cancelBrandCreation(): void
    {
        $this->form->resetBrandForm();
        $this->addNewBrand = false;
    }

    // ===================================================
    // TAB ERROR INDICATORS
    // ===================================================

    public function hasGeneralErrors(): bool
    {
        return $this->getErrorBag()->hasAny([
            'form.price',
            'form.sale_price',
            'form.cost_price',
            'form.downloads',
            'form.downloads.*',
            'form.download_limit',
            'form.download_expiry',
        ]);
    }

    public function hasInventoryErrors(): bool
    {
        return $this->getErrorBag()->hasAny([
            'form.sku',
            'form.manage_stock',
            'form.stock_quantity',
            'form.allow_backorder',
            'form.low_stock_threshold',
            'form.stock_status',
            'form.sold_individually',
        ]);
    }

    public function hasShippingErrors(): bool
    {
        return $this->getErrorBag()->hasAny([
            'form.weight',
            'form.length',
            'form.width',
            'form.height',
        ]);
    }

    public function hasLinkedProductsErrors(): bool
    {
        return $this->getErrorBag()->hasAny([
            'form.selected_upsells',
            'form.selected_cross_sells',
            'form.grouped_products',
        ]);
    }

    public function hasAttributesErrors(): bool
    {
        return false;
    }

    public function hasVariationsErrors(): bool
    {
        return false;
    }

    public function hasAdvancedErrors(): bool
    {
        return false;
    }

    // ===================================================
    // COMPUTED PROPERTIES
    // ===================================================

    #[Computed(persist: true)]
    public function productAttributes()
    {
        return Attribute::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    #[Computed(persist: true)]
    public function products()
    {
        $currentId = $this->form->getProductId();

        return Product::active()
            ->select('id', 'name', 'sku', 'image_path')
            ->when($currentId, fn($q) => $q->where('id', '!=', $currentId))
            ->orderBy('name')
            ->get()
            ->map(fn($product) => [
                'id'        => $product->id,
                'name'      => $product->name,
                'sku'       => $product->sku,
                'image_url' => $product->image_url,
            ]);
    }

    #[Computed(persist: true)]
    public function brands()
    {
        return Brand::active()->ordered()->select('id', 'name')->get();
    }

    #[Computed(persist: true)]
    public function categories()
    {
        $categories = Category::active()
            ->ordered()
            ->select('id', 'name', 'parent_id')
            ->with('children')
            ->whereNull('parent_id')
            ->get();

        return $this->flattenCategories($categories);
    }

    #[Computed]
    public function allCategories()
    {
        return Category::active()->orderBy('name')->get();
    }

    #[Computed]
    public function selectedTags()
    {
        return $this->form->getSelectedTags();
    }

    #[Computed(persist: true)]
    public function mostUsedTags()
    {
        return Tag::withCount('products')
            ->orderByDesc('products_count')
            ->limit(20)
            ->get();
    }

    // ===================================================
    // HELPERS
    // ===================================================

    protected function flattenCategories($categories, $depth = 0): array
    {
        $result = [];

        foreach ($categories as $category) {
            $result[] = [
                'id'    => $category->id,
                'name'  => $category->name,
                'depth' => $depth,
            ];

            if ($category->children->isNotEmpty()) {
                $result = array_merge(
                    $result,
                    $this->flattenCategories($category->children, $depth + 1)
                );
            }
        }

        return $result;
    }

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
}
