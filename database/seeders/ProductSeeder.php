<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
        // Load JSON file
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

        // Store product relationships to process after all products are created
        $productRelationships = [
            'cross_sell' => [],
            'upsell' => [],
            'related' => [],
        ];

        $this->command->info('🔄 Creating products...');

        // First pass: Create all main products and their accessories/cross-sells as products
        foreach ($data as $index => $productData) {
            $brand = null;
            $category = null;

            if (!empty($productData['brand'])) {
                $brand = $this->createBrand($productData['brand']);
            }

            if (!empty($productData['category'])) {
                $category = $this->createCategory($productData['category']);
            }

            $product = $this->createProduct($productData, $category, $brand);
            $this->command->info("✅ Created product: {$product->name} (SKU: {$product->sku})");

            // Process cross-sells (formerly accessories)
            if (!empty($productData['accessories']) && is_array($productData['accessories'])) {
                $crossSellSKUs = $this->processProductArray($productData['accessories'], 'cross-sell');
                $productRelationships['cross_sell'][$productData['sku']] = $crossSellSKUs;
                $this->command->info('  🔗 Stored ' . count($crossSellSKUs) . " cross-sell relationships for {$productData['name']}");
            }

            // Process upsells (if exists in your JSON)
            if (!empty($productData['upsells']) && is_array($productData['upsells'])) {
                $upsellSKUs = $this->processProductArray($productData['upsells'], 'upsell');
                $productRelationships['upsell'][$productData['sku']] = $upsellSKUs;
                $this->command->info('  ⬆️  Stored ' . count($upsellSKUs) . " upsell relationships for {$productData['name']}");
            }

            // Process related products (if exists in your JSON)
            if (!empty($productData['related']) && is_array($productData['related'])) {
                $relatedSKUs = $this->processProductArray($productData['related'], 'related');
                $productRelationships['related'][$productData['sku']] = $relatedSKUs;
                $this->command->info('  🔄 Stored ' . count($relatedSKUs) . " related product relationships for {$productData['name']}");
            }
        }

        // Second pass: Attach all product relationships
        $this->attachProductRelationships($productRelationships);

        $this->command->info('✅ Product seeding completed!');
    }

    /**
     * Process an array of products (can be SKUs or full product objects)
     * and return an array of SKUs
     */
    protected function processProductArray(array $products, string $type): array
    {
        $skus = [];

        foreach ($products as $product) {
            if (is_string($product)) {
                // New format: just SKU strings
                $skus[] = $product;
            } else {
                // Old format: full product objects (legacy support)
                $brand = null;
                $category = null;

                if (!empty($product['brand'])) {
                    $brand = $this->createBrand($product['brand']);
                }

                if (!empty($product['category'])) {
                    $category = $this->createCategory($product['category']);
                }

                // Create the product
                $createdProduct = $this->createProduct($product, $category, $brand);
                $this->command->info("  📎 Created {$type}: {$createdProduct->name} (SKU: {$createdProduct->sku})");

                // Store the SKU
                $skus[] = $product['sku'];
            }
        }

        return $skus;
    }

    /**
     * Attach all product relationships (cross-sells, upsells, related)
     */
    protected function attachProductRelationships(array $productRelationships): void
    {
        // Attach cross-sells
        if (!empty($productRelationships['cross_sell'])) {
            $this->command->info('🔗 Attaching cross-sell products...');
            foreach ($productRelationships['cross_sell'] as $productSKU => $crossSellSKUs) {
                $this->attachRelationship($productSKU, $crossSellSKUs, 'cross_sell', 'cross-sells');
            }
        }

        // Attach upsells
        if (!empty($productRelationships['upsell'])) {
            $this->command->info('⬆️  Attaching upsell products...');
            foreach ($productRelationships['upsell'] as $productSKU => $upsellSKUs) {
                $this->attachRelationship($productSKU, $upsellSKUs, 'upsell', 'upsells');
            }
        }

        // Attach related products
        if (!empty($productRelationships['related'])) {
            $this->command->info('🔄 Attaching related products...');
            foreach ($productRelationships['related'] as $productSKU => $relatedSKUs) {
                $this->attachRelationship($productSKU, $relatedSKUs, 'related', 'related products');
            }
        }
    }

    /**
     * Attach a specific relationship type to a product
     */
    protected function attachRelationship(string $productSKU, array $relatedSKUs, string $relationshipType, string $displayName): void
    {
        $product = Product::where('sku', $productSKU)->first();

        if (!$product) {
            $this->command->warn("⚠️  Product not found for SKU: {$productSKU}");
            return;
        }

        // Get IDs of related products
        $relatedProductIds = Product::whereIn('sku', $relatedSKUs)->pluck('id')->toArray();

        if (empty($relatedProductIds)) {
            $this->command->warn("⚠️  No related products found for SKUs: " . implode(', ', $relatedSKUs));
            return;
        }

        // Prepare data with sort_order
        $relationshipData = [];
        foreach ($relatedProductIds as $index => $relatedProductId) {
            $relationshipData[$relatedProductId] = [
                'type' => $relationshipType,
                'sort_order' => $index,
            ];
        }

        // Use the appropriate relationship method
        switch ($relationshipType) {
            case 'cross_sell':
                $product->crossSells()->sync($relationshipData);
                break;
            case 'upsell':
                $product->upsells()->sync($relationshipData);
                break;
            case 'related':
                $product->relatedProducts()->sync($relationshipData);
                break;
        }

        $this->command->info("✅ Attached " . count($relatedProductIds) . " {$displayName} to {$product->name}");
    }

    /**
     * Create a product with its images
     */
    protected function createProduct(array $productData, $category = null, $brand = null): Product
    {
        // Generate retail price
        if (!array_key_exists('price', $productData)) {
            // Generate a random retail price
            $retailPrice = fake()->numberBetween(50000, 500000);

            // Optional sale price (lower than retail)
            $salePrice = fake()->boolean(60)
                ? fake()->numberBetween(20000, $retailPrice - 1000)
                : null;
        } else {
            // Use provided price
            $retailPrice = $productData['price'];
            $salePrice = null;
        }

        // Validate required fields
        if (empty($productData['name']) || empty($productData['sku'])) {
            throw new \Exception('Product name and SKU are required. Product data: ' . json_encode($productData));
        }

        // Create slug from available data
        $slugParts = array_filter([
            $productData['name'],
            $productData['brand'] ?? '',
            $productData['model_number'] ?? '',
        ]);

        $product = Product::create([
            'name' => $productData['name'],
            'slug' => Str::slug(implode(' ', $slugParts)),
            'sku' => $productData['sku'] ?? null,
            'model_number' => $productData['model_number'] ?? null,
            // 'stock_quantity' => $productData['quantity'] ?? 0,
            'stock_quantity' => 100,
            'image_path' => $productData['image'] ?? null,
            'sale_price' => $salePrice,
            'price' => $retailPrice,
            'description' => $productData['description'] ?? null,
            'short_description' => $productData['short_description'] ?? null,
            'technical_specification' => !empty($productData['technical_specification'])
                ? json_encode($productData['technical_specification'])
                : null,
            'meta_title' => $productData['meta_title'] ?? null,
            'meta_description' => $productData['meta_description'] ?? null,
            'meta_keywords' => !empty($productData['meta_keywords'])
                ? json_encode($productData['meta_keywords'])
                : null,
            'canonical_url' => $productData['canonical_url'] ?? null,
            'status' => 'published',
        ]);

        if ($category) {
            $product->categories()->attach($category?->id);
        }

        if ($brand) {
            $product->brand_id = $brand->id;
            $product->save();
        }

        // Create gallery images if they exist
        $this->createGalleryImages($product, $productData);

        return $product;
    }

    /**
     * Create gallery images for a product
     */
    protected function createGalleryImages(Product $product, array $productData): void
    {
        $sortOrder = 0;

        // Create ProductImage for the main image first
        if (!empty($productData['image'])) {
            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $productData['image'],
                'alt_text' => $product->name,
                'sort_order' => $sortOrder++,
            ]);
        }

        // Create ProductImages for gallery images
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
     * Create Brand if it exists
     */
    protected function createBrand(string $brand)
    {
        $brand = Brand::firstOrCreate([
            'name' => $brand,
        ], ['slug' => Str::slug($brand)]);

        return $brand;
    }

    /**
     * Create category if it does not exists
     */
    protected function createCategory(string $category)
    {
        $category = Category::firstOrCreate([
            'name' => $category,
        ], ['slug' => Str::slug($category)]);

        return $category;
    }
}
