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
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * BaseProductComponent
 *
 * Abstract base class for the Create and Edit product Livewire components.
 * Owns all shared state, validation, and persistence logic for product management.
 * Concrete implementations (Create / Edit) only need to implement executeSave().
 */
abstract class BaseProductComponent extends Component
{
    use WithFileUploads;

    // =========================================================================
    // CORE STATE
    // =========================================================================

    /** The Livewire form object holding all product field values and rules. */
    public ProductForm $form;

    /** Tracks whether the admin has unsaved changes — used for the beforeunload guard. */
    public bool $isDirty = false;

    /** The currently active tab in the Product Data card. */
    public string $activeTab = 'general';

    /** Controls visibility of the inline "Add new category" form. */
    public bool $addNewCategory = false;

    /** Controls visibility of the inline "Add new brand" form. */
    public bool $addNewBrand = false;

    /** Controls visibility of the type-change confirmation modal. */
    public bool $showTypeChangeModal = false;

    /** Holds the pending product type during a type-change confirmation flow. */
    public string $pendingProductType = '';

    // =========================================================================
    // ATTRIBUTES STATE
    // =========================================================================

    /**
     * The list of attributes currently added to this product.
     * Each entry: [attribute_id, name, is_new, is_visible, is_variation_attribute, sort_order, values]
     */
    public array $selectedAttributes = [];

    /** Holds the selected value from the "Add existing attribute" dropdown before it is added. */
    public ?int $selectedExistingAttribute = null;

    // =========================================================================
    // VARIATIONS STATE
    // =========================================================================

    /**
     * The full list of variants for this product.
     * Each entry maps to a ProductVariant row including pricing, stock,
     * shipping, attributes, image, downloads, and flags.
     */
    public array $variants = [];

    /** IDs of variants queued for deletion on next save. */
    public array $variantsToDelete = [];

    /**
     * Subset of selectedAttributes where is_variation_attribute = true.
     * Drives the Generate Variations and Default Variation UI.
     */
    public array $availableAttributes = [];

    /**
     * Temporary file upload holders for variant images, keyed by variant index.
     * Merged into variants[] before persistProduct() runs.
     */
    public array $variantImages = [];

    /**
     * Holds the selected attribute values for the "Default Variation" dropdowns.
     * E.g. ['Color' => 'Red', 'Size' => 'Large']
     * When all attributes are selected, applyDefaultVariantSelection() fires.
     */
    public array $defaultVariantAttributes = [];

    // Bulk action staging fields — these are UI-only and do not map to saved data
    public ?float $bulkPrice = null;

    public ?int $bulkStockQuantity = null;

    public ?float $bulkWeight = null;

    public ?float $bulkLength = null;

    public ?float $bulkWidth = null;

    public ?float $bulkHeight = null;

    // =========================================================================
    // GROUPED PRODUCTS STATE
    // =========================================================================

    /**
     * Grouped products currently in the kit.
     * Each entry: [id, name, sku, price, quantity]
     */
    public array $groupedProducts = [];

    /** Staging area for the grouped product multi-select before "Add" is clicked. */
    public array $selectedGroupedProducts = [];

    // =========================================================================
    // ACCESSORIES STATE
    // =========================================================================

    /**
     * Accessories currently linked to this product.
     * Each entry: [id, name, sku, price, quantity]
     */
    public array $accessories = [];

    /** Staging area for the accessories multi-select before "Add" is clicked. */
    public array $selectedAccessories = [];

    // =========================================================================
    // DOWNLOADS STATE
    // =========================================================================

    /** IDs of product-level download records queued for deletion on next save. */
    public array $downloadsToDelete = [];

    // =========================================================================
    // DIRTY TRACKING
    // =========================================================================

    /**
     * Fires on every Livewire property update.
     * Marks isDirty = true for any data-bearing property change,
     * ignoring UI-only properties that don't represent unsaved data.
     */
    public function updated(string $property): void
    {
        $uiOnlyProperties = [
            'activeTab',
            'addNewCategory',
            'addNewBrand',
            'showTypeChangeModal',
            'pendingProductType',
            'selectedExistingAttribute',
            'selectedGroupedProducts',
            'selectedAccessories',
            'defaultVariantAttributes',
            'bulkPrice',
            'bulkStockQuantity',
            'bulkWeight',
            'bulkLength',
            'bulkWidth',
            'bulkHeight',
            'isDirty',
        ];

        $root = explode('.', $property)[0];

        if (! in_array($root, $uiOnlyProperties)) {
            $this->isDirty = true;
        }
    }

    // =========================================================================
    // SAVE PIPELINE
    // =========================================================================

    /**
     * Main save entry point.
     * Runs validation in sequence, then hands off to the concrete executeSave().
     * Any ValidationException is re-thrown so Livewire can surface field errors.
     */
    public function save(): void
    {
        try {
            $this->form->validate();
            $this->validateVariantImages();
            $this->validateDownloads();
            $this->validateVariants();
        } catch (ValidationException $e) {
            $this->dispatch('notify', title: 'Validation Error', variant: 'warning', message: 'Please correct the highlighted fields.');
            throw $e;
        }

        $this->executeSave();
    }

    /**
     * Implemented by Create and Edit to define what happens after validation passes.
     * Typically calls form->store() or form->update() then persistProduct().
     */
    abstract protected function executeSave(): void;

