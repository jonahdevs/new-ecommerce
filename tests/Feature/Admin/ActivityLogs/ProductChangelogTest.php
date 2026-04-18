<?php

use App\Models\Product;
use App\Models\User;
use App\Models\Brand;
use App\Enums\ProductStatus;
use Spatie\Activitylog\Models\Activity;

/**
 * Integration tests for Product model changelog tracking
 * 
 * **Validates: Requirements 1.1, 1.8, 1.9**
 * 
 * Tests that the Product model correctly tracks changes to:
 * - name
 * - price
 * - sale_price
 * - sku
 * - stock_quantity
 * - status
 * - brand_id
 */

beforeEach(function () {
    // Create a staff user for testing
    $this->admin = User::factory()->create([
        'email' => 'admin@test.com',
        'is_staff' => true,
    ]);

    $this->actingAs($this->admin);
});

test('product model logs name changes', function () {
    $product = Product::factory()->create([
        'name' => 'Original Product Name',
    ]);

    $product->update(['name' => 'Updated Product Name']);

    $activity = Activity::forSubject($product)->where('event', 'updated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['old']['name'])->toBe('Original Product Name')
        ->and($activity->properties['attributes']['name'])->toBe('Updated Product Name')
        ->and($activity->causer_id)->toBe($this->admin->id);
});

test('product model logs price changes', function () {
    $product = Product::factory()->create([
        'price' => 100.00,
    ]);

    $product->update(['price' => 150.00]);

    $activity = Activity::forSubject($product)->where('event', 'updated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['old']['price'])->toBe('100.00')
        ->and($activity->properties['attributes']['price'])->toBe('150.00')
        ->and($activity->causer_id)->toBe($this->admin->id);
});

test('product model logs sale_price changes', function () {
    $product = Product::factory()->create([
        'price' => 100.00,
        'sale_price' => 80.00,
    ]);

    $product->update(['sale_price' => 70.00]);

    $activity = Activity::forSubject($product)->where('event', 'updated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['old']['sale_price'])->toBe('80.00')
        ->and($activity->properties['attributes']['sale_price'])->toBe('70.00')
        ->and($activity->causer_id)->toBe($this->admin->id);
});

test('product model logs sku changes', function () {
    $product = Product::factory()->create([
        'sku' => 'SKU-001',
    ]);

    $product->update(['sku' => 'SKU-002']);

    $activity = Activity::forSubject($product)->where('event', 'updated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['old']['sku'])->toBe('SKU-001')
        ->and($activity->properties['attributes']['sku'])->toBe('SKU-002')
        ->and($activity->causer_id)->toBe($this->admin->id);
});

test('product model logs stock_quantity changes', function () {
    $product = Product::factory()->create([
        'stock_quantity' => 100,
    ]);

    $product->update(['stock_quantity' => 50]);

    $activity = Activity::forSubject($product)->where('event', 'updated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['old']['stock_quantity'])->toBe(100)
        ->and($activity->properties['attributes']['stock_quantity'])->toBe(50)
        ->and($activity->causer_id)->toBe($this->admin->id);
});

test('product model logs status changes', function () {
    $product = Product::factory()->create([
        'status' => ProductStatus::DRAFT,
    ]);

    $product->update(['status' => ProductStatus::PUBLISHED]);

    $activity = Activity::forSubject($product)->where('event', 'updated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['old']['status'])->toBe(ProductStatus::DRAFT->value)
        ->and($activity->properties['attributes']['status'])->toBe(ProductStatus::PUBLISHED->value)
        ->and($activity->causer_id)->toBe($this->admin->id);
});

test('product model logs brand_id changes', function () {
    $brand1 = Brand::factory()->create();
    $brand2 = Brand::factory()->create();

    $product = Product::factory()->create([
        'brand_id' => $brand1->id,
    ]);

    $product->update(['brand_id' => $brand2->id]);

    $activity = Activity::forSubject($product)->where('event', 'updated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['old']['brand_id'])->toBe($brand1->id)
        ->and($activity->properties['attributes']['brand_id'])->toBe($brand2->id)
        ->and($activity->causer_id)->toBe($this->admin->id);
});

test('product model logs multiple field changes in single update', function () {
    $product = Product::factory()->create([
        'name' => 'Original Name',
        'price' => 100.00,
        'sku' => 'SKU-001',
        'stock_quantity' => 100,
    ]);

    $product->update([
        'name' => 'Updated Name',
        'price' => 150.00,
        'sku' => 'SKU-002',
        'stock_quantity' => 50,
    ]);

    $activity = Activity::forSubject($product)->where('event', 'updated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['old'])->toHaveKeys(['name', 'price', 'sku', 'stock_quantity'])
        ->and($activity->properties['attributes'])->toHaveKeys(['name', 'price', 'sku', 'stock_quantity'])
        ->and($activity->properties['old']['name'])->toBe('Original Name')
        ->and($activity->properties['attributes']['name'])->toBe('Updated Name')
        ->and($activity->properties['old']['price'])->toBe('100.00')
        ->and($activity->properties['attributes']['price'])->toBe('150.00')
        ->and($activity->properties['old']['sku'])->toBe('SKU-001')
        ->and($activity->properties['attributes']['sku'])->toBe('SKU-002')
        ->and($activity->properties['old']['stock_quantity'])->toBe(100)
        ->and($activity->properties['attributes']['stock_quantity'])->toBe(50);
});

test('product model does not log changes to non-tracked fields', function () {
    $product = Product::factory()->create([
        'short_description' => 'Original description',
    ]);

    $product->update(['short_description' => 'Updated description']);

    $activity = Activity::forSubject($product)->where('event', 'updated')->first();

    // No activity should be logged for non-tracked fields
    expect($activity)->toBeNull();
});

test('product model does not create log entry when no tracked fields change', function () {
    $product = Product::factory()->create([
        'name' => 'Product Name',
        'short_description' => 'Original description',
    ]);

    // Update only non-tracked field
    $product->update(['short_description' => 'Updated description']);

    $activity = Activity::forSubject($product)->where('event', 'updated')->first();

    // No activity should be logged
    expect($activity)->toBeNull();
});

test('product model uses correct log name', function () {
    $product = Product::factory()->create([
        'name' => 'Test Product',
    ]);

    $product->update(['name' => 'Updated Product']);

    $activity = Activity::forSubject($product)->first();

    expect($activity->log_name)->toBe('product');
});

test('product model logs changes without causer when not authenticated', function () {
    // Log out to simulate system change
    auth()->logout();

    $product = Product::factory()->create([
        'name' => 'Original Name',
    ]);

    $product->update(['name' => 'Updated Name']);

    $activity = Activity::forSubject($product)->where('event', 'updated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBeNull()
        ->and($activity->properties['old']['name'])->toBe('Original Name')
        ->and($activity->properties['attributes']['name'])->toBe('Updated Name');
});
