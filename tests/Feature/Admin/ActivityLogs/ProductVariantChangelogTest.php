<?php

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

/**
 * Integration tests for ProductVariant model changelog tracking
 * 
 * **Validates: Requirements 1.2, 1.8, 1.9**
 * 
 * Tests that the ProductVariant model correctly tracks changes to:
 * - sku
 * - price
 * - sale_price
 * - stock_quantity
 * - is_active
 */

beforeEach(function () {
    // Create a staff user for testing
    $this->admin = User::factory()->create([
        'email' => 'admin@test.com',
        'is_staff' => true,
    ]);

    $this->actingAs($this->admin);
});

test('product variant model logs sku changes', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'sku' => 'VAR-SKU-001',
        'price' => 100.00,
        'stock_quantity' => 10,
    ]);

    $variant->update(['sku' => 'VAR-SKU-002']);

    $activity = Activity::forSubject($variant)->where('event', 'updated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['old']['sku'])->toBe('VAR-SKU-001')
        ->and($activity->properties['attributes']['sku'])->toBe('VAR-SKU-002')
        ->and($activity->causer_id)->toBe($this->admin->id);
});

test('product variant model logs price changes', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'sku' => 'VAR-SKU-001',
        'price' => 100.00,
        'stock_quantity' => 10,
    ]);

    $variant->update(['price' => 150.00]);

    $activity = Activity::forSubject($variant)->where('event', 'updated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['old']['price'])->toBe('100.00')
        ->and($activity->properties['attributes']['price'])->toBe('150.00')
        ->and($activity->causer_id)->toBe($this->admin->id);
});

test('product variant model logs sale_price changes', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'sku' => 'VAR-SKU-001',
        'price' => 100.00,
        'sale_price' => 80.00,
        'stock_quantity' => 10,
    ]);

    $variant->update(['sale_price' => 70.00]);

    $activity = Activity::forSubject($variant)->where('event', 'updated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['old']['sale_price'])->toBe('80.00')
        ->and($activity->properties['attributes']['sale_price'])->toBe('70.00')
        ->and($activity->causer_id)->toBe($this->admin->id);
});

test('product variant model logs stock_quantity changes', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'sku' => 'VAR-SKU-001',
        'price' => 100.00,
        'stock_quantity' => 100,
    ]);

    $variant->update(['stock_quantity' => 50]);

    $activity = Activity::forSubject($variant)->where('event', 'updated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['old']['stock_quantity'])->toBe(100)
        ->and($activity->properties['attributes']['stock_quantity'])->toBe(50)
        ->and($activity->causer_id)->toBe($this->admin->id);
});

test('product variant model logs is_active changes', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'sku' => 'VAR-SKU-001',
        'price' => 100.00,
        'stock_quantity' => 10,
        'is_active' => true,
    ]);

    $variant->update(['is_active' => false]);

    $activity = Activity::forSubject($variant)->where('event', 'updated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['old']['is_active'])->toBe(true)
        ->and($activity->properties['attributes']['is_active'])->toBe(false)
        ->and($activity->causer_id)->toBe($this->admin->id);
});

test('product variant model logs multiple field changes in single update', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'sku' => 'VAR-SKU-001',
        'price' => 100.00,
        'stock_quantity' => 100,
        'is_active' => true,
    ]);

    $variant->update([
        'sku' => 'VAR-SKU-002',
        'price' => 150.00,
        'stock_quantity' => 50,
        'is_active' => false,
    ]);

    $activity = Activity::forSubject($variant)->where('event', 'updated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['old'])->toHaveKeys(['sku', 'price', 'stock_quantity', 'is_active'])
        ->and($activity->properties['attributes'])->toHaveKeys(['sku', 'price', 'stock_quantity', 'is_active'])
        ->and($activity->properties['old']['sku'])->toBe('VAR-SKU-001')
        ->and($activity->properties['attributes']['sku'])->toBe('VAR-SKU-002')
        ->and($activity->properties['old']['price'])->toBe('100.00')
        ->and($activity->properties['attributes']['price'])->toBe('150.00')
        ->and($activity->properties['old']['stock_quantity'])->toBe(100)
        ->and($activity->properties['attributes']['stock_quantity'])->toBe(50)
        ->and($activity->properties['old']['is_active'])->toBe(true)
        ->and($activity->properties['attributes']['is_active'])->toBe(false);
});

test('product variant model does not log changes to non-tracked fields', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'sku' => 'VAR-SKU-001',
        'price' => 100.00,
        'stock_quantity' => 10,
        'is_active' => true, // Set explicitly to avoid default value change
        'name' => 'Original Variant Name',
    ]);

    $variant->update(['name' => 'Updated Variant Name']);

    $activity = Activity::forSubject($variant)->where('event', 'updated')->first();

    // No activity should be logged for non-tracked fields
    expect($activity)->toBeNull();
});

test('product variant model does not create log entry when no tracked fields change', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'sku' => 'VAR-SKU-001',
        'price' => 100.00,
        'stock_quantity' => 10,
        'is_active' => true, // Set explicitly to avoid default value change
        'name' => 'Original Variant Name',
    ]);

    // Update only non-tracked field
    $variant->update(['name' => 'Updated Variant Name']);

    $activity = Activity::forSubject($variant)->where('event', 'updated')->first();

    // No activity should be logged
    expect($activity)->toBeNull();
});

test('product variant model uses correct log name', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'sku' => 'VAR-SKU-001',
        'price' => 100.00,
        'stock_quantity' => 10,
    ]);

    $variant->update(['sku' => 'VAR-SKU-002']);

    $activity = Activity::forSubject($variant)->where('event', 'updated')->first();

    expect($activity->log_name)->toBe('productvariant');
});

test('product variant model logs changes without causer when not authenticated', function () {
    // Log out to simulate system change
    auth()->logout();

    $product = Product::factory()->create();
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'sku' => 'VAR-SKU-001',
        'price' => 100.00,
        'stock_quantity' => 10,
    ]);

    $variant->update(['sku' => 'VAR-SKU-002']);

    $activity = Activity::forSubject($variant)->where('event', 'updated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBeNull()
        ->and($activity->properties['old']['sku'])->toBe('VAR-SKU-001')
        ->and($activity->properties['attributes']['sku'])->toBe('VAR-SKU-002');
});
