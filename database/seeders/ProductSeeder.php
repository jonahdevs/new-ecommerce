<?php

namespace Database\Seeders;

use App\Enums\ProductLinkType;
use App\Enums\ProductType;
use App\Enums\ProductVisibility;
use App\Enums\StockStatus;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\BundleItem;
use App\Models\Category;
use App\Models\DownloadableFile;
use App\Models\GroupedProductItem;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductImage;
use App\Models\ProductLink;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /** @var array<string, int> SKU → product id */
    private array $productIdBySku = [];

    /** @var array<string, int> brand name → brand id */
    private array $brandIdByName = [];

    /** @var array<string, int> lowercased category name → category id */
    private array $categoryIdByName = [];

    /** @var array<string, int> attribute slug → attribute id */
    private array $attributeIdBySlug = [];

    /** @var array<string, array<string, int>> attribute slug → (value slug → attribute_value id) */
    private array $attributeValueIds = [];

    public function run(): void
    {
        $jsonPath = database_path('data/products.json');

        if (! File::exists($jsonPath)) {
            $this->command->error('products.json file not found at '.$jsonPath);

            return;
        }

        $data = json_decode(File::get($jsonPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error('Error parsing JSON: '.json_last_error_msg());

            return;
        }

        $this->primeLookups();

        // Pass 1: create every product (and its own attributes/variants/images/categories).
        foreach ($data as $item) {
            $this->createProduct($item);
        }

        // Pass 2: wire relationships that reference other products by SKU.
        foreach ($data as $item) {
            $this->linkRelationships($item);
        }
    }

    private function primeLookups(): void
    {
        $this->brandIdByName = Brand::pluck('id', 'name')->all();

        $this->categoryIdByName = Category::all()
            ->mapWithKeys(fn (Category $c) => [Str::lower($c->name) => $c->id])
            ->all();

        $this->attributeIdBySlug = Attribute::pluck('id', 'slug')->all();

        foreach (AttributeValue::all() as $value) {
            $slug = array_search($value->attribute_id, $this->attributeIdBySlug, true);
            if ($slug !== false) {
                $this->attributeValueIds[$slug][$value->slug] = $value->id;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createProduct(array $data): void
    {
        $sku = $data['sku'];
        $name = $data['name'];
        $type = $this->resolveType($data);
        $isVirtual = (bool) ($data['is_virtual'] ?? false);
        $isDownloadable = (bool) ($data['is_downloadable'] ?? false);
        $price = $this->toMinorUnits($data['price'] ?? null);
        $salePrice = $this->toMinorUnits($data['sale_price'] ?? null);
        $quantity = $data['stock_quantity'] ?? $data['quantity'] ?? null;
        $stockStatus = $this->resolveStockStatus($data, $quantity);

        $brandId = null;
        if (! empty($data['brand'])) {
            $brandId = $this->brandIdByName[trim($data['brand'])] ?? null;
        }

        $primaryCategoryId = null;
        if (! empty($data['category'])) {
            $primaryCategoryId = $this->categoryIdByName[Str::lower(trim($data['category']))] ?? null;
        }

        $product = Product::create([
            'name' => $name,
            'slug' => $this->buildSlug($data['slug'] ?? null, $name, $sku),
            'sku' => $sku,
            'brand_id' => $brandId,
            'primary_category_id' => $primaryCategoryId,
            'model_number' => $data['model_number'] ?? null,
            'type' => $type,
            'short_description' => $data['short_description'] ?? null,
            'description' => $data['description'] ?? null,
            'technical_specification' => $data['technical_specification'] ?? null,
            'price' => $price,
            'sale_price' => $salePrice,
            'is_virtual' => $isVirtual,
            'is_downloadable' => $isDownloadable,
            'requires_shipping' => $this->resolveRequiresShipping($type, $isVirtual),
            'length' => $data['length'] ?? null,
            'width' => $data['width'] ?? null,
            'height' => $data['height'] ?? null,
            'stock_status' => $stockStatus,
            'stock_quantity' => $quantity,
            'requires_quotation' => $data['requires_quotation'] ?? false,
            'quotation_notes' => $data['quotation_notes'] ?? null,
            'min_order_quantity' => $data['min_order_quantity'] ?? null,
            'visibility' => ProductVisibility::VISIBLE,
        ]);

        $this->productIdBySku[$sku] = $product->id;

        $this->createImages($product, $data);
        $this->createProductAttributes($product, $data['attributes'] ?? []);
        $this->createVariants($product, $data['variants'] ?? []);
        $this->createDownloadableFiles($product, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function linkRelationships(array $data): void
    {
        $productId = $this->productIdBySku[$data['sku']] ?? null;
        if ($productId === null) {
            return;
        }

        $this->attachProductLinks($productId, $data['accessories'] ?? [], ProductLinkType::ACCESSORY);
        $this->attachProductLinks($productId, $data['spare_parts'] ?? [], ProductLinkType::SPARE_PART);
        $this->attachProductLinks($productId, $data['upsells'] ?? [], ProductLinkType::UPSELL);
        $this->attachProductLinks($productId, $data['cross_sells'] ?? [], ProductLinkType::CROSS_SELL);
        $this->attachGroupedChildren($productId, $data['grouped_children'] ?? []);
        $this->attachBundleChildren($productId, $data['bundle_children'] ?? []);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveType(array $data): ProductType
    {
        return match ($data['type'] ?? 'simple') {
            'variable' => ProductType::VARIABLE,
            'grouped' => ProductType::GROUPED,
            'bundle', 'bundled' => ProductType::BUNDLE,
            default => ProductType::SIMPLE,
        };
    }

    private function resolveRequiresShipping(ProductType $type, bool $isVirtual): bool
    {
        return ! ($isVirtual || $type === ProductType::GROUPED);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveStockStatus(array $data, ?int $quantity): StockStatus
    {
        if (! empty($data['stock_status'])) {
            return StockStatus::from($data['stock_status']);
        }

        if ($quantity !== null && $quantity <= 0) {
            return StockStatus::OUT_OF_STOCK;
        }

        return StockStatus::IN_STOCK;
    }

    private function toMinorUnits(int|float|string|null $amount): ?int
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        return (int) round(((float) $amount) * 100);
    }

    private function buildSlug(?string $explicit, string $name, string $sku): string
    {
        if (! empty($explicit)) {
            return Str::slug($explicit);
        }

        return Str::slug($name.' '.$sku);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createImages(Product $product, array $data): void
    {
        $sortOrder = 0;

        if (! empty($data['image'])) {
            ProductImage::create([
                'product_id' => $product->id,
                'path' => $data['image'],
                'is_cover' => true,
                'sort_order' => $sortOrder++,
            ]);
        }

        foreach ($data['gallery'] ?? [] as $path) {
            ProductImage::create([
                'product_id' => $product->id,
                'path' => $path,
                'is_cover' => false,
                'sort_order' => $sortOrder++,
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $attributes
     */
    private function createProductAttributes(Product $product, array $attributes): void
    {
        foreach ($attributes as $index => $attr) {
            $attributeId = $this->attributeIdBySlug[$attr['slug']] ?? null;
            if ($attributeId === null) {
                continue;
            }

            ProductAttribute::create([
                'product_id' => $product->id,
                'attribute_id' => $attributeId,
                'values' => $attr['values'] ?? [],
                'is_variation_attribute' => $attr['is_variation_attribute'] ?? false,
                'is_visible' => $attr['is_visible'] ?? true,
                'sort_order' => $index + 1,
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $variants
     */
    private function createVariants(Product $product, array $variants): void
    {
        foreach ($variants as $index => $variantData) {
            $quantity = $variantData['stock_quantity'] ?? null;

            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'sku' => $variantData['sku'],
                'price' => $this->toMinorUnits($variantData['price'] ?? null),
                'compare_at_price' => $this->toMinorUnits($variantData['sale_price'] ?? null),
                'stock_status' => $this->resolveStockStatus($variantData, $quantity),
                'stock_quantity' => $quantity,
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);

            $valueIds = [];
            foreach ($variantData['attribute_values'] ?? [] as $pair) {
                $valueId = $this->attributeValueIds[$pair['attribute']][$pair['value']] ?? null;
                if ($valueId !== null) {
                    $valueIds[] = $valueId;
                }
            }

            if ($valueIds !== []) {
                $variant->attributeValues()->attach($valueIds);
            }
        }
    }

    /**
     * @param  array<int, string>  $linkedSkus
     */
    private function attachProductLinks(int $productId, array $linkedSkus, ProductLinkType $type): void
    {
        foreach ($linkedSkus as $index => $sku) {
            $linkedId = $this->productIdBySku[$sku] ?? null;
            if ($linkedId === null || $linkedId === $productId) {
                continue;
            }

            ProductLink::create([
                'product_id' => $productId,
                'linked_product_id' => $linkedId,
                'type' => $type,
                'sort_order' => $index + 1,
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $children
     */
    private function attachGroupedChildren(int $groupProductId, array $children): void
    {
        foreach ($children as $index => $child) {
            $childId = $this->productIdBySku[$child['sku']] ?? null;
            if ($childId === null) {
                continue;
            }

            GroupedProductItem::create([
                'group_product_id' => $groupProductId,
                'child_product_id' => $childId,
                'sort_order' => $index + 1,
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $children
     */
    private function attachBundleChildren(int $bundleProductId, array $children): void
    {
        foreach ($children as $index => $child) {
            $childId = $this->productIdBySku[$child['sku']] ?? null;
            if ($childId === null) {
                continue;
            }

            BundleItem::create([
                'bundle_product_id' => $bundleProductId,
                'product_id' => $childId,
                'quantity' => $child['quantity'] ?? 1,
                'is_optional' => $child['is_optional'] ?? false,
                'price_override' => $this->toMinorUnits($child['price_override'] ?? null),
                'sort_order' => $index + 1,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createDownloadableFiles(Product $product, array $data): void
    {
        foreach ($data['downloadable_files'] ?? [] as $index => $file) {
            DownloadableFile::create([
                'product_id' => $product->id,
                'name' => $file['name'],
                'file_path' => $file['file_path'],
                'file_name' => $file['file_name'] ?? basename($file['file_path']),
                'mime_type' => $file['mime_type'] ?? null,
                'file_size' => $file['file_size'] ?? null,
                'download_limit' => $file['download_limit'] ?? $data['download_limit'] ?? null,
                'download_expiry_days' => $file['download_expiry_days'] ?? $data['download_expiry'] ?? null,
                'version' => $file['version'] ?? null,
                'sort_order' => $index,
            ]);
        }
    }
}
