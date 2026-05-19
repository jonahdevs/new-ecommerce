<?php

use App\Enums\ProductType;
use App\Models\Product;
use App\Models\ProductVariant;
use Database\Seeders\AttributeSeeder;
use Database\Seeders\ProductSeeder;

/**
 * Verifies that ProductSeeder can produce each ProductType the platform
 * supports, plus the virtual / downloadable / requires_quotation variants.
 *
 * Uses a focused fixture JSON to keep the test fast and decoupled from the
 * full production seed data (700+ products).
 */
beforeEach(function () {
    $this->seed(AttributeSeeder::class);

    $this->fixturePath = sys_get_temp_dir().'/product_seeder_fixture_'.uniqid().'.json';

    file_put_contents($this->fixturePath, json_encode([
        [
            'sku' => 'TST/SIMPLE/A',
            'name' => 'Simple Product A',
            'category' => 'Test Category',
            'type' => 'simple',
            'price' => 1000,
            'image' => 'products/test.jpg',
            'short_description' => 'A simple product.',
        ],
        [
            'sku' => 'TST/SIMPLE/B',
            'name' => 'Simple Product B',
            'category' => 'Test Category',
            'type' => 'simple',
            'price' => 2000,
            'image' => 'products/test.jpg',
            'short_description' => 'Another simple product.',
        ],
        [
            'sku' => 'TST/VAR/A',
            'name' => 'Variable Apron',
            'category' => 'Test Category',
            'type' => 'variable',
            'price' => 1500,
            'image' => 'products/test.jpg',
            'short_description' => 'Variable product.',
            'attributes' => [
                ['slug' => 'color', 'values' => ['red', 'blue'], 'is_variation_attribute' => true],
                ['slug' => 'size', 'values' => ['s', 'm'], 'is_variation_attribute' => true],
            ],
            'variants' => [
                ['sku' => 'TST/VAR/A-RED-S', 'name' => 'Red/S', 'price' => 1500, 'stock_quantity' => 5, 'is_default' => true,
                    'attribute_values' => [['attribute' => 'color', 'value' => 'red'], ['attribute' => 'size', 'value' => 's']]],
                ['sku' => 'TST/VAR/A-BLU-M', 'name' => 'Blue/M', 'price' => 1700, 'stock_quantity' => 8,
                    'attribute_values' => [['attribute' => 'color', 'value' => 'blue'], ['attribute' => 'size', 'value' => 'm']]],
            ],
        ],
        [
            'sku' => 'TST/GRP/A',
            'name' => 'Grouped Kit',
            'category' => 'Test Category',
            'type' => 'grouped',
            'price' => null,
            'image' => 'products/test.jpg',
            'short_description' => 'Grouped product.',
            'grouped_children' => [
                ['sku' => 'TST/SIMPLE/A', 'quantity' => 2],
                ['sku' => 'TST/SIMPLE/B', 'quantity' => 1],
            ],
        ],
        [
            'sku' => 'TST/BNDL/A',
            'name' => 'Bundle Kit',
            'category' => 'Test Category',
            'type' => 'bundle',
            'price' => 2500,
            'image' => 'products/test.jpg',
            'short_description' => 'Bundle product.',
            'bundle_children' => [
                ['sku' => 'TST/SIMPLE/A', 'quantity' => 1],
                ['sku' => 'TST/SIMPLE/B', 'quantity' => 1],
            ],
        ],
        [
            'sku' => 'TST/VIRT/A',
            'name' => 'Virtual Service',
            'category' => 'Test Category',
            'type' => 'simple',
            'is_virtual' => true,
            'price' => 500,
            'image' => 'products/test.jpg',
        ],
        [
            'sku' => 'TST/DOWN/A',
            'name' => 'Downloadable Guide',
            'category' => 'Test Category',
            'type' => 'simple',
            'is_downloadable' => true,
            'download_limit' => 3,
            'download_expiry' => 14,
            'price' => 700,
            'image' => 'products/test.jpg',
        ],
        [
            'sku' => 'TST/QUOTE/A',
            'name' => 'Quotation-only Item',
            'category' => 'Test Category',
            'type' => 'simple',
            'requires_quotation' => true,
            'min_order_quantity' => 5,
            'price' => null,
            'image' => 'products/test.jpg',
        ],
    ]));

    $this->app->instance(ProductSeeder::class, tap(new ProductSeeder, function ($seeder) {
        $seeder->jsonPath = $this->fixturePath;
    }));
});

