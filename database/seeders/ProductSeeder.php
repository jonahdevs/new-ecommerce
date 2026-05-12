<?php

namespace Database\Seeders;

use App\Enums\ProductRelationshipType;
use App\Enums\ProductType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = database_path('seeders/data/products.json');

        if (!File::exists($jsonPath)) {
            $this->command->error("❌ JSON file not found: {$jsonPath}");
            return;
        }

        $jsonContent = File::get($jsonPath);
        $data = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error('❌ Invalid JSON: ' . json_last_error_msg());
            return;
        }

        // Store relationships to process after all products are created
        $productRelationships = [
            'accessory' => [], // accessories with recommended quantity
            'cross_sell' => [],
            'up_sells' => [],
        ];

        $this->command->info('🔄 Creating products...');

        foreach ($data as $productData) {
            $brand = !empty($productData['brand']) ? $this->createBrand($productData['brand']) : null;
            $category = !empty($productData['category']) ? $this->createCategory($productData['category']) : null;

            $product = $this->createProduct($productData, $category, $brand);
            $this->command->info("✅ Created product: {$product->name} (SKU: {$product->sku})");

            // Accessories — stored with recommended quantity
            // JSON format: array of SKU strings OR array of {sku, quantity} objects
            if (!empty($productData['accessories']) && is_array($productData['accessories'])) {
                $accessories = $this->processAccessoriesArray($productData['accessories']);
                $productRelationships['accessory'][$productData['sku']] = $accessories;
                $this->command->info('  🔧 Stored ' . count($accessories) . " accessory relationships for {$productData['name']}");
            }

            // Cross-sells
            if (!empty($productData['cross_sells']) && is_array($productData['cross_sells'])) {
                $crossSellSKUs = $this->processSkuArray($productData['cross_sells']);
                $productRelationships['cross_sell'][$productData['sku']] = $crossSellSKUs;
                $this->command->info('  🔗 Stored ' . count($crossSellSKUs) . " cross-sell relationships for {$productData['name']}");
            }

            // Upsells
            if (!empty($productData['upsells']) && is_array($productData['upsells'])) {
                $upsellSKUs = $this->processSkuArray($productData['upsells']);
                $productRelationships['up_sells'][$productData['sku']] = $upsellSKUs;
                $this->command->info('  ⬆️  Stored ' . count($upsellSKUs) . " upsell relationships for {$productData['name']}");
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
                $brand = !empty($product['brand']) ? $this->createBrand($product['brand']) : null;
                $category = !empty($product['category']) ? $this->createCategory($product['category']) : null;

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
        if (!empty($productRelationships['accessory'])) {
            $this->command->info('🔧 Attaching accessories...');
            foreach ($productRelationships['accessory'] as $productSKU => $accessories) {
                $this->attachAccessories($productSKU, $accessories);
            }
        }

        // Cross-sells
        if (!empty($productRelationships['cross_sell'])) {
            $this->command->info('🔗 Attaching cross-sells...');
            foreach ($productRelationships['cross_sell'] as $productSKU => $skus) {
                $this->attachSimpleRelationship($productSKU, $skus, ProductRelationshipType::CROSS_SELL, 'cross-sells');
            }
        }

        // Upsells
        if (!empty($productRelationships['up_sells'])) {
            $this->command->info('⬆️  Attaching upsells...');
            foreach ($productRelationships['up_sells'] as $productSKU => $skus) {
                $this->attachSimpleRelationship($productSKU, $skus, ProductRelationshipType::UP_SELLS, 'upsells');
            }
        }
    }

    /**
     * Attach accessories with recommended quantity to a product
     */
    protected function attachAccessories(string $productSKU, array $accessories): void
    {
        $product = Product::where('sku', $productSKU)->first();

        if (!$product) {
            $this->command->warn("⚠️  Product not found for SKU: {$productSKU}");
            return;
        }

        $skus = collect($accessories)->pluck('sku')->toArray();
        $quantityMap = collect($accessories)->keyBy('sku');

        $relatedProducts = Product::whereIn('sku', $skus)->get();

        if ($relatedProducts->isEmpty()) {
            $this->command->warn("⚠️  No accessory products found for SKUs: " . implode(', ', $skus));
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

        $this->command->info("✅ Attached " . count($syncData) . " accessories to {$product->name}");
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

        if (!$product) {
            $this->command->warn("⚠️  Product not found for SKU: {$productSKU}");
            return;
        }

        $relatedProductIds = Product::whereIn('sku', $skus)->pluck('id')->toArray();

        if (empty($relatedProductIds)) {
            $this->command->warn("⚠️  No products found for SKUs: " . implode(', ', $skus));
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

        $this->command->info("✅ Attached " . count($syncData) . " {$displayName} to {$product->name}");
    }
    /**
     * Create a product with its images
     */
    protected function createProduct(array $productData, $category = null, $brand = null): Product
    {
        if (empty($productData['name']) || empty($productData['sku'])) {
            throw new \Exception('Product name and SKU are required. Data: ' . json_encode($productData));
        }

        // price = the regular/list price from SAP (or JSON seed data)
        // sale_price = only set when there's an active discount (SAP sync handles this automatically)
        // For initial seeding, we set price and leave sale_price null
        $price = $productData['price'] ?? null;

        $slugParts = array_filter([
            $productData['name'],
            $productData['brand'] ?? '',
            $productData['model_number'] ?? '',
        ]);

        // Draft if no image or price is missing/zero
        $hasImage = !empty($productData['image']);
        $hasPrice = !empty($price) && $price > 0;
        $status = ($hasImage && $hasPrice) ? 'published' : 'draft';

        if (!$hasImage) {
            $this->command->warn("  ⚠️  No image for \"{$productData['name']}\" — setting to draft");
        }

        if (!$hasPrice) {
            $this->command->warn("  ⚠️  No price for \"{$productData['name']}\" — setting to draft");
        }

        $product = Product::create([
            'name' => $productData['name'],
            'slug' => Str::slug(implode(' ', $slugParts)),
            'sku' => $productData['sku'],
            'type' => ProductType::SIMPLE,
            'model_number' => $productData['model_number'] ?? null,
            'stock_quantity' => 100,
            'image_path' => $productData['image'] ?? null,
            'price' => $price,
            'sale_price' => null, // No discount on initial seed - SAP sync will set this when prices drop
            'description' => $productData['description'] ?? null,
            'short_description' => $productData['short_description'] ?? null,
            'technical_specification' => $productData['technical_specification'] ?? null,
            'length' => $productData['length'] ?? null,
            'width' => $productData['width'] ?? null,
            'height' => $productData['height'] ?? null,
            'weight' => $productData['weight'] ?? null,
            'meta_title' => $productData['meta_title'] ?? null,
            'meta_description' => $productData['meta_description'] ?? null,
            'meta_keywords' => !empty($productData['meta_keywords'])
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

        if (!empty($productData['image'])) {
            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $productData['image'],
                'alt_text' => $product->name,
                'sort_order' => $sortOrder++,
            ]);
        }

        if (!empty($productData['gallery']) && is_array($productData['gallery'])) {
            foreach ($productData['gallery'] as $imagePath) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $imagePath,
                    'alt_text' => $product->name . ' - Image ' . ($sortOrder + 1),
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
}
