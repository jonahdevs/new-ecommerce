<?php

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Enums\ProductVisibility;
use App\Enums\StockStatus;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\BundleItem;
use App\Models\Category;
use App\Models\DownloadableFile;
use App\Models\GroupedProductItem;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Spatie\Tags\Tag;

new #[Layout('layouts::app')] class extends Component
{
    use WithFileUploads;

    // ─── Identity ──────────────────────────────────────────────────────────────
    public ?int $productId = null;

    public string $name = '';

    public string $slug = '';

    public string $model_number = '';

    public string $type = 'simple';

    // ─── Content ───────────────────────────────────────────────────────────────
    public string $short_description = '';

    public string $description = '';

    public string $technical_specification = '';

    // ─── Organisation (sidebar) ────────────────────────────────────────────────
    public ?int $brand_id = null;

    public ?int $primary_category_id = null;

    // ─── Pricing ───────────────────────────────────────────────────────────────
    public ?float $price = null;

    public ?float $sale_price = null;

    public ?float $cost_price = null;

    public bool $is_taxable = true;

    // ─── Inventory ─────────────────────────────────────────────────────────────
    public string $sku = '';

    public string $stock_status = 'in_stock';

    public ?int $stock_quantity = null;

    public bool $allow_backorder = false;

    public ?int $low_stock_threshold = null;

    public ?int $min_order_quantity = null;

    // ─── Shipping ──────────────────────────────────────────────────────────────
    public bool $requires_shipping = true;

    public ?float $weight = null;

    public ?float $length = null;

    public ?float $width = null;

    public ?float $height = null;

    // ─── Status & Visibility (sidebar) ────────────────────────────────────────
    public string $status = 'draft';

    public string $published_at = '';

    public string $visibility = 'visible';

    public int $sort_order = 0;

    // ─── Advanced / B2B ────────────────────────────────────────────────────────
    public bool $requires_quotation = false;

    public string $quotation_notes = '';

    // ─── SEO ───────────────────────────────────────────────────────────────────
    public string $meta_title = '';

    public string $meta_description = '';

    public string $canonical_url = '';

    // ─── Attributes (edit mode) ────────────────────────────────────────────────
    /**
     * @var array<int, array{attribute_id: ?int, name: string, values_string: string, is_visible: bool, is_variation_attribute: bool, collapsed: bool}>
     */
    public array $selectedAttributes = [];

    // ─── Variants (edit mode) ──────────────────────────────────────────────────
    /**
     * @var array<int, array{id: ?int, sku: string, price: ?float, compare_at_price: ?float, cost_price: ?float, stock_status: string, stock_quantity: ?int, allow_backorder: bool, manage_stock: bool, weight: ?float, length: ?float, width: ?float, height: ?float, description: string, image_path: ?string, image_url: ?string, is_active: bool, is_default: bool, label: string, collapsed: bool}>
     */
    public array $variants = [];

    /** @var array<int, string> Checked variant indexes for bulk actions */
    public array $selectedVariantIndexes = [];

    /** @var array<int, TemporaryUploadedFile|null> */
    public array $pendingVariantImages = [];

    /**
     * Per-attribute default values used to pre-fill the label when adding a manual variant.
     *
     * @var array<string, string> key = attribute name, value = selected default value
     */
    public array $defaultVariantFormValues = [];

    // ─── Bulk edit modal state ─────────────────────────────────────────────────
    public string $bulkEditField = '';

    public ?float $bulkEditNumericValue = null;

    public string $bulkEditSelectValue = '';

    // ─── Downloadable Files (edit mode) ────────────────────────────────────────
    /**
     * @var array<int, array{id: ?int, name: string, download_limit: ?int, download_expiry_days: ?int, version: string}>
     */
    public array $downloadableFiles = [];

    // ─── Linked Products (edit mode) ───────────────────────────────────────────
    /**
     * @var array<int, array{product_id: int, name: string, sku: ?string, quantity: int, is_optional: bool, price_override: ?float}>
     */
    public array $linkedProducts = [];

    public string $linkedProductSearch = '';

    // ─── Tags (sidebar) ────────────────────────────────────────────────────────
    /**
     * @var array<int, array{id: int, name: string}>
     */
    public array $selectedTags = [];

    public string $tagSearch = '';

    // ─── Images (sidebar) ──────────────────────────────────────────────────────
    /** Pending cover image upload (Livewire temp file). */
    public $pendingCoverImage = null;

    /** Existing cover image record. */
    public ?array $coverImage = null;

    /** Pending gallery uploads (Livewire temp files). */
    public array $pendingGalleryImages = [];

    /** Existing gallery image records. */
    public array $galleryImages = [];

    private bool $slugManuallyEdited = false;

    public function mount(?Product $product = null): void
    {
        if (! $product) {
            return;
        }

        $product->load(['productAttributes.attribute.values', 'variants.attributeValues', 'downloadableFiles', 'tags', 'images']);

        $this->productId = $product->id;
        $this->name = $product->name;
        $this->slug = $product->slug;
        $this->sku = (string) $product->sku;
        $this->model_number = (string) $product->model_number;
        $this->type = $product->type->value;
        $this->short_description = (string) $product->short_description;
        $this->description = (string) $product->description;
        $this->technical_specification = (string) $product->technical_specification;
        $this->brand_id = $product->brand_id;
        $this->primary_category_id = $product->primary_category_id;
        $this->price = $product->price ? round($product->price / 100, 2) : null;
        $this->sale_price = $product->sale_price ? round($product->sale_price / 100, 2) : null;
        $this->cost_price = $product->cost_price ? round($product->cost_price / 100, 2) : null;
        $this->is_taxable = (bool) $product->is_taxable;
        $this->stock_status = $product->stock_status->value;
        $this->stock_quantity = $product->stock_quantity;
        $this->allow_backorder = (bool) $product->allow_backorder;
        $this->low_stock_threshold = $product->low_stock_threshold;
        $this->min_order_quantity = $product->min_order_quantity;
        $this->requires_shipping = (bool) $product->requires_shipping;
        $this->weight = $product->weight ? (float) $product->weight : null;
        $this->length = $product->length ? (float) $product->length : null;
        $this->width = $product->width ? (float) $product->width : null;
        $this->height = $product->height ? (float) $product->height : null;
        $this->status = $product->status->value;
        $this->published_at = $product->published_at?->format('Y-m-d\TH:i') ?? '';
        $this->visibility = $product->visibility->value;
        $this->sort_order = (int) $product->sort_order;
        $this->requires_quotation = (bool) $product->requires_quotation;
        $this->quotation_notes = (string) $product->quotation_notes;
        $this->meta_title = (string) $product->meta_title;
        $this->meta_description = (string) $product->meta_description;
        $this->canonical_url = (string) $product->canonical_url;
        $this->slugManuallyEdited = true;

        // Attributes
        $this->selectedAttributes = $product->productAttributes
            ->map(function ($pa) {
                $storedValues = $pa->values ?? [];
                // If it's an existing attribute with value IDs, resolve to labels
                if ($pa->attribute_id && ! empty($storedValues) && is_numeric($storedValues[0] ?? null)) {
                    $labels = $pa->attribute->values
                        ->whereIn('id', $storedValues)
                        ->map(fn ($v) => $v->label ?: $v->value)
                        ->values()
                        ->all();
                    $valuesString = implode(' | ', $labels);
                } else {
                    $valuesString = implode(' | ', $storedValues);
                }

                return [
                    'attribute_id' => $pa->attribute_id,
                    'name' => $pa->attribute->name,
                    'values_string' => $valuesString,
                    'is_visible' => (bool) $pa->is_visible,
                    'is_variation_attribute' => (bool) $pa->is_variation_attribute,
                    'collapsed' => true,
                ];
            })->all();

        // Variants
        $this->variants = $product->variants
            ->map(fn ($v) => [
                'id' => $v->id,
                'sku' => (string) $v->sku,
                'price' => $v->price ? round($v->price / 100, 2) : null,
                'compare_at_price' => $v->compare_at_price ? round($v->compare_at_price / 100, 2) : null,
                'cost_price' => $v->cost_price ? round($v->cost_price / 100, 2) : null,
                'stock_status' => $v->stock_status->value,
                'stock_quantity' => $v->stock_quantity,
                'allow_backorder' => (bool) $v->allow_backorder,
                'manage_stock' => $v->stock_quantity !== null,
                'weight' => $v->weight ? (float) $v->weight : null,
                'length' => $v->length ? (float) $v->length : null,
                'width' => $v->width ? (float) $v->width : null,
                'height' => $v->height ? (float) $v->height : null,
                'description' => (string) $v->description,
                'image_path' => $v->image,
                'image_url' => $v->image ? Storage::disk('public')->url($v->image) : null,
                'is_active' => (bool) $v->is_active,
                'is_default' => $v->id === $product->default_variant_id,
                'label' => $v->attributeValues->map(fn ($av) => $av->label ?: $av->value)->join(' / '),
                'collapsed' => true,
            ])->all();

        // Default form values — one entry per variation attribute, initialised to empty
        $this->defaultVariantFormValues = collect($this->selectedAttributes)
            ->filter(fn ($a) => $a['is_variation_attribute'])
            ->mapWithKeys(fn ($a) => [$a['name'] => ''])
            ->all();

        // Downloadable files
        $this->downloadableFiles = $product->downloadableFiles
            ->map(fn ($f) => [
                'id' => $f->id,
                'name' => $f->name,
                'download_limit' => $f->download_limit,
                'download_expiry_days' => $f->download_expiry_days,
                'version' => (string) $f->version,
            ])->all();

        // Linked products
        if ($product->type === ProductType::GROUPED) {
            $this->linkedProducts = GroupedProductItem::where('group_product_id', $product->id)
                ->with('child')
                ->get()
                ->map(fn ($gi) => [
                    'product_id' => $gi->child_product_id,
                    'name' => $gi->child->name,
                    'sku' => $gi->child->sku,
                    'quantity' => 1,
                    'is_optional' => false,
                    'price_override' => null,
                ])->all();
        } elseif ($product->type === ProductType::BUNDLE) {
            $this->linkedProducts = $product->bundleItems()
                ->with('product')
                ->get()
                ->map(fn ($bi) => [
                    'product_id' => $bi->product_id,
                    'name' => $bi->product->name,
                    'sku' => $bi->product->sku,
                    'quantity' => $bi->quantity,
                    'is_optional' => (bool) $bi->is_optional,
                    'price_override' => $bi->price_override ? round($bi->price_override / 100, 2) : null,
                ])->all();
        }

        // Tags
        $this->selectedTags = $product->tags
            ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])
            ->all();

        // Images
        $cover = $product->images->firstWhere('is_cover', true);
        $this->coverImage = $cover ? ['id' => $cover->id, 'url' => $cover->url, 'alt' => (string) $cover->alt] : null;

        $this->galleryImages = $product->images
            ->where('is_cover', false)
            ->map(fn ($img) => ['id' => $img->id, 'url' => $img->url, 'alt' => (string) $img->alt])
            ->values()
            ->all();
    }

    // ==================================================
    // SLUG
    // ==================================================

    public function updatedName(): void
    {
        if (! $this->slugManuallyEdited) {
            $this->slug = Str::slug($this->name);
        }
    }

    public function updatedSlug(): void
    {
        $this->slugManuallyEdited = true;
        $this->slug = Str::slug($this->slug);
    }

    // ==================================================
    // ATTRIBUTES
    // ==================================================

    public function addNewAttribute(): void
    {
        $this->selectedAttributes[] = [
            'attribute_id' => null,
            'name' => '',
            'values_string' => '',
            'is_visible' => true,
            'is_variation_attribute' => false,
            'collapsed' => false,
        ];
    }

    public function addExistingAttribute(int $attributeId): void
    {
        if (! $attributeId) {
            return;
        }

        if (collect($this->selectedAttributes)->contains('attribute_id', $attributeId)) {
            return;
        }

        $attr = Attribute::with('values')->find($attributeId);
        if (! $attr) {
            return;
        }

        $valuesString = $attr->values
            ->where('is_active', true)
            ->map(fn ($v) => $v->label ?: $v->value)
            ->join(' | ');

        $this->selectedAttributes[] = [
            'attribute_id' => $attributeId,
            'name' => $attr->name,
            'values_string' => $valuesString,
            'is_visible' => true,
            'is_variation_attribute' => false,
            'collapsed' => false,
        ];

        unset($this->allAttributes);
    }

    public function removeAttribute(int $index): void
    {
        unset($this->selectedAttributes[$index]);
        $this->selectedAttributes = array_values($this->selectedAttributes);
        unset($this->allAttributes);
    }

    public function toggleAttributeCollapsed(int $index): void
    {
        $this->selectedAttributes[$index]['collapsed'] = ! $this->selectedAttributes[$index]['collapsed'];
    }

    // ==================================================
    // VARIANTS
    // ==================================================

    public function generateVariants(): void
    {
        $variationAttributes = collect($this->selectedAttributes)
            ->filter(fn ($a) => $a['is_variation_attribute'] && trim($a['values_string']) !== '');

        if ($variationAttributes->isEmpty()) {
            Flux::toast(heading: 'No variation attributes', text: 'Mark at least one attribute as "Used for variations" and enter its values.', variant: 'warning');

            return;
        }

        $valueSets = $variationAttributes->map(function ($attr) {
            return array_values(array_filter(array_map('trim', explode('|', $attr['values_string']))));
        })->values()->all();

        $combinations = $this->cartesianProduct($valueSets);
        $existingLabels = collect($this->variants)->pluck('label')->all();
        $added = 0;

        foreach ($combinations as $combo) {
            $label = implode(' / ', $combo);

            if (in_array($label, $existingLabels, true)) {
                continue;
            }

            $this->variants[] = [
                'id' => null,
                'sku' => '',
                'price' => null,
                'compare_at_price' => null,
                'cost_price' => null,
                'stock_status' => 'in_stock',
                'stock_quantity' => null,
                'allow_backorder' => false,
                'manage_stock' => false,
                'weight' => null,
                'length' => null,
                'width' => null,
                'height' => null,
                'description' => '',
                'image_path' => null,
                'image_url' => null,
                'is_active' => true,
                'is_default' => false,
                'label' => $label,
                'collapsed' => false,
            ];
            $added++;
        }

        Flux::toast(
            heading: $added > 0 ? "{$added} variation(s) generated" : 'No new variations',
            text: $added > 0 ? 'Fill in SKUs and prices, then save.' : 'All combinations already exist.',
            variant: $added > 0 ? 'success' : 'warning',
        );
    }

    public function addManualVariant(): void
    {
        $defaultLabel = collect($this->defaultVariantFormValues)
            ->filter()
            ->values()
            ->join(' / ');

        $this->variants[] = [
            'id' => null,
            'sku' => '',
            'price' => null,
            'compare_at_price' => null,
            'cost_price' => null,
            'stock_status' => 'in_stock',
            'stock_quantity' => null,
            'allow_backorder' => false,
            'manage_stock' => false,
            'weight' => null,
            'length' => null,
            'width' => null,
            'height' => null,
            'description' => '',
            'image_path' => null,
            'image_url' => null,
            'is_active' => true,
            'is_default' => false,
            'label' => $defaultLabel,
            'collapsed' => false,
        ];
    }

    public function removeVariant(int $index): void
    {
        $imagePath = $this->variants[$index]['image_path'] ?? null;

        if ($imagePath) {
            Storage::disk('public')->delete($imagePath);
        }

        $variantId = $this->variants[$index]['id'] ?? null;

        if ($variantId) {
            ProductVariant::findOrFail($variantId)->delete();
        }

        unset($this->variants[$index], $this->pendingVariantImages[$index]);
        $this->variants = array_values($this->variants);
        $this->pendingVariantImages = array_values($this->pendingVariantImages);
        $this->selectedVariantIndexes = [];
    }

    public function removeVariantImage(int $index): void
    {
        $imagePath = $this->variants[$index]['image_path'] ?? null;

        if ($imagePath) {
            Storage::disk('public')->delete($imagePath);
            $this->variants[$index]['image_path'] = null;
            $this->variants[$index]['image_url'] = null;

            $variantId = $this->variants[$index]['id'] ?? null;

            if ($variantId) {
                ProductVariant::find($variantId)?->update(['image' => null]);
            }
        }

        unset($this->pendingVariantImages[$index]);
    }

    public function toggleVariantCollapsed(int $index): void
    {
        $this->variants[$index]['collapsed'] = ! $this->variants[$index]['collapsed'];
    }

    public function expandAllVariants(): void
    {
        foreach ($this->variants as &$variant) {
            $variant['collapsed'] = false;
        }
    }

    public function collapseAllVariants(): void
    {
        foreach ($this->variants as &$variant) {
            $variant['collapsed'] = true;
        }
    }

    public function setDefaultVariant(int $index): void
    {
        $wasDefault = $this->variants[$index]['is_default'] ?? false;

        foreach ($this->variants as &$variant) {
            $variant['is_default'] = false;
        }
        unset($variant);

        if (! $wasDefault) {
            $this->variants[$index]['is_default'] = true;
        }
    }

    public function toggleSelectAllVariants(): void
    {
        if (count($this->selectedVariantIndexes) === count($this->variants)) {
            $this->selectedVariantIndexes = [];
        } else {
            $this->selectedVariantIndexes = array_map('strval', array_keys($this->variants));
        }
    }

    public function bulkActivateVariants(): void
    {
        foreach ($this->selectedVariantIndexes as $index) {
            $this->variants[(int) $index]['is_active'] = true;
        }
        $this->selectedVariantIndexes = [];
    }

    public function bulkDeactivateVariants(): void
    {
        foreach ($this->selectedVariantIndexes as $index) {
            $this->variants[(int) $index]['is_active'] = false;
        }
        $this->selectedVariantIndexes = [];
    }

    public function openBulkEdit(string $field): void
    {
        if (empty($this->selectedVariantIndexes)) {
            Flux::toast(heading: 'No variants selected', text: 'Select at least one variation first.', variant: 'warning');

            return;
        }

        $this->bulkEditField = $field;
        $this->bulkEditNumericValue = null;
        $this->bulkEditSelectValue = '';
        $this->modal('bulk-variant-edit')->show();
    }

    public function applyBulkEdit(): void
    {
        $numericFields = ['price', 'compare_at_price', 'cost_price', 'stock_quantity'];

        if (in_array($this->bulkEditField, $numericFields)) {
            $this->validateOnly('bulkEditNumericValue', [
                'bulkEditNumericValue' => ['nullable', 'numeric', 'min:0'],
            ]);

            foreach ($this->selectedVariantIndexes as $index) {
                $this->variants[(int) $index][$this->bulkEditField] = $this->bulkEditNumericValue;

                if ($this->bulkEditField === 'stock_quantity') {
                    $this->variants[(int) $index]['manage_stock'] = $this->bulkEditNumericValue !== null;
                }
            }
        } elseif ($this->bulkEditField === 'stock_status') {
            $this->validateOnly('bulkEditSelectValue', [
                'bulkEditSelectValue' => ['required', Rule::in(array_column(StockStatus::cases(), 'value'))],
            ]);

            foreach ($this->selectedVariantIndexes as $index) {
                $this->variants[(int) $index]['stock_status'] = $this->bulkEditSelectValue;
            }
        }

        $this->modal('bulk-variant-edit')->close();
        $count = count($this->selectedVariantIndexes);
        $this->selectedVariantIndexes = [];
        Flux::toast(heading: "{$count} variation(s) updated", variant: 'success');
    }

    public function bulkDeleteVariants(): void
    {
        if (empty($this->selectedVariantIndexes)) {
            return;
        }

        $indexes = array_map('intval', $this->selectedVariantIndexes);
        rsort($indexes);

        foreach ($indexes as $index) {
            $imagePath = $this->variants[$index]['image_path'] ?? null;

            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            $variantId = $this->variants[$index]['id'] ?? null;

            if ($variantId) {
                ProductVariant::findOrFail($variantId)->delete();
            }

            unset($this->variants[$index], $this->pendingVariantImages[$index]);
        }

        $this->variants = array_values($this->variants);
        $this->pendingVariantImages = array_values($this->pendingVariantImages);
        $this->selectedVariantIndexes = [];

        Flux::toast(heading: count($indexes).' variant(s) deleted', variant: 'success');
    }

    // ==================================================
    // DOWNLOADABLE FILES
    // ==================================================

    public function addFile(): void
    {
        $this->downloadableFiles[] = [
            'id' => null,
            'name' => '',
            'download_limit' => null,
            'download_expiry_days' => null,
            'version' => '',
        ];
    }

    public function removeFile(int $index): void
    {
        $fileId = $this->downloadableFiles[$index]['id'] ?? null;

        if ($fileId) {
            DownloadableFile::findOrFail($fileId)->delete();
        }

        unset($this->downloadableFiles[$index]);
        $this->downloadableFiles = array_values($this->downloadableFiles);
    }

    // ==================================================
    // LINKED PRODUCTS
    // ==================================================

    public function addLinkedProduct(int $productId): void
    {
        if (collect($this->linkedProducts)->contains('product_id', $productId)) {
            return;
        }

        $product = Product::findOrFail($productId);

        $this->linkedProducts[] = [
            'product_id' => $productId,
            'name' => $product->name,
            'sku' => $product->sku,
            'quantity' => 1,
            'is_optional' => false,
            'price_override' => null,
        ];

        $this->linkedProductSearch = '';
        unset($this->linkedProductResults);
    }

    public function removeLinkedProduct(int $index): void
    {
        unset($this->linkedProducts[$index]);
        $this->linkedProducts = array_values($this->linkedProducts);
    }

    // ==================================================
    // TAGS
    // ==================================================

    public function addTag(int $tagId, string $name): void
    {
        if (collect($this->selectedTags)->contains('id', $tagId)) {
            return;
        }

        $this->selectedTags[] = ['id' => $tagId, 'name' => $name];
        $this->tagSearch = '';
        unset($this->tagResults);
    }

    public function removeTag(int $index): void
    {
        unset($this->selectedTags[$index]);
        $this->selectedTags = array_values($this->selectedTags);
    }

    // ==================================================
    // IMAGES
    // ==================================================

    public function removeCoverImage(): void
    {
        if ($this->coverImage) {
            ProductImage::find($this->coverImage['id'])?->delete();
            $this->coverImage = null;
        }

        $this->pendingCoverImage = null;
    }

    public function removeGalleryImage(int $index): void
    {
        $image = $this->galleryImages[$index] ?? null;

        if ($image) {
            ProductImage::find($image['id'])?->delete();
        }

        unset($this->galleryImages[$index]);
        $this->galleryImages = array_values($this->galleryImages);
    }

    // ==================================================
    // SAVE
    // ==================================================

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($this->productId)],
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($this->productId)->whereNull('deleted_at')],
            'type' => ['required', Rule::in(array_column(ProductType::cases(), 'value'))],
            'price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'stock_status' => ['required', Rule::in(array_column(StockStatus::cases(), 'value'))],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'min_order_quantity' => ['nullable', 'integer', 'min:1'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'length' => ['nullable', 'numeric', 'min:0'],
            'width' => ['nullable', 'numeric', 'min:0'],
            'height' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(array_column(ProductStatus::cases(), 'value'))],
            'published_at' => ['nullable', 'date', 'required_if:status,scheduled'],
            'visibility' => ['required', Rule::in(array_column(ProductVisibility::cases(), 'value'))],
            'sort_order' => ['integer', 'min:0'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'primary_category_id' => ['nullable', 'exists:categories,id'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'canonical_url' => ['nullable', 'url', 'max:500'],
            'variants.*.sku' => ['nullable', 'string', 'max:100'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.cost_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock_quantity' => ['nullable', 'integer', 'min:0'],
            'variants.*.weight' => ['nullable', 'numeric', 'min:0'],
            'variants.*.length' => ['nullable', 'numeric', 'min:0'],
            'variants.*.width' => ['nullable', 'numeric', 'min:0'],
            'variants.*.height' => ['nullable', 'numeric', 'min:0'],
            'downloadableFiles.*.name' => ['required_with:downloadableFiles', 'string', 'max:255'],
            'downloadableFiles.*.download_limit' => ['nullable', 'integer', 'min:1'],
            'downloadableFiles.*.download_expiry_days' => ['nullable', 'integer', 'min:1'],
        ]);

        $data = [
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku ?: null,
            'model_number' => $this->model_number ?: null,
            'type' => $this->type,
            'short_description' => $this->short_description ?: null,
            'description' => $this->description ?: null,
            'technical_specification' => $this->technical_specification ?: null,
            'brand_id' => $this->brand_id,
            'primary_category_id' => $this->primary_category_id,
            'price' => $this->price !== null ? (int) round($this->price * 100) : null,
            'sale_price' => $this->sale_price !== null ? (int) round($this->sale_price * 100) : null,
            'cost_price' => $this->cost_price !== null ? (int) round($this->cost_price * 100) : null,
            'is_taxable' => $this->is_taxable,
            'stock_status' => $this->stock_status,
            'stock_quantity' => $this->stock_quantity,
            'allow_backorder' => $this->allow_backorder,
            'low_stock_threshold' => $this->low_stock_threshold,
            'min_order_quantity' => $this->min_order_quantity,
            'requires_shipping' => $this->requires_shipping,
            'weight' => $this->weight,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
            'status' => $this->status,
            'published_at' => $this->published_at ?: null,
            'visibility' => $this->visibility,
            'sort_order' => $this->sort_order,
            'requires_quotation' => $this->requires_quotation,
            'quotation_notes' => $this->quotation_notes ?: null,
            'meta_title' => $this->meta_title ?: null,
            'meta_description' => $this->meta_description ?: null,
            'canonical_url' => $this->canonical_url ?: null,
        ];

        if ($this->productId) {
            $product = Product::findOrFail($this->productId);
            $product->update($data);

            $this->saveRelationships($product);

            Flux::toast(heading: 'Product saved', text: $this->name.' has been updated.', variant: 'success');
        } else {
            $product = Product::create($data);
            Flux::toast(heading: 'Product created', text: $this->name.' has been added. You can now manage its details.', variant: 'success');
            $this->redirectRoute('admin.products.edit', $product, navigate: true);
        }
    }

    private function saveRelationships(Product $product): void
    {
        // ─── Attributes ────────────────────────────────────────────────────────
        $product->productAttributes()->delete();
        foreach ($this->selectedAttributes as $i => $attr) {
            $values = array_values(array_filter(array_map('trim', explode('|', $attr['values_string']))));
            ProductAttribute::create([
                'product_id' => $product->id,
                'attribute_id' => $attr['attribute_id'],
                'values' => $values,
                'is_variation_attribute' => $attr['is_variation_attribute'],
                'is_visible' => $attr['is_visible'],
                'sort_order' => $i,
            ]);
        }

        // ─── Variants ──────────────────────────────────────────────────────────
        $keptIds = collect($this->variants)->pluck('id')->filter()->all();
        $product->variants()->whereNotIn('id', $keptIds)->delete();

        $savedVariantIds = [];

        foreach ($this->variants as $i => $v) {
            $variantData = [
                'sku' => $v['sku'] ?: Str::upper(Str::random(8)),
                'price' => $v['price'] !== null ? (int) round((float) $v['price'] * 100) : null,
                'compare_at_price' => $v['compare_at_price'] !== null ? (int) round((float) $v['compare_at_price'] * 100) : null,
                'cost_price' => $v['cost_price'] !== null ? (int) round((float) $v['cost_price'] * 100) : null,
                'stock_status' => $v['stock_status'],
                'stock_quantity' => ($v['manage_stock'] ?? false) ? $v['stock_quantity'] : null,
                'allow_backorder' => (bool) ($v['allow_backorder'] ?? false),
                'weight' => $v['weight'] ?: null,
                'length' => $v['length'] ?: null,
                'width' => $v['width'] ?: null,
                'height' => $v['height'] ?: null,
                'description' => $v['description'] ?: null,
                'is_active' => (bool) $v['is_active'],
                'sort_order' => $i,
            ];

            if (! empty($this->pendingVariantImages[$i])) {
                if ($v['image_path']) {
                    Storage::disk('public')->delete($v['image_path']);
                }
                $variantData['image'] = $this->pendingVariantImages[$i]->store('products/variants', 'public');
            } else {
                $variantData['image'] = $v['image_path'];
            }

            if ($v['id']) {
                ProductVariant::findOrFail($v['id'])->update($variantData);
                $savedVariantIds[$i] = $v['id'];
            } else {
                $created = $product->variants()->create($variantData);
                $savedVariantIds[$i] = $created->id;
                $this->variants[$i]['id'] = $created->id;
            }
        }

        // Persist default variant
        $defaultIndex = collect($this->variants)->search(fn ($v) => $v['is_default'] ?? false);
        $product->update([
            'default_variant_id' => $defaultIndex !== false ? ($savedVariantIds[$defaultIndex] ?? null) : null,
        ]);

        // ─── Downloadable Files ────────────────────────────────────────────────
        $keptFileIds = collect($this->downloadableFiles)->pluck('id')->filter()->all();
        $product->downloadableFiles()->whereNotIn('id', $keptFileIds)->delete();

        foreach ($this->downloadableFiles as $i => $f) {
            $fileData = [
                'name' => $f['name'],
                'file_path' => $f['name'], // placeholder until file upload implemented
                'file_name' => $f['name'],
                'download_limit' => $f['download_limit'],
                'download_expiry_days' => $f['download_expiry_days'],
                'version' => $f['version'] ?: null,
                'sort_order' => $i,
            ];

            if ($f['id']) {
                DownloadableFile::findOrFail($f['id'])->update($fileData);
            } else {
                $product->downloadableFiles()->create($fileData);
            }
        }

        // ─── Linked Products ───────────────────────────────────────────────────
        if ($product->type === ProductType::GROUPED) {
            GroupedProductItem::where('group_product_id', $product->id)->delete();
            foreach ($this->linkedProducts as $i => $lp) {
                GroupedProductItem::create([
                    'group_product_id' => $product->id,
                    'child_product_id' => $lp['product_id'],
                    'sort_order' => $i,
                ]);
            }
        } elseif ($product->type === ProductType::BUNDLE) {
            $product->bundleItems()->delete();
            foreach ($this->linkedProducts as $i => $lp) {
                BundleItem::create([
                    'bundle_product_id' => $product->id,
                    'product_id' => $lp['product_id'],
                    'quantity' => max(1, (int) $lp['quantity']),
                    'is_optional' => (bool) $lp['is_optional'],
                    'price_override' => $lp['price_override'] !== null ? (int) round((float) $lp['price_override'] * 100) : null,
                    'sort_order' => $i,
                ]);
            }
        }

        // ─── Tags ──────────────────────────────────────────────────────────────
        $product->syncTags(collect($this->selectedTags)->pluck('name')->all());

        // ─── Cover Image ───────────────────────────────────────────────────────
        if ($this->pendingCoverImage) {
            $product->images()->where('is_cover', true)->delete();
            $path = $this->pendingCoverImage->store('products', 'public');
            $product->images()->create(['path' => $path, 'is_cover' => true, 'sort_order' => 0]);
            $this->pendingCoverImage = null;
            $cover = $product->images()->where('is_cover', true)->first();
            $this->coverImage = $cover ? ['id' => $cover->id, 'url' => $cover->url, 'alt' => ''] : null;
        }

        // ─── Gallery Images ────────────────────────────────────────────────────
        if (! empty($this->pendingGalleryImages)) {
            $sortStart = $product->images()->where('is_cover', false)->max('sort_order') + 1;
            foreach ($this->pendingGalleryImages as $i => $file) {
                $path = $file->store('products', 'public');
                $img = $product->images()->create(['path' => $path, 'is_cover' => false, 'sort_order' => $sortStart + $i]);
                $this->galleryImages[] = ['id' => $img->id, 'url' => $img->url, 'alt' => ''];
            }
            $this->pendingGalleryImages = [];
        }

        // Refresh variant image URLs after save and clear pending uploads
        foreach ($this->variants as $i => &$v) {
            if (! empty($this->pendingVariantImages[$i])) {
                $v['image_path'] = $variantData['image'] ?? $v['image_path'];
                $v['image_url'] = $v['image_path'] ? Storage::disk('public')->url($v['image_path']) : null;
            }
        }
        unset($v);
        $this->pendingVariantImages = [];
    }

    // ==================================================
    // COMPUTED
    // ==================================================

    #[Computed]
    public function brands(): Collection
    {
        return Brand::where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function categories(): Collection
    {
        return Category::whereNull('parent_id')
            ->with('children')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);
    }

    /** Attributes from catalog not yet added to this product. */
    #[Computed]
    public function allAttributes(): Collection
    {
        $selectedIds = collect($this->selectedAttributes)->pluck('attribute_id')->filter()->all();

        return Attribute::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->reject(fn ($a) => in_array($a->id, $selectedIds));
    }

    #[Computed]
    public function linkedProductResults(): Collection
    {
        if (strlen(trim($this->linkedProductSearch)) < 2) {
            return collect();
        }

        $linkedIds = collect($this->linkedProducts)->pluck('product_id')->all();
        $term = '%'.$this->linkedProductSearch.'%';

        return Product::where(fn ($q) => $q->where('name', 'like', $term)->orWhere('sku', 'like', $term))
            ->whereNotIn('id', $linkedIds)
            ->when($this->productId, fn ($q) => $q->where('id', '!=', $this->productId))
            ->whereNotIn('type', [ProductType::GROUPED->value, ProductType::BUNDLE->value])
            ->limit(8)
            ->get(['id', 'name', 'sku']);
    }

    #[Computed]
    public function tagResults(): Collection
    {
        if (strlen(trim($this->tagSearch)) < 1) {
            return collect();
        }

        $selectedIds = collect($this->selectedTags)->pluck('id')->all();
        $locale = config('app.locale', 'en');

        return Tag::where("name->{$locale}", 'like', '%'.$this->tagSearch.'%')
            ->whereNotIn('id', $selectedIds)
            ->limit(8)
            ->get();
    }

    // ==================================================
    // HELPERS
    // ==================================================

    /** @param array<int, array<int, array{id: int, label: string}>> $arrays */
    private function cartesianProduct(array $arrays): array
    {
        $result = [[]];
        foreach ($arrays as $values) {
            $tmp = [];
            foreach ($result as $existing) {
                foreach ($values as $value) {
                    $tmp[] = array_merge($existing, [$value]);
                }
            }
            $result = $tmp;
        }

        return $result;
    }

    public function getTitle(): string
    {
        return $this->productId ? 'Edit product — Admin' : 'New product — Admin';
    }

    public function render(): View
    {
        return view('pages.admin.products.form.form')->title($this->getTitle());
    }
};