afterEach(function () {
    if (isset($this->fixturePath) && file_exists($this->fixturePath)) {
        unlink($this->fixturePath);
    }
});

it('creates simple products with correct type', function () {
    $this->seed(ProductSeeder::class);

    $product = Product::where('sku', 'TST/SIMPLE/A')->first();

    expect($product)->not->toBeNull()
        ->and($product->type)->toBe(ProductType::SIMPLE)
        ->and((float) $product->price)->toBe(1000.0)
        ->and($product->status->value)->toBe('published');
});

it('creates variable products with their variants and variation attributes', function () {
    $this->seed(ProductSeeder::class);

    $product = Product::where('sku', 'TST/VAR/A')->with(['variants', 'attributes'])->first();

    expect($product->type)->toBe(ProductType::VARIABLE)
        ->and($product->variants)->toHaveCount(2)
        ->and($product->attributes)->toHaveCount(2);

    $variant = ProductVariant::where('sku', 'TST/VAR/A-RED-S')->first();

    expect($variant)->not->toBeNull()
        ->and((float) $variant->price)->toBe(1500.0)
        ->and($variant->is_default)->toBeTrue()
        ->and($variant->attribute_hash)->not->toBeNull()
        ->and($variant->attributeValues)->toHaveCount(2);

    // variation flag is set on the product_attributes pivot
    $colorPivot = $product->attributes->firstWhere('slug', 'color')->pivot;
    expect((bool) $colorPivot->is_variation_attribute)->toBeTrue();
});

it('creates grouped products and links children with quantities', function () {
    $this->seed(ProductSeeder::class);

    $product = Product::where('sku', 'TST/GRP/A')->with('groupedProducts')->first();

    expect($product->type)->toBe(ProductType::GROUPED)
        ->and($product->groupedProducts)->toHaveCount(2);

    $simpleA = $product->groupedProducts->firstWhere('sku', 'TST/SIMPLE/A');
    expect((int) $simpleA->pivot->quantity)->toBe(2);
});

it('creates bundle products and links children with quantities', function () {
    $this->seed(ProductSeeder::class);

    $product = Product::where('sku', 'TST/BNDL/A')->with('bundleProducts')->first();

    expect($product->type)->toBe(ProductType::BUNDLE)
        ->and($product->bundleProducts)->toHaveCount(2)
        ->and((float) $product->price)->toBe(2500.0);

    // bundle savings calculation should work: children total 3000, bundle 2500 → ~16.7%
    expect($product->bundle_value)->toBe(3000.0)
        ->and($product->bundle_savings_percent)->toBeGreaterThan(0);
});

it('creates virtual products with is_virtual flag set', function () {
    $this->seed(ProductSeeder::class);

    $product = Product::where('sku', 'TST/VIRT/A')->first();

    expect($product->is_virtual)->toBeTrue()
        ->and($product->isVirtual())->toBeTrue();
});

it('creates downloadable products with is_downloadable flag and limits', function () {
    $this->seed(ProductSeeder::class);

    $product = Product::where('sku', 'TST/DOWN/A')->first();

    expect($product->is_downloadable)->toBeTrue()
        ->and($product->isDownloadable())->toBeTrue()
        ->and($product->download_limit)->toBe(3)
        ->and($product->download_expiry)->toBe(14);
});

it('creates quotation-only products with requires_quotation flag', function () {
    $this->seed(ProductSeeder::class);

    $product = Product::where('sku', 'TST/QUOTE/A')->first();

    expect($product->requires_quotation)->toBeTrue()
        ->and((float) $product->min_order_quantity)->toBe(5.0)
        ->and($product->status->value)->toBe('published'); // published despite null price
});