    /**
     * Persists all product-related data that lives outside ProductForm:
     * attributes, variations (with variant downloads), and product-level downloads.
     * Wrapped in a DB transaction so a partial failure rolls back everything.
     */
    protected function persistProduct(Product $product): void
    {
        DB::transaction(function () use ($product) {
            // Merge any newly uploaded variant images into the variants array
            foreach ($this->variantImages as $index => $image) {
                if (! empty($image) && isset($this->variants[$index])) {
                    $this->variants[$index]['image'] = $image;
                }
            }

            // Sync component-level arrays back to the form before saving
            // so ProductForm::syncRelationships() has the current state
            $this->form->grouped_products = collect($this->groupedProducts)
                ->map(fn ($item) => [
                    'id' => $item['id'],
                    'quantity' => $item['quantity'] ?? 1,
                ])
                ->toArray();

            $this->form->accessories = collect($this->accessories)
                ->map(fn ($item) => [
                    'id' => $item['id'],
                    'quantity' => $item['quantity'] ?? 1,
                ])
                ->toArray();

            // Attributes and variations are not relevant for grouped products
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

            // Product-level downloads (variant_id = null) are synced separately
            if ($this->form->is_downloadable) {
                app(ProductDownloadService::class)->sync(
                    product: $product,
                    downloads: $this->form->downloads,
                    downloadsToDelete: $this->downloadsToDelete,
                    variantId: null,
                );
            }
        });
    }

    // =========================================================================
    // VALIDATION HELPERS
    // =========================================================================

    /**
     * Validates any uploaded variant images against file type and size rules.
     * Runs before executeSave() so invalid files are caught early.
     */
    protected function validateVariantImages(): void
    {
        $rules = [];

        foreach ($this->variantImages as $index => $image) {
            if (! empty($image)) {
                $rules["variantImages.{$index}"] = [
                    'nullable',
                    'image',
                    'max:2048',
                    'mimes:jpg,jpeg,png,gif,webp',
                ];
            }
        }

        if (! empty($rules)) {
            $this->validate($rules, [], [
                'variantImages.*' => 'variant image',
            ]);
        }
    }

    /**
     * Ensures every download row has either a newly uploaded file
     * or an existing saved file path.
     * Prevents saving an empty download record that produces a broken link.
     */
    protected function validateDownloads(): void
    {
        if (! $this->form->is_downloadable) {
            return;
        }

        foreach ($this->form->downloads as $index => $download) {
            $hasNewFile = ! empty($download['file']);
            $hasExistingFile = ! empty($download['file_path']);

            if (! $hasNewFile && ! $hasExistingFile) {
                $validator = validator([], []);
                $validator->errors()->add(
                    "form.downloads.{$index}.file",
                    'Each download entry must have a file.'
                );
                throw new ValidationException($validator);
            }
        }
    }

    /**
     * Validates active variant pricing for variable products:
     * - Every active variant must have a regular price.
     * - Sale price must be less than regular price when set.
     */
    protected function validateVariants(): void
    {
        if ($this->form->type !== 'variable') {
            return;
        }

        foreach ($this->variants as $index => $variant) {
            // Skip inactive variants — they are not shown in the store
            if (empty($variant['is_active'])) {
                continue;
            }

            if (is_null($variant['price']) || $variant['price'] === '') {
                $validator = validator([], []);
                $validator->errors()->add(
                    "variants.{$index}.price",
                    'Variation "'.($variant['name'] ?? '#'.($index + 1)).'" must have a price.'
                );
                throw new ValidationException($validator);
            }

            if (! empty($variant['sale_price']) && $variant['sale_price'] >= $variant['price']) {
                $validator = validator([], []);
                $validator->errors()->add(
                    "variants.{$index}.sale_price",
                    'Sale price for "'.($variant['name'] ?? '#'.($index + 1)).'" must be less than regular price.'
                );
                throw new ValidationException($validator);
            }
        }
    }

    // =========================================================================
    // PRODUCT TYPE SWITCHING
    // =========================================================================

    /**
     * Fires when form.type changes.
     * Handles state cleanup, tab redirection, and the type-change confirmation
     * modal when switching away from variable while active variants exist.
     */
    public function updatedFormType(string $value): void
    {
        $productId = $this->form->getProductId();

        if ($value === 'grouped') {
            // Grouped products cannot be virtual or downloadable
            $this->form->is_virtual = false;
            $this->form->is_downloadable = false;

            // Clear attribute/variation state — irrelevant for grouped
            $this->selectedAttributes = [];
            $this->availableAttributes = [];
            $this->selectedExistingAttribute = null;
        }

        if ($value !== 'grouped') {
            // Clear grouped state when switching to any non-grouped type
            $this->groupedProducts = [];
            $this->selectedGroupedProducts = [];
        }

        // Switching to simple while active variants exist requires confirmation
        if (
            $value === 'simple' && $productId &&
            app(ProductVariationService::class)->hasActiveVariants($productId)
        ) {
            $this->pendingProductType = $value;
            $this->form->type = 'variable'; // Hold at variable until confirmed
            $this->showTypeChangeModal = true;

            return;
        }

        // Switching back to variable reactivates all deactivated variants.
        // Also ensure variants are loaded if they weren't at mount (e.g. was a simple product).
        if ($value === 'variable' && $productId) {
            $product = Product::findOrFail($productId);

            if (empty($this->variants)) {
                $this->loadProductVariants($product);
            }

            app(ProductVariationService::class)->reactivateAll($productId);

            foreach ($this->variants as $index => $variant) {
                $this->variants[$index]['is_active'] = true;
            }

            $this->dispatch('notify', title: 'Type Changed', variant: 'success', message: 'Switched to Variable. Variations restored.');
        }

        // Switching to grouped — load any existing grouped children that weren't loaded at mount.
        if ($value === 'grouped' && $productId && empty($this->groupedProducts)) {
            $this->loadGroupedProducts(Product::findOrFail($productId));
        }

        // Navigate to the most useful tab for the new type.
        // For grouped → Linked Products (where child products are added).
        // For variable → Variations (where variation setup lives).
        // For everything else → stay on the current tab if it is still valid, otherwise General.
        $this->activeTab = $this->resolveActiveTabForType($value);
    }

