<?php

namespace App\Livewire\Forms\Admin;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Enums\ProductVisibility;
use App\Models\Attribute as ProductAttribute;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;

class ProductForm extends Form
{
    public ?Product $product = null;

    // ── Basic ──────────────────────────────────────────────────────────────────

    public string $name = '';

    public string $model_number = '';

    public string $slug = '';

    public string $type = 'simple';

    public string $description = '';

    public string $short_description = '';

    // ── Status ─────────────────────────────────────────────────────────────────

    public string $status = 'draft';

    public string $visibility = 'public';

    // ── General / Pricing ──────────────────────────────────────────────────────

    public string $price = '';

    public string $sale_price = '';

    public ?int $tax_class_id = null;

    // ── Inventory ──────────────────────────────────────────────────────────────

    public string $sku = '';

    public bool $manage_stock = false;

    public int $stock_quantity = 0;

    // ── Shipping ───────────────────────────────────────────────────────────────

    public string $weight = '';

    public string $height = '';

    public string $width = '';

    public string $length = '';

    public string $shipping_information = '';

    public string $warranty_information = '';

    public string $return_policy = '';

    // ── Media ──────────────────────────────────────────────────────────────────

    /** @var TemporaryUploadedFile|null */
    public $image = null;

    public ?string $existing_image = null;

    /** @var array<int, TemporaryUploadedFile> */
    public array $new_images = [];

    /** @var array<int, array{id: int, url: string, alt_text: string|null}> */
    public array $existing_images = [];

    /** @var array<int, int> */
    public array $images_to_delete = [];

    // ── Taxonomy ───────────────────────────────────────────────────────────────

    public ?int $brand_id = null;

    /** @var array<int, int> */
    public array $category_ids = [];

    // ── Linked Products ────────────────────────────────────────────────────────

    /** @var array<int, array{id: int, name: string, sku: string|null}> */
    public array $upsell_products = [];

    /** @var array<int, array{id: int, name: string, sku: string|null}> */
    public array $cross_sell_products = [];

    /** @var array<int, array{id: int, name: string, sku: string|null}> */
    public array $accessory_products = [];

    // ── Attributes ─────────────────────────────────────────────────────────────

    /**
     * @var array<int, array{
     *   attribute_id: int|null,
     *   name: string,
     *   values: array<int, int>,
     *   is_variation_attribute: bool,
     *   is_visible: bool
     * }>
     */
    public array $product_attributes = [];

    // ── Variations ─────────────────────────────────────────────────────────────

    /**
     * @var array<int, array{
     *   id: int|null,
     *   name: string,
     *   sku: string,
     *   price: string,
     *   sale_price: string,
     *   cost_price: string,
     *   manage_stock: bool,
     *   stock_quantity: int,
     *   stock_status: string,
     *   allow_backorders: bool,
     *   backorder_message: string,
     *   max_backorder_quantity: string,
     *   expected_restock_date: string,
     *   low_stock_threshold: string,
     *   weight: string,
     *   height: string,
     *   width: string,
     *   length: string,
     *   image_path: string|null,
     *   is_default: bool,
     *   is_active: bool,
     *   description: string,
     *   attributes: array<int, int>
     * }>
     */
    public array $variations = [];

    // ── SEO ────────────────────────────────────────────────────────────────────

    public string $meta_title = '';

    public string $meta_description = '';

    public string $meta_keywords = '';

    // ── Specifications ─────────────────────────────────────────────────────────

    public string $specifications = '';

    // ── Advanced ───────────────────────────────────────────────────────────────

    public string $purchase_note = '';

    public bool $requires_quotation = false;

    public int $sort_order = 0;

    public bool $reviews_enabled = true;

    // ── Validation ─────────────────────────────────────────────────────────────

