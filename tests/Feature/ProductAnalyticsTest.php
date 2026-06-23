<?php

use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Enums\ProductVisibility;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductView;
use App\Models\Review;
use Livewire\Livewire;

it('loads the product analytics page', function () {
    actingAsAdmin();

    $product = Product::factory()->create();

    $this->get(route('admin.products.show', $product))->assertOk();
});

it('computes period-scoped units, revenue and gross margin', function () {
    actingAsAdmin();

    $product = Product::factory()->create(['cost_price' => 100_000]); // KES 1,000 unit cost

    $order = Order::factory()->completed()->create();
    OrderItem::factory()->forProduct($product)->create([
        'order_id' => $order->id,
        'quantity' => 3,
        'unit_price_cents' => 500_000,
        'line_total_cents' => 1_500_000,
    ]);

    // An order outside the paid statuses must not count.
    $pending = Order::factory()->create(['status' => OrderStatus::PENDING]);
    OrderItem::factory()->forProduct($product)->create(['order_id' => $pending->id, 'quantity' => 9]);

    $m = Livewire::test('pages::admin.products.show', ['product' => $product])->instance()->metrics();

    expect($m['units'])->toBe(3)
        ->and($m['revenue_cents'])->toBe(1_500_000)
        ->and($m['cost_cents'])->toBe(300_000)
        ->and($m['margin_cents'])->toBe(1_200_000)
        ->and($m['margin_pct'])->toBe(80.0)
        ->and($m['orders'])->toBe(1);
});

it('counts views and view-to-purchase conversion', function () {
    actingAsAdmin();

    $product = Product::factory()->create();

    foreach (range(1, 4) as $i) {
        ProductView::create(['product_id' => $product->id, 'session_id' => "s{$i}", 'viewed_at' => now()]);
    }

    $order = Order::factory()->completed()->create();
    OrderItem::factory()->forProduct($product)->create(['order_id' => $order->id, 'quantity' => 1, 'line_total_cents' => 500_000]);

    $m = Livewire::test('pages::admin.products.show', ['product' => $product])->instance()->metrics();

    expect($m['views'])->toBe(4)
        ->and($m['conversion_pct'])->toBe(25.0);
});

it('respects a custom date range', function () {
    actingAsAdmin();

    $product = Product::factory()->create();

    $recent = Order::factory()->completed()->create();
    $recent->forceFill(['created_at' => now()->subDay()])->save();
    OrderItem::factory()->forProduct($product)->create(['order_id' => $recent->id, 'quantity' => 2]);

    $old = Order::factory()->completed()->create();
    $old->forceFill(['created_at' => now()->subDays(60)])->save();
    OrderItem::factory()->forProduct($product)->create(['order_id' => $old->id, 'quantity' => 5]);

    $m = Livewire::test('pages::admin.products.show', ['product' => $product])
        ->set('dateFrom', now()->subDays(6)->toDateString())
        ->set('dateTo', now()->toDateString())
        ->call('applyCustom')
        ->instance()->metrics();

    expect($m['units'])->toBe(2); // only the recent order falls in the 7-day window
});

it('summarises approved reviews', function () {
    actingAsAdmin();

    $product = Product::factory()->create();
    Review::factory()->count(2)->for($product)->create(['status' => 'approved', 'rating' => 5]);
    Review::factory()->for($product)->create(['status' => 'approved', 'rating' => 3]);
    Review::factory()->for($product)->create(['status' => 'pending', 'rating' => 1]);

    $stats = Livewire::test('pages::admin.products.show', ['product' => $product])->instance()->reviewStats();

    expect($stats['total'])->toBe(3)
        ->and($stats['pending'])->toBe(1)
        ->and($stats['average'])->toBe(4.3); // (5+5+3)/3
});

it('records a product view from the storefront', function () {
    $product = Product::factory()->create([
        'visibility' => ProductVisibility::VISIBLE,
        'status' => ProductStatus::PUBLISHED,
    ]);

    Livewire::test('pages::storefront.product', ['product' => $product]);

    expect(ProductView::where('product_id', $product->id)->count())->toBe(1);
});
