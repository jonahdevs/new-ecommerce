<?php

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Sap\SapProductSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->syncService = new SapProductSyncService();
});

// ============================================
// PRODUCT TESTS
// ============================================

test('new product: sets price, no sale_price', function () {
    $product = Product::factory()->create([
        'sku' => 'TEST-001',
        'price' => null,
        'sale_price' => null,
    ]);

    $this->syncService->batchSyncProducts([
        ['sku' => 'TEST-001', 'price' => 1500, 'stock_quantity' => 10],
    ]);

    $product->refresh();

    expect((float) $product->price)->toBe(1500.0);
    expect($product->sale_price)->toBeNull();
});

test('price drop: keeps old price, sets sale_price', function () {
    $product = Product::factory()->create([
        'sku' => 'TEST-002',
        'price' => 1500,
        'sale_price' => null,
    ]);

    $this->syncService->batchSyncProducts([
        ['sku' => 'TEST-002', 'price' => 1200, 'stock_quantity' => 10],
    ]);

    $product->refresh();

    expect((float) $product->price)->toBe(1500.0); // "Was" price unchanged
    expect((float) $product->sale_price)->toBe(1200.0); // New lower price as sale
});

test('price increase: updates price, clears sale_price', function () {
    $product = Product::factory()->create([
        'sku' => 'TEST-003',
        'price' => 1500,
        'sale_price' => null,
    ]);

    $this->syncService->batchSyncProducts([
        ['sku' => 'TEST-003', 'price' => 1800, 'stock_quantity' => 10],
    ]);

    $product->refresh();

    expect((float) $product->price)->toBe(1800.0); // Updated to new higher price
    expect($product->sale_price)->toBeNull();
});

test('same price: no change', function () {
    $product = Product::factory()->create([
        'sku' => 'TEST-004',
        'price' => 1500,
        'sale_price' => null,
    ]);

    $this->syncService->batchSyncProducts([
        ['sku' => 'TEST-004', 'price' => 1500, 'stock_quantity' => 10],
    ]);

    $product->refresh();

    expect((float) $product->price)->toBe(1500.0);
    expect($product->sale_price)->toBeNull();
});

test('sale ends: price restored clears sale_price', function () {
    // Product is currently on sale
    $product = Product::factory()->create([
        'sku' => 'TEST-005',
        'price' => 1500, // "Was" price
        'sale_price' => 1200, // Current sale price
    ]);

    // SAP sends the original price back (sale ended)
    $this->syncService->batchSyncProducts([
        ['sku' => 'TEST-005', 'price' => 1500, 'stock_quantity' => 10],
    ]);

    $product->refresh();

    expect((float) $product->price)->toBe(1500.0);
    expect($product->sale_price)->toBeNull(); // Sale cleared
});

test('sale price changes while still on sale', function () {
    // Product is currently on sale
    $product = Product::factory()->create([
        'sku' => 'TEST-006',
        'price' => 1500, // "Was" price
        'sale_price' => 1200, // Current sale price
    ]);

    // SAP sends a different (but still lower) price
    $this->syncService->batchSyncProducts([
        ['sku' => 'TEST-006', 'price' => 1000, 'stock_quantity' => 10],
    ]);

    $product->refresh();

    expect((float) $product->price)->toBe(1500.0); // "Was" price unchanged
    expect((float) $product->sale_price)->toBe(1000.0); // Updated sale price
});

test('price increases above was price while on sale', function () {
    // Product is currently on sale
    $product = Product::factory()->create([
        'sku' => 'TEST-007',
        'price' => 1500, // "Was" price
        'sale_price' => 1200, // Current sale price
    ]);

    // SAP sends a price higher than the "was" price
    $this->syncService->batchSyncProducts([
        ['sku' => 'TEST-007', 'price' => 1800, 'stock_quantity' => 10],
    ]);

    $product->refresh();

    expect((float) $product->price)->toBe(1800.0); // New higher price
    expect($product->sale_price)->toBeNull(); // Sale cleared
});

test('tiny discount does not show as sale', function () {
    $product = Product::factory()->create([
        'sku' => 'TEST-008',
        'price' => 1500,
        'sale_price' => null,
    ]);

    // Less than 1% discount (1500 * 0.01 = 15, so 1495 is only 0.33% off)
    $this->syncService->batchSyncProducts([
        ['sku' => 'TEST-008', 'price' => 1495, 'stock_quantity' => 10],
    ]);

    $product->refresh();

    // Should just update price, not show as sale
    expect((float) $product->price)->toBe(1495.0);
    expect($product->sale_price)->toBeNull();
});

test('stock is always updated', function () {
    $product = Product::factory()->create([
        'sku' => 'TEST-009',
        'price' => 1500,
        'stock_quantity' => 5,
    ]);

    $this->syncService->batchSyncProducts([
        ['sku' => 'TEST-009', 'price' => 1500, 'stock_quantity' => 25],
    ]);

    $product->refresh();

    expect($product->stock_quantity)->toBe(25);
});

test('stock status updates based on quantity', function () {
    $product = Product::factory()->create([
        'sku' => 'TEST-010',
        'price' => 1500,
        'stock_quantity' => 10,
        'stock_status' => 'in_stock',
    ]);

    $this->syncService->batchSyncProducts([
        ['sku' => 'TEST-010', 'price' => 1500, 'stock_quantity' => 0],
    ]);

    $product->refresh();

    expect($product->stock_quantity)->toBe(0);
    expect($product->stock_status)->toBe('out_of_stock');
});