    public function rules(): array
    {
        $productId = $this->product?->id;

        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'model_number' => ['nullable', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:products,slug,'.$productId],
            'type' => ['required', 'string', 'in:'.implode(',', array_column(ProductType::cases(), 'value'))],
            'description' => ['nullable', 'string'],
            'short_description' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:'.implode(',', array_column(ProductStatus::cases(), 'value'))],
            'visibility' => ['required', 'string', 'in:'.implode(',', array_column(ProductVisibility::cases(), 'value'))],
            'price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'tax_class_id' => ['nullable', 'integer', 'exists:tax_classes,id'],
            'sku' => ['nullable', 'string', 'max:255', 'unique:products,sku,'.$productId],
            'manage_stock' => ['boolean'],
            'stock_quantity' => ['integer', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'height' => ['nullable', 'numeric', 'min:0'],
            'width' => ['nullable', 'numeric', 'min:0'],
            'length' => ['nullable', 'numeric', 'min:0'],
            'shipping_information' => ['nullable', 'string'],
            'warranty_information' => ['nullable', 'string'],
            'return_policy' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:2048'],
            'new_images.*' => ['nullable', 'image', 'max:2048'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'upsell_products' => ['nullable', 'array'],
            'cross_sell_products' => ['nullable', 'array'],
            'accessory_products' => ['nullable', 'array'],
            'product_attributes' => ['nullable', 'array'],
            'product_attributes.*.attribute_id' => ['nullable', 'integer', 'exists:attributes,id'],
            'product_attributes.*.values' => ['nullable'],
            'variations' => ['nullable', 'array'],
            'variations.*.price' => ['nullable', 'numeric', 'min:0'],
            'variations.*.sale_price' => ['nullable', 'numeric', 'min:0'],
            'variations.*.cost_price' => ['nullable', 'numeric', 'min:0'],
            'variations.*.sku' => ['nullable', 'string', 'max:255'],
            'variations.*.weight' => ['nullable', 'numeric', 'min:0'],
            'variations.*.height' => ['nullable', 'numeric', 'min:0'],
            'variations.*.width' => ['nullable', 'numeric', 'min:0'],
            'variations.*.length' => ['nullable', 'numeric', 'min:0'],
            'variations.*.stock_quantity' => ['nullable', 'integer', 'min:0'],
            'variations.*.low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'variations.*.max_backorder_quantity' => ['nullable', 'integer', 'min:1'],
            'variations.*.expected_restock_date' => ['nullable', 'date'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string', 'max:500'],
            'specifications' => ['nullable', 'string'],
            'purchase_note' => ['nullable', 'string'],
            'requires_quotation' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
            'reviews_enabled' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Product name is required.',
            'name.min' => 'Product name must be at least 2 characters.',
            'slug.unique' => 'This slug is already in use by another product.',
            'sku.unique' => 'This SKU is already taken by another product.',
            'image.image' => 'The product image must be an image file.',
            'image.max' => 'The product image may not exceed 2MB.',
            'new_images.*.image' => 'Each gallery file must be an image.',
            'new_images.*.max' => 'Gallery images may not exceed 2MB each.',
        ];
    }

    // ── Hydration ──────────────────────────────────────────────────────────────

    public function setProduct(Product $product): void
    {
        $this->product = $product;

        // Basic
        $this->name = $product->name;
        $this->model_number = $product->model_number ?? '';
        $this->slug = $product->slug;
        $this->type = $product->type->value;
        $this->description = $product->description ?? '';
        $this->short_description = $product->short_description ?? '';

        // Status
        $this->status = $product->status->value;
        $this->visibility = $product->visibility->value;

        // Pricing
        $this->price = $product->price !== null ? (string) $product->price : '';
        $this->sale_price = $product->sale_price !== null ? (string) $product->sale_price : '';
        $this->tax_class_id = $product->tax_class_id;

        // Inventory
        $this->sku = $product->sku ?? '';
        $this->manage_stock = (bool) $product->manage_stock;
        $this->stock_quantity = $product->stock_quantity ?? 0;

        // Shipping
        $this->weight = $product->weight !== null ? (string) $product->weight : '';
        $this->height = $product->height !== null ? (string) $product->height : '';
        $this->width = $product->width !== null ? (string) $product->width : '';
        $this->length = $product->length !== null ? (string) $product->length : '';
        $this->shipping_information = $product->shipping_information ?? '';
        $this->warranty_information = $product->warranty_information ?? '';
        $this->return_policy = $product->return_policy ?? '';

        // Media
        $this->existing_image = $product->image_path;
        $this->existing_images = $product->images->map(fn (ProductImage $img) => [
            'id' => $img->id,
            'url' => $img->url,
            'alt_text' => $img->alt_text,
        ])->toArray();

        // Taxonomy
        $this->brand_id = $product->brand_id;
        $this->category_ids = $product->categories->pluck('id')->toArray();

        // Linked products
        if ($product->relationLoaded('upsells')) {
            $this->upsell_products = $product->upsells->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'sku' => $p->sku])->toArray();
        }
        if ($product->relationLoaded('crossSells')) {
            $this->cross_sell_products = $product->crossSells->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'sku' => $p->sku])->toArray();
        }
        if ($product->relationLoaded('accessories')) {
            $this->accessory_products = $product->accessories->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'sku' => $p->sku])->toArray();
        }

        // Attributes
        if ($product->relationLoaded('attributes')) {
            $this->product_attributes = $product->attributes->map(fn ($attr) => [
                'attribute_id'           => $attr->id,
                'name'                   => $attr->name,
                'values'                 => is_array($attr->pivot->values)
                    ? $attr->pivot->values
                    : (json_decode($attr->pivot->values ?? '[]', true) ?? []),
                'is_variation_attribute' => (bool) $attr->pivot->is_variation_attribute,
                'is_visible'             => (bool) $attr->pivot->is_visible,
                'is_new'                 => false,
            ])->toArray();
        }

        // Variations
        if ($product->relationLoaded('variants')) {
            $this->variations = $product->variants->map(fn (ProductVariant $v) => [
                'id'                     => $v->id,
                'name'                   => $v->name ?? '',
                'sku'                    => $v->sku ?? '',
                'price'                  => $v->price !== null ? (string) $v->price : '',
                'sale_price'             => $v->sale_price !== null ? (string) $v->sale_price : '',
                'cost_price'             => $v->cost_price !== null ? (string) $v->cost_price : '',
                'manage_stock'           => (bool) $v->manage_stock,
                'stock_quantity'         => $v->stock_quantity ?? 0,
                'stock_status'           => $v->stock_status ?? 'in_stock',
                'allow_backorders'       => (bool) $v->allow_backorders,
                'backorder_message'      => $v->backorder_message ?? '',
                'max_backorder_quantity' => $v->max_backorder_quantity !== null ? (string) $v->max_backorder_quantity : '',
                'expected_restock_date'  => $v->expected_restock_date?->format('Y-m-d') ?? '',
                'low_stock_threshold'    => $v->low_stock_threshold !== null ? (string) $v->low_stock_threshold : '',
                'weight'                 => $v->weight !== null ? (string) $v->weight : '',
                'height'                 => $v->height !== null ? (string) $v->height : '',
                'width'                  => $v->width !== null ? (string) $v->width : '',
                'length'                 => $v->length !== null ? (string) $v->length : '',
                'image_path'             => $v->image_path,
                'is_default'             => (bool) $v->is_default,
                'is_active'              => (bool) $v->is_active,
                'description'            => $v->description ?? '',
                'attributes'             => is_array($v->attributes) ? $v->attributes : [],
            ])->toArray();
        }

        // SEO
        $this->meta_title = $product->meta_title ?? '';
        $this->meta_description = $product->meta_description ?? '';
        $this->meta_keywords = is_array($product->meta_keywords)
            ? implode(', ', $product->meta_keywords)
            : ($product->meta_keywords ?? '');

        // Specifications
        $this->specifications = $product->technical_specification ?? '';

        // Advanced
        $this->purchase_note = $product->purchase_note ?? '';
        $this->requires_quotation = (bool) $product->requires_quotation;
        $this->sort_order = $product->sort_order ?? 0;
        $this->reviews_enabled = (bool) $product->reviews_enabled;
    }

    // ── Persistence ────────────────────────────────────────────────────────────

    public function store(): Product
    {
        $this->validate();

        $product = Product::create($this->productData());

        $this->syncRelationships($product);

        return $product;
    }

    public function update(): void
    {
        $this->validate();

        $this->product->update($this->productData());

        $this->syncRelationships($this->product);
    }

    protected function productData(): array
    {
        return [
            'name' => $this->name,
            'model_number' => $this->model_number ?: null,
            'slug' => $this->slug ?: Str::slug($this->name),
            'type' => $this->type,
            'description' => $this->description ?: null,
            'short_description' => $this->short_description ?: null,
            'status' => $this->status,
            'visibility' => $this->visibility,
            'price' => $this->price !== '' ? $this->price : null,
            'sale_price' => $this->sale_price !== '' ? $this->sale_price : null,
            'tax_class_id' => $this->tax_class_id ?: null,
            'sku' => $this->sku ?: null,
            'manage_stock' => $this->manage_stock,
            'stock_quantity' => $this->stock_quantity,
            'weight' => $this->weight !== '' ? $this->weight : null,
            'height' => $this->height !== '' ? $this->height : null,
            'width' => $this->width !== '' ? $this->width : null,
            'length' => $this->length !== '' ? $this->length : null,
            'shipping_information' => $this->shipping_information ?: null,
            'warranty_information' => $this->warranty_information ?: null,
            'return_policy' => $this->return_policy ?: null,
            'image_path' => $this->resolveImagePath(),
            'brand_id' => $this->brand_id ?: null,
            'meta_title' => $this->meta_title ?: null,
            'meta_description' => $this->meta_description ?: null,
            'meta_keywords' => $this->meta_keywords ? array_map('trim', explode(',', $this->meta_keywords)) : null,
            'technical_specification' => $this->specifications ?: null,
            'purchase_note' => $this->purchase_note ?: null,
            'requires_quotation' => $this->requires_quotation,
            'sort_order' => $this->sort_order,
            'reviews_enabled' => $this->reviews_enabled,
        ];
    }

    protected function syncRelationships(Product $product): void
    {
        $product->categories()->sync($this->category_ids);
        $this->syncGalleryImages($product);
        $this->syncLinkedProducts($product);
        $this->syncProductAttributes($product);
        $this->syncVariations($product);
    }

    protected function syncLinkedProducts(Product $product): void
    {
        $product->upsells()->sync(array_column($this->upsell_products, 'id'));
        $product->crossSells()->sync(array_column($this->cross_sell_products, 'id'));
        $product->accessories()->sync(array_column($this->accessory_products, 'id'));
    }

    protected function syncProductAttributes(Product $product): void
    {
        $syncData = [];
        $allValueIds = [];

        foreach ($this->product_attributes as $attr) {
            if ($attr['is_new'] ?? false) {
                // Create or reuse attribute + values from pipe-separated input
                $name = trim($attr['name'] ?? '');
                if (! $name) {
                    continue;
                }

                $attribute = ProductAttribute::firstOrCreate(
                    ['slug' => Str::slug($name)],
                    ['name' => $name, 'is_active' => true]
                );

                $valueIds = [];
                if (! empty($attr['values'])) {
                    foreach (array_filter(array_map('trim', explode('|', $attr['values']))) as $rawValue) {
                        $value = AttributeValue::firstOrCreate(
                            ['attribute_id' => $attribute->id, 'slug' => Str::slug($rawValue)],
                            ['label' => $rawValue, 'value' => Str::slug($rawValue)]
                        );
                        $valueIds[] = $value->id;
                    }
                }

                $syncData[$attribute->id] = [
                    'is_variation_attribute' => $attr['is_variation_attribute'],
                    'is_visible'             => $attr['is_visible'],
                    'values'                 => json_encode($valueIds),
                ];
                $allValueIds = array_merge($allValueIds, $valueIds);
            } elseif ($attr['attribute_id']) {
                $values = is_array($attr['values']) ? $attr['values'] : [];
                $syncData[$attr['attribute_id']] = [
                    'is_variation_attribute' => $attr['is_variation_attribute'],
                    'is_visible'             => $attr['is_visible'],
                    'values'                 => json_encode($values),
                ];
                $allValueIds = array_merge($allValueIds, $values);
            }
        }

        $product->attributes()->sync($syncData);
        $product->attributeValues()->sync(array_unique($allValueIds));
    }

    protected function syncVariations(Product $product): void
    {
        $existingIds = array_filter(array_column($this->variations, 'id'));

        // Delete removed variants
        if (! empty($existingIds)) {
            $product->variants()->whereNotIn('id', $existingIds)->delete();
        } else {
            // All variations are new — only delete if we have variations at all
            if (! empty($this->variations)) {
                $product->variants()->delete();
            }
        }

        foreach ($this->variations as $v) {
            $data = [
                'product_id'             => $product->id,
                'name'                   => $v['name'] ?: null,
                'sku'                    => $v['sku'] ?: null,
                'price'                  => $v['price'] !== '' ? $v['price'] : null,
                'sale_price'             => $v['sale_price'] !== '' ? $v['sale_price'] : null,
                'cost_price'             => $v['cost_price'] !== '' ? $v['cost_price'] : null,
                'manage_stock'           => $v['manage_stock'],
                'stock_quantity'         => $v['stock_quantity'],
                'stock_status'           => $v['stock_status'],
                'allow_backorders'       => $v['allow_backorders'] ?? false,
                'backorder_message'      => $v['backorder_message'] ?: null,
                'max_backorder_quantity' => $v['max_backorder_quantity'] !== '' ? $v['max_backorder_quantity'] : null,
                'expected_restock_date'  => $v['expected_restock_date'] !== '' ? $v['expected_restock_date'] : null,
                'low_stock_threshold'    => $v['low_stock_threshold'] !== '' ? $v['low_stock_threshold'] : null,
                'weight'                 => $v['weight'] !== '' ? $v['weight'] : null,
                'height'                 => $v['height'] !== '' ? $v['height'] : null,
                'width'                  => $v['width'] !== '' ? $v['width'] : null,
                'length'                 => $v['length'] !== '' ? $v['length'] : null,
                'image_path'             => $v['image_path'] ?? null,
                'is_default'             => $v['is_default'],
                'is_active'              => $v['is_active'],
                'description'            => $v['description'] ?: null,
                'attributes'             => $v['attributes'],
            ];

            if ($v['id']) {
                ProductVariant::where('id', $v['id'])->where('product_id', $product->id)->update($data);
            } else {
                $product->variants()->create($data);
            }
        }
    }

    protected function syncGalleryImages(Product $product): void
    {
        if (! empty($this->images_to_delete)) {
            ProductImage::whereIn('id', $this->images_to_delete)
                ->where('product_id', $product->id)
                ->get()
                ->each(function (ProductImage $image) {
                    \Storage::disk('public')->delete($image->image_path);
                    $image->delete();
                });
        }

        foreach ($this->new_images as $index => $file) {
            if ($file) {
                $path = $file->store('products/gallery', 'public');
                $product->images()->create([
                    'image_path' => $path,
                    'sort_order' => $product->images()->count() + $index,
                ]);
            }
        }
    }

    protected function resolveImagePath(): ?string
    {
        if ($this->image && is_object($this->image)) {
            return $this->image->store('products/images', 'public');
        }

        return $this->existing_image;
    }
}