    /**
     * Returns the tab the user should land on after switching to $type.
     * Jumps to the primary tab for grouped/variable; for all other types
     * keeps the current tab when it is still valid, or falls back to General.
     */
    private function resolveActiveTabForType(string $type): string
    {
        return match ($type) {
            'grouped' => 'linked-products',
            'variable' => 'variations',
            default => in_array($this->activeTab, ['general', 'inventory', 'shipping', 'linked-products', 'attributes', 'advanced'])
                ? $this->activeTab
                : 'general',
        };
    }

    /**
     * Fires when form.is_virtual changes.
     * Redirects away from the Shipping tab when virtual is enabled —
     * shipping is irrelevant for virtual products.
     */
    public function updatedFormIsVirtual(bool $value): void
    {
        if ($value && $this->activeTab === 'shipping') {
            $this->activeTab = 'general';
        }
    }

    /**
     * Confirmed type change: deactivates all variants and switches to Simple.
     */
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
        $this->activeTab = $this->resolveActiveTabForType($this->pendingProductType);
        $this->pendingProductType = '';
        $this->showTypeChangeModal = false;

        $this->dispatch('notify', title: 'Type Changed', variant: 'warning', message: 'Switched to Simple. All variations deactivated.');
    }

    /**
     * Cancelled type change: reverts form.type back to variable.
     */
    public function cancelTypeChange(): void
    {
        $this->form->type = 'variable';
        $this->pendingProductType = '';
        $this->showTypeChangeModal = false;
    }

    // =========================================================================
    // DEFAULT VARIATION SELECTION
    // =========================================================================

    /**
     * Fires when any defaultVariantAttributes dropdown changes.
     * Delegates to applyDefaultVariantSelection() which matches the combination.
     */
    public function updatedDefaultVariantAttributes(): void
    {
        $this->applyDefaultVariantSelection();
    }

    /**
     * Finds the variant matching the current defaultVariantAttributes selection
     * and sets it as the default, clearing is_default on all others.
     * Requires all variation attributes to have a selected value before running.
     */
    private function applyDefaultVariantSelection(): void
    {
        // Wait until every attribute has a value selected
        if (count($this->defaultVariantAttributes) !== count($this->availableAttributes)) {
            return;
        }

        $matched = null;

        foreach ($this->variants as $index => $variant) {
            $isMatch = true;

            foreach ($this->defaultVariantAttributes as $attrName => $attrValue) {
                if (($variant['attributes'][$attrName] ?? null) !== $attrValue) {
                    $isMatch = false;
                    break;
                }
            }

            $this->variants[$index]['is_default'] = $isMatch;

            if ($isMatch) {
                $matched = $index;
            }
        }

        if ($matched !== null) {
            $this->dispatch('notify', title: 'Default Variation', variant: 'success', message: 'Default variation updated.');
        } else {
            $this->dispatch('notify', title: 'No Match Found', variant: 'warning', message: 'No variation matches this combination.');
        }
    }

    // =========================================================================
    // ATTRIBUTES
    // =========================================================================

    /**
     * Loads the product's existing attributes into selectedAttributes state
     * and rebuilds availableAttributes (variation-only subset).
     */
    protected function loadProductAttributes(Product $product): void
    {
        $this->selectedAttributes = $product
            ->attributes()
            ->withPivot(['is_visible', 'is_variation_attribute', 'sort_order', 'values'])
            ->get()
            ->map(fn ($attr) => [
                'attribute_id' => $attr->id,
                'name' => $attr->name,
                'is_new' => false,
                'is_visible' => (bool) $attr->pivot->is_visible,
                'is_variation_attribute' => (bool) $attr->pivot->is_variation_attribute,
                'sort_order' => $attr->pivot->sort_order,
                'values' => json_decode($attr->pivot->values ?? '[]', true) ?? [],
            ])
            ->toArray();

        $this->syncAvailableAttributes();
    }

    /** Appends a blank new attribute row to the attributes list. */
    public function addNewAttribute(): void
    {
        $this->selectedAttributes[] = [
            'attribute_id' => null,
            'name' => '',
            'is_new' => true,
            'is_visible' => true,
            'is_variation_attribute' => false,
            'sort_order' => count($this->selectedAttributes),
            'values' => '',
        ];
    }

    /**
     * Adds an existing global attribute to the product's attribute list.
     * Prevents duplicates and dispatches a warning if already added.
     */
    public function addExistingAttribute($attributeId): void
    {
        if (! $attributeId) {
            return;
        }

        $already = collect($this->selectedAttributes)
            ->pluck('attribute_id')
            ->contains((int) $attributeId);

        if ($already) {
            $this->dispatch('notify', title: 'Duplicate Attribute', variant: 'warning', message: 'Attribute already added.');

            return;
        }

        $attribute = Attribute::find($attributeId);
        if (! $attribute) {
            return;
        }

        $this->selectedAttributes[] = [
            'attribute_id' => $attribute->id,
            'name' => $attribute->name,
            'is_new' => false,
            'is_visible' => true,
            'is_variation_attribute' => false,
            'sort_order' => count($this->selectedAttributes),
            'values' => [],
        ];

        $this->syncAvailableAttributes();
    }

    /** Removes an attribute row by index and re-indexes the array. */
    public function removeSelectedAttribute(int $index): void
    {
        array_splice($this->selectedAttributes, $index, 1);
        $this->selectedAttributes = array_values($this->selectedAttributes);
        $this->syncAvailableAttributes();
    }

    /**
     * Called by Livewire when selectedAttributes changes.
     * Busts the productAttributeValueOptions computed cache
     * and re-syncs availableAttributes.
     */
    public function updatedSelectedAttributes(): void
    {
        unset($this->productAttributeValueOptions);
        $this->syncAvailableAttributes();
    }

    /**
     * Returns pre-loaded attribute values for a given attribute ID.
     * Reads from the productAttributeValueOptions computed property
     * to avoid N+1 queries in Blade loops.
     */
    public function getProductAttributeValues(int $attributeId): array
    {
        return $this->productAttributeValueOptions[$attributeId] ?? [];
    }

    /**
     * Saves the current selectedAttributes to the DB via ProductAttributeService.
     * Validates that all attributes have a name and at least one value before saving.
     */
    public function saveAttributes(): void
    {
        $productId = $this->form->getProductId();

        if (! $productId) {
            $this->dispatch('notify', title: 'Note', variant: 'info', message: 'Attributes will be saved when you create the product.');

            return;
        }

        foreach ($this->selectedAttributes as $attr) {
            if (empty(trim($attr['name']))) {
                $this->dispatch('notify', title: 'Validation', variant: 'warning', message: 'All attributes must have a name.');

                return;
            }

            $hasValues = is_array($attr['values'])
                ? ! empty($attr['values'])
                : ! empty(trim($attr['values']));

            if (! $hasValues) {
                $this->dispatch('notify', title: 'Validation', variant: 'warning', message: "Attribute \"{$attr['name']}\" must have at least one value.");

                return;
            }
        }

        app(ProductAttributeService::class)->save(
            Product::findOrFail($productId),
            $this->selectedAttributes
        );

        $this->syncAvailableAttributes();
        $this->dispatch('notify', title: 'Attributes Saved', variant: 'success', message: 'Attributes saved.');
    }

    /**
     * Rebuilds availableAttributes as the subset of selectedAttributes
     * where is_variation_attribute = true.
     * Called whenever selectedAttributes changes.
     */
    private function syncAvailableAttributes(): void
    {
        $this->availableAttributes = collect($this->selectedAttributes)
            ->filter(fn ($a) => $a['is_variation_attribute'])
            ->values()
            ->toArray();
    }

    // =========================================================================
    // VARIATIONS — LOADING
    // =========================================================================

    /**
     * Loads all active variants for the product into the variants state array.
     * Includes attribute values, images, and per-variant downloads.
     * Also pre-selects defaultVariantAttributes from the default variant.
     */
    protected function loadProductVariants(Product $product): void
    {
        $this->variants = $product
            ->variants()
            ->with([
                'attributeValues:id,attribute_id,value',
                'attributeValues.attribute:id,name',
                'downloads',
            ])
            ->get()
            ->map(fn ($variant) => [
                'id' => $variant->id,
                'attribute_hash' => $variant->attribute_hash,   // ← ADD THIS
                'name' => $variant->name,
                'sku' => $variant->sku,
                'image_path' => $variant->image_path,
                'image' => null,
                'price' => $variant->price,
                'sale_price' => $variant->sale_price,
                'manage_stock' => $variant->manage_stock,
                'stock_quantity' => $variant->stock_quantity,
                'stock_status' => $variant->stock_status,
                'allow_backorders' => $variant->allow_backorders ? '1' : '',
                'max_backorder_quantity' => $variant->max_backorder_quantity,
                'expected_restock_date' => $variant->expected_restock_date?->format('Y-m-d'),
                'backorder_message' => $variant->backorder_message,
                'low_stock_threshold' => $variant->low_stock_threshold,
                'weight' => $variant->weight,
                'length' => $variant->length,
                'width' => $variant->width,
                'height' => $variant->height,
                'description' => $variant->description,
                'is_active' => $variant->is_active,
                'is_default' => $variant->is_default,
                'attributes' => $variant->attributeValues
                    ->mapWithKeys(fn ($av) => [$av->attribute->name => $av->value])
                    ->toArray(),
                'attribute_value_ids' => $variant->attributeValues->pluck('id')->toArray(),
                'attribute_hash' => $variant->attribute_hash,
                'downloads' => $variant->downloads->map(fn ($d) => [
                    'id' => $d->id,
                    'name' => $d->name,
                    'file' => null,
                    'file_path' => $d->file_path,
                    'file_name' => $d->file_name,
                    'file_type' => $d->file_type,
                    'file_size' => $d->file_size,
                    'formatted_file_size' => $d->formatted_file_size,
                ])->toArray(),
                'downloads_to_delete' => [],
            ])
            ->toArray();

        // Pre-populate the Default Variation dropdowns from the saved default variant
        $defaultVariant = collect($this->variants)->firstWhere('is_default', true)
            ?? collect($this->variants)->first();

        if ($defaultVariant) {
            $this->defaultVariantAttributes = $defaultVariant['attributes'];
        }
    }

    // =========================================================================
    // VARIATIONS — GENERATION
    // =========================================================================

    /**
     * Generates all missing variant combinations from the current variation attributes.
     * Uses a Cartesian product of attribute values and skips existing hashes.
     * New variants are appended to the variants array without affecting existing ones.
     */
    public function generateVariations(): void
    {
        $variationAttributes = collect($this->availableAttributes)
            ->filter(fn ($a) => $a['is_variation_attribute'])
            ->values();

        if ($variationAttributes->isEmpty()) {
            $this->dispatch('notify', title: 'No Variation Attributes', variant: 'warning', message: 'No attributes marked as "Used for variations".');

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

            $attributeValueGroups[] = $values->map(fn ($v) => [
                'attribute_name' => $attr['name'],
                'value' => $v->value,
                'value_id' => $v->id,
            ])->toArray();
        }

        if (empty($attributeValueGroups)) {
            $this->dispatch('notify', title: 'Missing Values', variant: 'warning', message: 'Please select values for your variation attributes.');

            return;
        }

        $combinations = $this->cartesian($attributeValueGroups);
        $existingHashes = collect($this->variants)->pluck('attribute_hash')->toArray();
        $newCount = 0;

        foreach ($combinations as $combination) {
            $valueIds = collect($combination)->pluck('value_id')->sort()->toArray();
            $hash = md5(implode('-', $valueIds));

            // Skip combinations that already exist
            if (in_array($hash, $existingHashes)) {
                continue;
            }

            $attributes = collect($combination)
                ->mapWithKeys(fn ($c) => [$c['attribute_name'] => $c['value']])
                ->toArray();

            $this->variants[] = $this->blankVariantTemplate($attributes, $valueIds, $hash);
            $newCount++;
        }

        $this->dispatch('notify', title: 'Variations Generated', variant: 'success', message: "{$newCount} new variation(s) generated.");
    }

    /**
     * Re-runs generateVariations() to add any missing combinations
     * without affecting existing variants.
     * Dispatches an info notice if nothing new was added.
     */
    public function regenerateVariations(): void
    {
        $before = count($this->variants);
        $this->generateVariations();
        $after = count($this->variants);

        if ($after === $before) {
            $this->dispatch('notify', title: 'No New Variations', variant: 'info', message: 'All variation combinations already exist. Nothing to add.');
        }
    }

    // =========================================================================
    // VARIATIONS — CRUD
    // =========================================================================

    /**
     * Appends a blank manually-added variant row to the variants array.
     * Uses a manual_ prefixed hash to distinguish from generated variants
     * and prevent false duplicate detection in generateVariations().
     */
    public function addVariant(): void
    {
        $this->variants[] = $this->blankVariantTemplate(
            attributes: [],
            valueIds: [],
            hash: 'manual_'.uniqid('', true)
        );
    }

    /**
     * Removes a variant by index.
     * If the variant has an ID (already saved), queues it for deletion on save.
     */
    public function removeVariant(int $index): void
    {
        $variant = $this->variants[$index];

        if (! empty($variant['id'])) {
            $this->variantsToDelete[] = $variant['id'];
        }

        array_splice($this->variants, $index, 1);
        $this->variants = array_values($this->variants);
    }

    /**
     * Queues all variants for deletion and clears the variants array.
     * Deletions are applied to the DB on next save.
     */
    public function clearAllVariants(): void
    {
        foreach ($this->variants as $variant) {
            if (! empty($variant['id'])) {
                $this->variantsToDelete[] = $variant['id'];
            }
        }

        $this->variants = [];
        $this->dispatch('notify', title: 'Variations Cleared', variant: 'success', message: 'All variations removed. Save to apply.');
    }

    /** Clears the variant image upload for a given index and nulls its stored path. */
    public function removeVariantImage(int $index): void
    {
        $this->variantImages[$index] = null;
        $this->variants[$index]['image_path'] = null;
        $this->variants[$index]['image'] = null;
    }

    // =========================================================================
    // VARIATIONS — BULK ACTIONS
    // =========================================================================

    /** Toggles is_active on all variants simultaneously. */
    public function toggleAllVariantsActive(): void
    {
        foreach ($this->variants as $index => $variant) {
            $this->variants[$index]['is_active'] = ! $variant['is_active'];
        }
    }

    /** Toggles manage_stock on all variants simultaneously. */
    public function toggleAllVariantsManageStock(): void
    {
        foreach ($this->variants as $index => $variant) {
            $this->variants[$index]['manage_stock'] = ! $variant['manage_stock'];
        }
    }

    /** Sets stock_status to the given value on all variants. */
    public function setAllVariantsStockStatus(string $status): void
    {
        foreach ($this->variants as $index => $variant) {
            $this->variants[$index]['stock_status'] = $status;
        }
    }

    /**
     * Applies bulkPrice and/or bulkSalePrice to all variants,
     * then clears the bulk staging fields.
     */
    public function applyBulkPricing(): void
    {
        foreach ($this->variants as $index => $variant) {
            if ($this->bulkPrice !== null) {
                $this->variants[$index]['price'] = $this->bulkPrice;
            }
        }

        $this->bulkPrice = null;
        Flux::modal('bulk-pricing')->close();
        $this->dispatch('notify', title: 'Bulk Pricing Applied', variant: 'success', message: 'Pricing applied to all variations.');
    }

    /**
     * Applies bulkStockQuantity to all variants,
     * then clears the bulk staging field.
     */
    public function applyBulkStock(): void
    {
        foreach ($this->variants as $index => $variant) {
            if ($this->bulkStockQuantity !== null) {
                $this->variants[$index]['stock_quantity'] = $this->bulkStockQuantity;
            }
        }

        $this->bulkStockQuantity = null;
        Flux::modal('bulk-stock"')->close();
        $this->dispatch('notify', title: 'Bulk Stock Applied', variant: 'success', message: 'Stock applied to all variations.');
    }

    /**
     * Applies bulk weight and dimensions to all variants,
     * then clears the bulk staging fields.
     */
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
        Flux::modal('bulk-dimensions')->close();
        $this->dispatch('notify', title: 'Bulk Dimensions Applied', variant: 'success', message: 'Dimensions applied to all variations.');
    }

    // =========================================================================
    // VARIATIONS — DOWNLOADS
    // =========================================================================

    /** Appends a blank download row to a specific variant's downloads array. */
    public function addVariantDownloadFile(int $variantIndex): void
    {
        $this->variants[$variantIndex]['downloads'][] = [
            'id' => null,
            'name' => '',
            'file' => null,
            'file_path' => null,
            'file_name' => null,
            'file_type' => null,
            'file_size' => null,
        ];
    }

    /**
     * Removes a download row from a specific variant.
     * If the row has a saved ID, queues it for deletion in downloads_to_delete.
     */
    public function removeVariantDownloadFile(int $variantIndex, int $downloadIndex): void
    {
        $download = $this->variants[$variantIndex]['downloads'][$downloadIndex];

        if (! empty($download['id'])) {
            $this->variants[$variantIndex]['downloads_to_delete'][] = $download['id'];
        }

        array_splice($this->variants[$variantIndex]['downloads'], $downloadIndex, 1);
        $this->variants[$variantIndex]['downloads'] = array_values(
            $this->variants[$variantIndex]['downloads']
        );
    }

    // =========================================================================
    // GROUPED PRODUCTS
    // =========================================================================

    /**
     * Loads grouped products (kit items) for the product into component state.
     * Reads from the groupedProducts() relationship with pivot quantity.
     */
    protected function loadGroupedProducts(Product $product): void
    {
        $this->groupedProducts = $product
            ->groupedProducts()
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'price' => $p->price,
                'quantity' => $p->pivot->quantity,
            ])
            ->toArray();
    }

    /**
     * Adds the selected products from the staging array to the kit.
     * Prevents duplicates and notifies on success.
     */
    public function addGroupedProducts(): void
    {
        if (empty($this->selectedGroupedProducts)) {
            return;
        }

        $existingIds = collect($this->groupedProducts)->pluck('id')->toArray();
        $newIds = array_filter(
            $this->selectedGroupedProducts,
            fn ($id) => ! in_array($id, $existingIds)
        );

        if (empty($newIds)) {
            $this->dispatch('notify', title: 'Already Added', variant: 'warning', message: 'Selected products are already in the kit.');

            return;
        }

        $products = Product::whereIn('id', $newIds)->select('id', 'name', 'sku', 'price')->get();

        foreach ($products as $product) {
            $this->groupedProducts[] = [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => $product->price,
                'quantity' => 1,
            ];
        }

        $this->selectedGroupedProducts = [];
        $this->dispatch('notify', title: 'Products Added', variant: 'success', message: count($products).' product(s) added to kit.');
    }

    /** Removes a grouped product from the kit by index. */
    public function removeGroupedProduct(int $index): void
    {
        array_splice($this->groupedProducts, $index, 1);
        $this->groupedProducts = array_values($this->groupedProducts);
    }

    /** Calculates the total price of all items in the grouped product kit. */
    public function getGroupedTotal(): float
    {
        return collect($this->groupedProducts)
            ->sum(fn ($item) => ($item['price'] ?? 0) * ($item['quantity'] ?? 1));
    }

    // =========================================================================
    // ACCESSORIES
    // =========================================================================

    /**
     * Loads accessories linked to the product into component state.
     * Reads from the accessories() relationship with pivot quantity.
     */
    protected function loadAccessories(Product $product): void
    {
        $this->accessories = $product
            ->accessories()
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'price' => $p->price,
                'quantity' => $p->pivot->quantity,
            ])
            ->toArray();
    }

    /**
     * Adds the selected accessories from the staging array.
     * Prevents duplicates and notifies on success.
     */
    public function addAccessories(): void
    {
        if (empty($this->selectedAccessories)) {
            return;
        }

        $existingIds = collect($this->accessories)->pluck('id')->toArray();
        $newIds = array_filter(
            $this->selectedAccessories,
            fn ($id) => ! in_array($id, $existingIds)
        );

        if (empty($newIds)) {
            $this->dispatch('notify', title: 'Already Added', variant: 'warning', message: 'Selected products are already added as accessories.');

            return;
        }

        $products = Product::whereIn('id', $newIds)->select('id', 'name', 'sku', 'price')->get();

        foreach ($products as $product) {
            $this->accessories[] = [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => $product->price,
                'quantity' => 1,
            ];
        }

        $this->selectedAccessories = [];
        $this->dispatch('notify', title: 'Accessories Added', variant: 'success', message: count($products).' accessory(s) added.');
    }

    /** Removes an accessory by index. */
    public function removeAccessory(int $index): void
    {
        array_splice($this->accessories, $index, 1);
        $this->accessories = array_values($this->accessories);
    }

    // =========================================================================
    // PRODUCT-LEVEL DOWNLOADS
    // =========================================================================

    /**
     * Loads product-level download files (variant_id = null) into form state.
     * Variant-level downloads are loaded separately in loadProductVariants().
     */
    protected function loadProductDownloads(Product $product): void
    {
        $this->form->downloads = $product->downloads()
            ->whereNull('variant_id')
            ->get()
            ->map(fn ($download) => [
                'id' => $download->id,
                'name' => $download->name,
                'file' => null,
                'file_path' => $download->file_path,
                'file_name' => $download->file_name,
                'file_type' => $download->file_type,
                'file_size' => $download->file_size,
                'formatted_file_size' => $download->formatted_file_size,
            ])
            ->toArray();
    }

    /** Appends a blank download row to the product-level downloads list. */
    public function addDownloadFile(): void
    {
        $this->form->downloads[] = [
            'id' => null,
            'name' => '',
            'file' => null,
            'file_path' => null,
            'file_name' => null,
            'file_type' => null,
            'file_size' => null,
        ];
    }

    /**
     * Removes a product-level download row by index.
     * If the row has a saved ID, queues it for deletion.
     */
    public function removeDownloadFile(int $index): void
    {
        if (! empty($this->form->downloads[$index]['id'])) {
            $this->downloadsToDelete[] = $this->form->downloads[$index]['id'];
        }

        array_splice($this->form->downloads, $index, 1);
        $this->form->downloads = array_values($this->form->downloads);
    }

    /** Clears the uploaded file from a download row without removing the row itself. */
    public function clearDownloadFile(int $index): void
    {
        $this->form->downloads[$index]['file'] = null;
    }

    // =========================================================================
    // IMAGES
    // =========================================================================

    /**
     * Marks an existing gallery image for deletion on next save
     * and removes it from the existingImages display list immediately.
     */
    public function removeGalleryImage(int $imageId): void
    {
        if (! in_array($imageId, $this->form->imagesToDelete)) {
            $this->form->imagesToDelete[] = $imageId;
        }

        $this->form->existingImages = array_values(
            array_filter(
                $this->form->existingImages,
                fn ($img) => $img['id'] !== $imageId
            )
        );
    }

    /**
     * Removes a newly uploaded gallery image that has not yet been saved.
     * Operates on the form->images staging array.
     */
    public function removeNewImage(int $index): void
    {
        unset($this->form->images[$index]);
        $this->form->images = array_values($this->form->images);
    }

    // =========================================================================
    // TAGS
    // =========================================================================

    /** Parses form->newTagInput and adds each tag to the selection. */
    public function addTags(): void
    {
        $this->form->addTags();
        unset($this->selectedTags);
    }

    /** Removes a tag from the selection by ID. */
    public function removeTag(int $tagId): void
    {
        $this->form->removeTag($tagId);
        unset($this->selectedTags);
    }

    // =========================================================================
    // CATEGORIES
    // =========================================================================

    /** Creates a new category and adds it to the selection, then resets the form. */
    public function createCategory(): void
    {
        $this->form->createCategory();
        $this->addNewCategory = false;
        unset($this->categories);
    }

    /** Cancels category creation and resets the inline form fields. */
    public function cancelCategoryCreation(): void
    {
        $this->form->resetCategoryForm();
        $this->addNewCategory = false;
    }

    // =========================================================================
    // BRANDS
    // =========================================================================

    /** Creates a new brand and selects it, then resets the form. */
    public function createBrand(): void
    {
        $this->form->createBrand();
        $this->addNewBrand = false;
        unset($this->brands);
    }

    /** Cancels brand creation and resets the inline form fields. */
    public function cancelBrandCreation(): void
    {
        $this->form->resetBrandForm();
        $this->addNewBrand = false;
    }

    // =========================================================================
    // TAB ERROR INDICATORS
    // =========================================================================

    /** Returns true if any General tab field has a validation error. */
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

    /** Returns true if any Inventory tab field has a validation error. */
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

    /** Returns true if any Shipping tab field has a validation error. */
    public function hasShippingErrors(): bool
    {
        return $this->getErrorBag()->hasAny([
            'form.weight',
            'form.length',
            'form.width',
            'form.height',
            'form.warranty_information',
            'form.return_policy',
            'form.shipping_information',
        ]);
    }

    /** Returns true if any Linked Products tab field has a validation error. */
    public function hasLinkedProductsErrors(): bool
    {
        return $this->getErrorBag()->hasAny([
            'form.selected_upsells',
            'form.selected_cross_sells',
            'form.accessories',
            'form.grouped_products',
        ]);
    }

    /** Returns true if any Attributes tab field has a validation error. */
    public function hasAttributesErrors(): bool
    {
        return $this->getErrorBag()->hasAny([
            'selectedAttributes',
            'selectedAttributes.*',
            'selectedAttributes.*.name',
            'selectedAttributes.*.values',
        ]);
    }

    /** Returns true if any Variations tab field has a validation error. */
    public function hasVariationsErrors(): bool
    {
        return $this->getErrorBag()->hasAny([
            'variants',
            'variants.*',
            'variants.*.price',
            'variants.*.sku',
            'variants.*.stock_quantity',
        ]);
    }

    /** Returns true if any Advanced tab field has a validation error. */
    public function hasAdvancedErrors(): bool
    {
        return $this->getErrorBag()->hasAny([
            'form.sort_order',
            'form.reviews_enabled',
            'form.purchase_note',
            'form.requires_quotation',
            'form.min_order_quantity',
            'form.quotation_notes',
        ]);
    }

    // =========================================================================
    // COMPUTED PROPERTIES
    // =========================================================================

    /**
     * All active global attributes, ordered by sort_order.
     * Persisted across requests since attributes rarely change mid-session.
     */
    #[Computed(persist: true)]
    public function productAttributes()
    {
        return Attribute::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Only loads already-selected products for initial display.
     * Search is handled via searchProducts() method for AJAX.
     */
    #[Computed]
    public function products()
    {
        $selectedIds = collect([
            ...$this->form->selected_upsells,
            ...$this->form->selected_cross_sells,
            ...collect($this->accessories)->pluck('id'),
            ...collect($this->groupedProducts)->pluck('id'),
        ])->filter()->unique()->values()->toArray();

        if (empty($selectedIds)) {
            return collect();
        }

        return Product::active()
            ->select('id', 'name', 'sku', 'image_path')
            ->whereIn('id', $selectedIds)
            ->orderBy('name')
            ->get()
            ->map(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'image_url' => $product->image_url,
            ]);
    }

    /**
     * AJAX search for products - used by x-my-choices component.
     * Returns max 20 results to keep response fast.
     */
    public function searchProducts(string $search = '')
    {
        $currentId = $this->form->getProductId();

        return Product::active()
            ->select('id', 'name', 'sku', 'image_path')
            ->when($currentId, fn ($q) => $q->where('id', '!=', $currentId))
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'image_url' => $product->image_url,
            ]);
    }

    /**
     * All active brands for the brand selector.
     * Persisted — busted explicitly after createBrand().
     */
    #[Computed(persist: true)]
    public function brands()
    {
        return Brand::active()->ordered()->select('id', 'name')->get();
    }

    /**
     * All tax classes for the tax class selector.
     */
    #[Computed]
    public function taxClasses()
    {
        return \App\Models\TaxClass::orderBy('name')->select('id', 'name', 'rate')->get();
    }

    /**
     * Flattened category tree for the category checkbox list.
     * Persisted — busted explicitly after createCategory().
     */
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

    /** All active categories (unflattened) for the parent category selector. */
    #[Computed]
    public function allCategories()
    {
        return Category::active()->orderBy('name')->get();
    }

    /** The currently selected tags as a Tag collection, for display in the tag badges. */
    #[Computed]
    public function selectedTags()
    {
        return $this->form->getSelectedTags();
    }

    /**
     * Top 20 most-used tags for the "most used tags" quick-pick.
     * Not persisted — tag counts change as products are created.
     */
    #[Computed]
    public function mostUsedTags()
    {
        return Tag::withCount('products')
            ->orderByDesc('products_count')
            ->limit(20)
            ->get();
    }

    /**
     * All attribute values for the currently selected attributes,
     * grouped by attribute_id to avoid N+1 queries in Blade loops.
     * Busted when selectedAttributes changes via updatedSelectedAttributes().
     */
    #[Computed]
    public function productAttributeValueOptions(): array
    {
        $ids = collect($this->selectedAttributes)
            ->pluck('attribute_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($ids)) {
            return [];
        }

        return AttributeValue::whereIn('attribute_id', $ids)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('attribute_id')
            ->map(fn ($values) => $values->map(fn ($v) => [
                'id' => $v->id,
                'name' => $v->label ?: $v->value,
            ])->toArray())
            ->toArray();
    }

    /**
     * Count of active variants that are missing a regular price.
     * Used to drive the "X variations do not have a price" alert banner.
     */
    #[Computed]
    public function unpricedVariantsCount(): int
    {
        return collect($this->variants)
            ->filter(fn ($v) => $v['is_active'] && (is_null($v['price']) || $v['price'] === ''))
            ->count();
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Returns a blank variant array template with sensible defaults.
     * Used by both addVariant() and generateVariations() to ensure
     * all variant rows have a consistent structure including downloads.
     *
     * @param  array  $attributes  Key-value map of attribute name => value
     * @param  array  $valueIds  Attribute value IDs for hash and sync
     * @param  string  $hash  Unique hash — md5 of sorted value IDs for generated,
     *                        manual_ prefixed uniqid for manually added variants
     */
    private function blankVariantTemplate(array $attributes, array $valueIds, string $hash): array
    {
        return [
            'id' => null,
            'name' => empty($attributes)
                ? null
                : implode(' - ', array_values($attributes)),
            'sku' => null,
            'image' => null,
            'image_path' => null,
            'price' => null,
            'sale_price' => null,
            'manage_stock' => true,
            'stock_quantity' => 0,
            'stock_status' => 'in_stock',
            'allow_backorders' => null,
            'low_stock_threshold' => null,
            'max_backorder_quantity' => null,
            'expected_restock_date' => null,
            'backorder_message' => null,
            'weight' => null,
            'length' => null,
            'width' => null,
            'height' => null,
            'description' => null,
            'is_active' => true,
            'is_default' => false,
            'attributes' => $attributes,
            'attribute_value_ids' => $valueIds,
            'attribute_hash' => $hash,
            'downloads' => [],
            'downloads_to_delete' => [],
        ];
    }

    /**
     * Recursively flattens a nested category tree into a flat array
     * with a depth indicator for use in indented checkbox lists.
     *
     * @param  Collection  $categories  Root-level categories with loaded children
     * @param  int  $depth  Current nesting depth (0 = root)
     * @return array Flat array of [id, name, depth]
     */
    protected function flattenCategories($categories, int $depth = 0): array
    {
        $result = [];

        foreach ($categories as $category) {
            $result[] = [
                'id' => $category->id,
                'name' => $category->name,
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

    /**
     * Computes the Cartesian product of multiple arrays.
     * Used by generateVariations() to produce all attribute value combinations.
     *
     * Example: [[S, M], [Red, Blue]] → [[S,Red], [S,Blue], [M,Red], [M,Blue]]
     *
     * @param  array  $arrays  Array of arrays to combine
     * @return array All possible combinations
     */
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