test('sap_last_synced_at is updated', function () {
    $product = Product::factory()->create([
        'sku' => 'TEST-011',
        'price' => 1500,
        'sap_last_synced_at' => null,
    ]);

    $this->syncService->batchSyncProducts([
        ['sku' => 'TEST-011', 'price' => 1500, 'stock_quantity' => 10],
    ]);

    $product->refresh();

    expect($product->sap_last_synced_at)->not->toBeNull();
});

// ============================================
// VARIANT TESTS
// ============================================

test('variant: new variant sets price, no sale_price', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'sku' => 'VAR-001',
        'price' => null,
        'sale_price' => null,
        'stock_quantity' => 0,
    ]);

    $this->syncService->batchSyncProducts([
        ['sku' => 'VAR-001', 'price' => 2000, 'stock_quantity' => 15],
    ]);

    $variant->refresh();

    expect((float) $variant->price)->toBe(2000.0);
    expect($variant->sale_price)->toBeNull();
    expect($variant->stock_quantity)->toBe(15);
});

test('variant: price drop shows as sale', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'sku' => 'VAR-002',
        'price' => 2000,
        'sale_price' => null,
        'stock_quantity' => 10,
    ]);

    $this->syncService->batchSyncProducts([
        ['sku' => 'VAR-002', 'price' => 1600, 'stock_quantity' => 10],
    ]);

    $variant->refresh();

    expect((float) $variant->price)->toBe(2000.0); // "Was" price unchanged
    expect((float) $variant->sale_price)->toBe(1600.0); // Sale price set
});

test('variant: price increase clears sale', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'sku' => 'VAR-003',
        'price' => 2000,
        'sale_price' => 1600,
        'stock_quantity' => 10,
    ]);

    $this->syncService->batchSyncProducts([
        ['sku' => 'VAR-003', 'price' => 2200, 'stock_quantity' => 10],
    ]);

    $variant->refresh();

    expect((float) $variant->price)->toBe(2200.0);
    expect($variant->sale_price)->toBeNull();
});

test('variant: stock updates correctly', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'sku' => 'VAR-004',
        'price' => 2000,
        'stock_quantity' => 5,
        'stock_status' => 'in_stock',
    ]);

    $this->syncService->batchSyncProducts([
        ['sku' => 'VAR-004', 'price' => 2000, 'stock_quantity' => 0],
    ]);

    $variant->refresh();

    expect($variant->stock_quantity)->toBe(0);
    expect($variant->stock_status)->toBe('out_of_stock');
});

test('variant SKU takes priority over product SKU', function () {
    // Create a product with SKU "SHARED-SKU"
    $product = Product::factory()->create([
        'sku' => 'SHARED-SKU',
        'price' => 1000,
        'stock_quantity' => 50,
    ]);

    // Create a variant with the same SKU (edge case - shouldn't happen but let's handle it)
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'sku' => 'SHARED-SKU',
        'price' => 1500,
        'stock_quantity' => 20,
    ]);

    // Sync should update the VARIANT, not the product
    $this->syncService->batchSyncProducts([
        ['sku' => 'SHARED-SKU', 'price' => 1200, 'stock_quantity' => 30],
    ]);

    $product->refresh();
    $variant->refresh();

    // Product should be unchanged
    expect((float) $product->price)->toBe(1000.0);
    expect($product->stock_quantity)->toBe(50);

    // Variant should be updated
    expect((float) $variant->price)->toBe(1500.0); // "Was" price
    expect((float) $variant->sale_price)->toBe(1200.0); // Sale price
    expect($variant->stock_quantity)->toBe(30);
});

test('returns correct type in response for variant', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'sku' => 'VAR-TYPE-TEST',
        'price' => 1000,
        'stock_quantity' => 10,
    ]);

    $result = $this->syncService->batchSyncProducts([
        ['sku' => 'VAR-TYPE-TEST', 'price' => 1000, 'stock_quantity' => 10],
    ]);

    expect($result['successful'])->toBe(1);
    expect($result['details'][0]['type'])->toBe('variant');
    expect($result['details'][0]['variant_id'])->toBe($variant->id);
});

test('returns correct type in response for product', function () {
    $product = Product::factory()->create([
        'sku' => 'PROD-TYPE-TEST',
        'price' => 1000,
        'stock_quantity' => 10,
    ]);

    $result = $this->syncService->batchSyncProducts([
        ['sku' => 'PROD-TYPE-TEST', 'price' => 1000, 'stock_quantity' => 10],
    ]);

    expect($result['successful'])->toBe(1);
    expect($result['details'][0]['type'])->toBe('product');
    expect($result['details'][0]['product_id'])->toBe($product->id);
});

test('unknown SKU returns error', function () {
    $result = $this->syncService->batchSyncProducts([
        ['sku' => 'UNKNOWN-SKU-12345', 'price' => 1000, 'stock_quantity' => 10],
    ]);

    expect($result['failed'])->toBe(1);
    expect($result['details'][0]['success'])->toBeFalse();
    expect($result['details'][0]['error'])->toContain('not found');
});
