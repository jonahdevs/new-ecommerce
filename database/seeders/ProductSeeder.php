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

        // Store product-accessory relationships to process after all products are created
        $productAccessories = [];

        $this->command->info('🔄 Creating products and accessories...');

        // First pass: Create all main products and their accessories as products
        foreach ($data['products'] as $index => $productData) {
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

            // If product has accessories, store them for later processing
            // New architecture: accessories are now just SKUs, not full product objects
            if (!empty($productData['accessories']) && is_array($productData['accessories'])) {
                // Check if accessories are SKU strings or full product objects
                $firstAccessory = $productData['accessories'][0];

                if (is_string($firstAccessory)) {
                    // New format: accessories are just SKU strings
                    $productAccessories[$productData['sku']] = $productData['accessories'];
                    $this->command->info('  🔗 Stored ' . count($productData['accessories']) . " accessory SKUs for {$productData['name']}");
                } else {
                    // Old format: accessories are full product objects (legacy support)
                    $accessorySKUs = [];

                    foreach ($productData['accessories'] as $accessoryData) {
                        $accessoryBrand = null;
                        $accessoryCategory = null;

                        if (!empty($accessoryData['brand'])) {
                            $accessoryBrand = $this->createBrand($accessoryData['brand']);
                        }

                        if (!empty($accessoryData['category'])) {
                            $accessoryCategory = $this->createCategory($accessoryData['category']);
                        }

                        // Create the accessory as a product
                        $accessoryProduct = $this->createProduct($accessoryData, $accessoryCategory, $accessoryBrand);
                        $this->command->info("  📎 Created accessory: {$accessoryProduct->name} (SKU: {$accessoryProduct->sku})");

                        // Store the SKU for later attachment
                        $accessorySKUs[] = $accessoryData['sku'];
                    }

                    // Store the relationship for later processing
                    $productAccessories[$productData['sku']] = $accessorySKUs;
                    $this->command->info('  🔗 Stored ' . count($accessorySKUs) . " accessory relationships for {$productData['name']}");
                }
            }
        }

        // Second pass: Attach cross-sell products
        $this->command->info('🔗 Cross-sell products...');
        foreach ($productAccessories as $productSKU => $crossSellSKUs) {
            $product = Product::where('sku', $productSKU)->first();

            if ($product) {
                $crossSellSKUs = Product::whereIn('sku', $crossSellSKUs)->pluck('id')->toArray();

                if (!empty($crossSellSKUs)) {
                    $product->crossSells()->sync($crossSellSKUs);
                    $this->command->info('✅ Attached ' . count($crossSellSKUs) . " cross-sells to {$product->name}");
                }
            } else {
                $this->command->warn("⚠️  Product not found for SKU: {$productSKU}");
            }
        }
    }

    /**
     * Create a product with its images
     */
    protected function createProduct(array $productData, $category = null, $brand = null): Product
    {
        // Generate retail price
        $retailPrice = fake()->numberBetween(50000, 500000);

        // Generate sale price (optional). Ensure it's lower than retail price.
        // If you don't want all products discounted, randomly choose.
        $salePrice = fake()->boolean(60)   // 60% chance of having a sale price
            ? fake()->numberBetween(20000, $retailPrice - 1000)
            : null;

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
            'stock_quantity' => $productData['quantity'] ?? 0,
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
