<?php

namespace Database\Seeders;

use App\Enums\ProductRelationshipType;
use App\Enums\ProductType;
use App\Models\Attribute as ProductAttribute;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Override to seed from a different JSON file (used in tests).
     */
    public ?string $jsonPath = null;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = $this->jsonPath ?? database_path('seeders/data/products.json');

        if (! File::exists($jsonPath)) {
            $this->command->error("❌ JSON file not found: {$jsonPath}");

            return;
        }

        $jsonContent = File::get($jsonPath);
        $data = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error('❌ Invalid JSON: '.json_last_error_msg());

            return;
        }

        // Store relationships to process after all products are created
        $productRelationships = [
            'accessory' => [], // accessories with recommended quantity
            'cross_sell' => [],
            'up_sells' => [],
            'grouped' => [],
            'bundle' => [],
        ];

        $this->command->info('🔄 Creating products...');

        foreach ($data as $productData) {
            $brand = ! empty($productData['brand']) ? $this->createBrand($productData['brand']) : null;
            $category = ! empty($productData['category']) ? $this->createCategory($productData['category']) : null;

            $product = $this->createProduct($productData, $category, $brand);
            $this->command->info("✅ Created product: {$product->name} (SKU: {$product->sku}) [{$product->type->value}]");

            // Variation attributes + variants — for variable products
            if (! empty($productData['attributes']) && is_array($productData['attributes'])) {
                $this->attachProductAttributes($product, $productData['attributes']);
            }

            if (! empty($productData['variants']) && is_array($productData['variants'])) {
                $this->createVariants($product, $productData['variants']);
            }

            // Accessories — stored with recommended quantity
            // JSON format: array of SKU strings OR array of {sku, quantity} objects
            if (! empty($productData['accessories']) && is_array($productData['accessories'])) {
                $accessories = $this->processAccessoriesArray($productData['accessories']);
                $productRelationships['accessory'][$productData['sku']] = $accessories;
                $this->command->info('  🔧 Stored '.count($accessories)." accessory relationships for {$productData['name']}");
            }

            // Cross-sells
            if (! empty($productData['cross_sells']) && is_array($productData['cross_sells'])) {
                $crossSellSKUs = $this->processSkuArray($productData['cross_sells']);
                $productRelationships['cross_sell'][$productData['sku']] = $crossSellSKUs;
                $this->command->info('  🔗 Stored '.count($crossSellSKUs)." cross-sell relationships for {$productData['name']}");
            }

            // Upsells
            if (! empty($productData['upsells']) && is_array($productData['upsells'])) {
                $upsellSKUs = $this->processSkuArray($productData['upsells']);
                $productRelationships['up_sells'][$productData['sku']] = $upsellSKUs;
                $this->command->info('  ⬆️  Stored '.count($upsellSKUs)." upsell relationships for {$productData['name']}");
            }

            // Grouped children (for type=grouped)
            if (! empty($productData['grouped_children']) && is_array($productData['grouped_children'])) {
                $children = $this->processAccessoriesArray($productData['grouped_children']);
                $productRelationships['grouped'][$productData['sku']] = $children;
                $this->command->info('  📦 Stored '.count($children)." grouped children for {$productData['name']}");
            }

            // Bundle children (for type=bundle)
            if (! empty($productData['bundle_children']) && is_array($productData['bundle_children'])) {
                $children = $this->processAccessoriesArray($productData['bundle_children']);
                $productRelationships['bundle'][$productData['sku']] = $children;
                $this->command->info('  🎁 Stored '.count($children)." bundle children for {$productData['name']}");
            }
        }

        $this->attachProductRelationships($productRelationships);

        $this->command->info('✅ Product seeding completed!');
    }

    /**
     * Process accessories array — supports two JSON formats:
     *
     * Simple:   ["SKU-001", "SKU-002"]
     * With qty: [{"sku": "SKU-001", "quantity": 6}, {"sku": "SKU-002", "quantity": 2}]
     *
     * Returns: [['sku' => 'SKU-001', 'quantity' => 6], ...]
     */
    protected function processAccessoriesArray(array $accessories): array
    {
        return collect($accessories)->map(function ($item) {
            if (is_string($item)) {
                return ['sku' => $item, 'quantity' => 1];
            }

            return [
                'sku' => $item['sku'],
                'quantity' => $item['quantity'] ?? 1,
            ];
        })->toArray();
    }

    /**
     * Process a simple SKU array (for cross-sells and upsells)
     * Supports both string SKUs and legacy full product objects
     */
    protected function processSkuArray(array $products): array
    {
        $skus = [];

        foreach ($products as $product) {
            if (is_string($product)) {
                $skus[] = $product;
            } else {
                // Legacy: full product object in JSON
                $brand = ! empty($product['brand']) ? $this->createBrand($product['brand']) : null;
                $category = ! empty($product['category']) ? $this->createCategory($product['category']) : null;

                $created = $this->createProduct($product, $category, $brand);
                $this->command->info("  📎 Created product: {$created->name} (SKU: {$created->sku})");

                $skus[] = $product['sku'];
            }
        }

        return $skus;
    }

    /**
     * Attach all product relationships
     */
    protected function attachProductRelationships(array $productRelationships): void
    {
        // Accessories
        if (! empty($productRelationships['accessory'])) {
            $this->command->info('🔧 Attaching accessories...');
            foreach ($productRelationships['accessory'] as $productSKU => $accessories) {
                $this->attachAccessories($productSKU, $accessories);
            }
        }

        // Cross-sells
        if (! empty($productRelationships['cross_sell'])) {
            $this->command->info('🔗 Attaching cross-sells...');
            foreach ($productRelationships['cross_sell'] as $productSKU => $skus) {
                $this->attachSimpleRelationship($productSKU, $skus, ProductRelationshipType::CROSS_SELL, 'cross-sells');
            }
        }

        // Upsells
        if (! empty($productRelationships['up_sells'])) {
            $this->command->info('⬆️  Attaching upsells...');
            foreach ($productRelationships['up_sells'] as $productSKU => $skus) {
                $this->attachSimpleRelationship($productSKU, $skus, ProductRelationshipType::UP_SELLS, 'upsells');
            }
        }

        // Grouped children (uses same {sku, quantity} shape as accessories)
        if (! empty($productRelationships['grouped'])) {
            $this->command->info('📦 Attaching grouped children...');
            foreach ($productRelationships['grouped'] as $productSKU => $children) {
                $this->attachChildrenWithQuantity($productSKU, $children, ProductRelationshipType::GROUPED, 'grouped children');
            }
        }

        // Bundle children
        if (! empty($productRelationships['bundle'])) {
            $this->command->info('🎁 Attaching bundle children...');
            foreach ($productRelationships['bundle'] as $productSKU => $children) {
                $this->attachChildrenWithQuantity($productSKU, $children, ProductRelationshipType::BUNDLE, 'bundle children');
            }
        }
    }

    /**
     * Attach children with per-item quantity for grouped/bundle products
     */
    protected function attachChildrenWithQuantity(
        string $productSKU,
        array $children,
        ProductRelationshipType $type,
        string $displayName
    ): void {
        $product = Product::where('sku', $productSKU)->first();

        if (! $product) {
            $this->command->warn("⚠️  Product not found for SKU: {$productSKU}");

            return;
        }

        $skus = collect($children)->pluck('sku')->toArray();
        $quantityMap = collect($children)->keyBy('sku');

        $relatedProducts = Product::whereIn('sku', $skus)->get();

        if ($relatedProducts->isEmpty()) {
            $this->command->warn("⚠️  No {$displayName} found for SKUs: ".implode(', ', $skus));

            return;
        }

        $syncData = [];
        foreach ($relatedProducts as $index => $relatedProduct) {
            $quantity = $quantityMap->get($relatedProduct->sku)['quantity'] ?? 1;
            $syncData[$relatedProduct->id] = [
                'type' => $type->value,
                'quantity' => $quantity,
                'sort_order' => $index,
            ];
        }

        $relation = match ($type) {
            ProductRelationshipType::GROUPED => $product->groupedProducts(),
            ProductRelationshipType::BUNDLE => $product->bundleProducts(),
            default => null,
        };

        if ($relation) {
            $relation->sync($syncData);
        }

        $this->command->info('✅ Attached '.count($syncData)." {$displayName} to {$product->name}");
    }

    /**
     * Attach attribute definitions (and their values) to a product.
     *
     * JSON shape per attribute:
     *   {"slug": "color", "values": ["red", "blue"], "is_variation_attribute": true, "is_visible": true}
     *
     * Both attribute and value slugs must already exist (see AttributeSeeder).
     */
    protected function attachProductAttributes(Product $product, array $attributes): void
    {
        $syncData = [];
        $allValueIds = [];

        foreach ($attributes as $index => $attr) {
            if (empty($attr['slug'])) {
                continue;
            }

            $attribute = ProductAttribute::where('slug', $attr['slug'])->first();

            if (! $attribute) {
                $this->command->warn("  ⚠️  Attribute slug '{$attr['slug']}' not found, skipping");

                continue;
            }

            $valueIds = AttributeValue::where('attribute_id', $attribute->id)
                ->whereIn('slug', $attr['values'] ?? [])
                ->pluck('id')
                ->toArray();

            $syncData[$attribute->id] = [
                'is_variation_attribute' => (bool) ($attr['is_variation_attribute'] ?? false),
                'is_visible' => (bool) ($attr['is_visible'] ?? true),
                'sort_order' => $index,
                'values' => json_encode($valueIds),
            ];

            $allValueIds = array_merge($allValueIds, $valueIds);
        }

        if (! empty($syncData)) {
            $product->attributes()->sync($syncData);
        }

        if (! empty($allValueIds)) {
            $product->attributeValues()->sync(array_unique($allValueIds));
        }
    }

    /**
     * Create variants for a variable product.
     *
     * JSON shape per variant:
     *   {
     *     "sku": "TEST/VAR/001-RED-S",
     *     "name": "Red - Small",
     *     "price": 1500,
     *     "sale_price": null,
     *     "stock_quantity": 25,
     *     "is_default": true,
     *     "attribute_values": [
     *       {"attribute": "color", "value": "red"},
     *       {"attribute": "size", "value": "s"}
     *     ]
     *   }
     */
    protected function createVariants(Product $product, array $variants): void
    {
        foreach ($variants as $index => $variantData) {
            $valueIds = [];
            $attributesPayload = [];

            foreach ($variantData['attribute_values'] ?? [] as $av) {
                $attribute = ProductAttribute::where('slug', $av['attribute'] ?? '')->first();
                if (! $attribute) {
                    continue;
                }
                $value = AttributeValue::where('attribute_id', $attribute->id)
                    ->where('slug', $av['value'] ?? '')
                    ->first();
                if (! $value) {
                    continue;
                }
                $valueIds[] = $value->id;
                $attributesPayload[$attribute->slug] = $value->slug;
            }

            sort($valueIds);
            $attributeHash = ! empty($valueIds) ? md5(implode('-', $valueIds)) : null;

            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'attribute_hash' => $attributeHash,
                'name' => $variantData['name'] ?? null,
                'attributes' => $attributesPayload,
                'sku' => $variantData['sku'] ?? null,
                'price' => $variantData['price'] ?? null,
                'sale_price' => $variantData['sale_price'] ?? null,
                'stock_quantity' => $variantData['stock_quantity'] ?? 0,
                'manage_stock' => $variantData['manage_stock'] ?? true,
                'stock_status' => $variantData['stock_status'] ?? 'in_stock',
                'weight' => $variantData['weight'] ?? null,
                'length' => $variantData['length'] ?? null,
                'width' => $variantData['width'] ?? null,
                'height' => $variantData['height'] ?? null,
                'image_path' => $variantData['image'] ?? null,
                'is_default' => (bool) ($variantData['is_default'] ?? ($index === 0)),
                'is_active' => (bool) ($variantData['is_active'] ?? true),
                'sort_order' => $index,
                'description' => $variantData['description'] ?? null,
            ]);

            if (! empty($valueIds)) {
                $variant->attributeValues()->sync($valueIds);
            }

            $this->command->info("    🎨 Created variant: {$variant->name} (SKU: {$variant->sku})");
        }
    }

    /**
     * Attach accessories with recommended quantity to a product
     */
    protected function attachAccessories(string $productSKU, array $accessories): void
    {
        $product = Product::where('sku', $productSKU)->first();

        if (! $product) {
            $this->command->warn("⚠️  Product not found for SKU: {$productSKU}");

            return;
        }

        $skus = collect($accessories)->pluck('sku')->toArray();
        $quantityMap = collect($accessories)->keyBy('sku');

        $relatedProducts = Product::whereIn('sku', $skus)->get();

        if ($relatedProducts->isEmpty()) {
            $this->command->warn('⚠️  No accessory products found for SKUs: '.implode(', ', $skus));

            return;
        }

        $syncData = [];
        foreach ($relatedProducts as $index => $relatedProduct) {
            $quantity = $quantityMap->get($relatedProduct->sku)['quantity'] ?? 1;
            $syncData[$relatedProduct->id] = [
                'type' => ProductRelationshipType::ACCESSORY->value,
                'quantity' => $quantity,
                'sort_order' => $index,
            ];
        }

        $product->accessories()->sync($syncData);

        $this->command->info('✅ Attached '.count($syncData)." accessories to {$product->name}");
    }

    /**
     * Attach a simple relationship (cross-sell or upsell) — no quantity needed
     */
    protected function attachSimpleRelationship(
        string $productSKU,
        array $skus,
        ProductRelationshipType $type,
        string $displayName
    ): void {
        $product = Product::where('sku', $productSKU)->first();

        if (! $product) {
            $this->command->warn("⚠️  Product not found for SKU: {$productSKU}");

            return;
        }

        $relatedProductIds = Product::whereIn('sku', $skus)->pluck('id')->toArray();

        if (empty($relatedProductIds)) {
            $this->command->warn('⚠️  No products found for SKUs: '.implode(', ', $skus));

            return;
        }

        $syncData = [];
        foreach ($relatedProductIds as $index => $relatedProductId) {
            $syncData[$relatedProductId] = [
                'type' => $type->value,
                'quantity' => 1,
                'sort_order' => $index,
            ];
        }

        match ($type) {
            ProductRelationshipType::CROSS_SELL => $product->crossSells()->sync($syncData),
            ProductRelationshipType::UP_SELLS => $product->upsells()->sync($syncData),
            default => null,
        };

        $this->command->info('✅ Attached '.count($syncData)." {$displayName} to {$product->name}");
    }

    /**
     * Create a product with its images
     */
    protected function createProduct(array $productData, $category = null, $brand = null): Product
    {
        if (empty($productData['name']) || empty($productData['sku'])) {
            throw new \Exception('Product name and SKU are required. Data: '.json_encode($productData));
        }

        // price = the regular/list price from SAP (or JSON seed data)
        // sale_price = only set when there's an active discount (SAP sync handles this automatically)
        // For initial seeding, we set price and leave sale_price null
        $price = $productData['price'] ?? null;
        $type = $this->resolveProductType($productData['type'] ?? null);
        $isVirtual = (bool) ($productData['is_virtual'] ?? false);
        $isDownloadable = (bool) ($productData['is_downloadable'] ?? false);
        $requiresQuotation = (bool) ($productData['requires_quotation'] ?? false);

        $slugParts = array_filter([
            $productData['name'],
            $productData['brand'] ?? '',
            $productData['model_number'] ?? '',
        ]);

        // Publish logic — types that don't need a base price still need an image:
        //   - grouped: price is derived from children
        //   - variable: price comes from variants
        //   - virtual: digital service may be free or post-purchase priced
        //   - requires_quotation: price is by request
        // Other types must have both image and price.
        $hasImage = ! empty($productData['image']);
        $hasPrice = ! empty($price) && $price > 0;
        $priceOptional = $type === ProductType::GROUPED
            || $type === ProductType::VARIABLE
            || $isVirtual
            || $requiresQuotation;
        $status = ($hasImage && ($hasPrice || $priceOptional))
            ? 'published'
            : 'draft';

        if (! $hasImage) {
            $this->command->warn("  ⚠️  No image for \"{$productData['name']}\" — setting to draft");
        }

        if (! $hasPrice && ! $priceOptional) {
            $this->command->warn("  ⚠️  No price for \"{$productData['name']}\" — setting to draft");
        }

        $product = Product::create([
            'name' => $productData['name'],
            'slug' => Str::slug(implode(' ', $slugParts)),
            'sku' => $productData['sku'],
            'type' => $type,
            'is_virtual' => $isVirtual,
            'is_downloadable' => $isDownloadable,
            'download_limit' => $productData['download_limit'] ?? null,
            'download_expiry' => $productData['download_expiry'] ?? null,
            'requires_quotation' => $requiresQuotation,
            'min_order_quantity' => $productData['min_order_quantity'] ?? null,
            'quotation_notes' => $productData['quotation_notes'] ?? null,
            'model_number' => $productData['model_number'] ?? null,
            'stock_quantity' => $productData['stock_quantity'] ?? 100,
            'image_path' => $productData['image'] ?? null,
            'price' => $price,
            'sale_price' => $productData['sale_price'] ?? null,
            'description' => $productData['description'] ?? null,
            'short_description' => $productData['short_description'] ?? null,
            'technical_specification' => $productData['technical_specification'] ?? null,
            'length' => $productData['length'] ?? null,
            'width' => $productData['width'] ?? null,
            'height' => $productData['height'] ?? null,
            'weight' => $productData['weight'] ?? null,
            'meta_title' => $productData['meta_title'] ?? null,
            'meta_description' => $productData['meta_description'] ?? null,
            'meta_keywords' => ! empty($productData['meta_keywords'])
                ? json_encode($productData['meta_keywords'])
                : null,
            'canonical_url' => $productData['canonical_url'] ?? null,
            'status' => $status,
        ]);

        if ($category) {
            $product->categories()->attach($category->id);
        }

        if ($brand) {
            $product->brand_id = $brand->id;
            $product->save();
        }

        $this->createGalleryImages($product, $productData);

        return $product;
    }

    /**
     * Create gallery images for a product
     */
    protected function createGalleryImages(Product $product, array $productData): void
    {
        $sortOrder = 0;

        if (! empty($productData['image'])) {
            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $productData['image'],
                'alt_text' => $product->name,
                'sort_order' => $sortOrder++,
            ]);
        }

        if (! empty($productData['gallery']) && is_array($productData['gallery'])) {
            foreach ($productData['gallery'] as $imagePath) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $imagePath,
                    'alt_text' => $product->name.' - Image '.($sortOrder + 1),
                    'sort_order' => $sortOrder++,
                ]);
            }
        }
    }

    /**
     * Find or create a Brand
     */
    protected function createBrand(string $brand): Brand
    {
        return Brand::firstOrCreate(
            ['name' => $brand],
            ['slug' => Str::slug($brand)]
        );
    }

    /**
     * Find or create a Category
     */
    protected function createCategory(string $category): Category
    {
        return Category::firstOrCreate(
            ['name' => $category],
            ['slug' => Str::slug($category)]
        );
    }

    /**
     * Resolve a JSON-provided type string to a ProductType enum.
     * Falls back to SIMPLE for unrecognised or missing values.
     */
    protected function resolveProductType(?string $type): ProductType
    {
        if (! $type) {
            return ProductType::SIMPLE;
        }

        return ProductType::tryFrom(strtolower($type)) ?? ProductType::SIMPLE;
    }
}
