<?php

use App\Enums\ProductStatus;
use App\Enums\ProductVisibility;
use App\Enums\StockStatus;
use App\Models\Product;
use Livewire\Livewire;

beforeEach(function () {
    actingAsAdmin();
});

it('loads the products admin index', function () {
    $this->get(route('admin.products.index'))->assertOk();
});

it('reports product KPIs', function () {
    Product::factory()->count(2)->create(['status' => ProductStatus::PUBLISHED, 'stock_status' => StockStatus::IN_STOCK]);
    Product::factory()->create(['status' => ProductStatus::DRAFT, 'stock_status' => StockStatus::OUT_OF_STOCK]);
    Product::factory()->create(['status' => ProductStatus::DRAFT, 'stock_status' => StockStatus::IN_STOCK, 'stock_quantity' => 2, 'low_stock_threshold' => 5]);

    $stats = Livewire::test('pages::admin.products.index')->get('stats');

    expect($stats['published'])->toBe(2)
        ->and($stats['draft'])->toBe(2)
        ->and($stats['out'])->toBe(1)
        ->and($stats['low'])->toBe(1);
});

it('selects all products on the page', function () {
    Product::factory()->count(3)->create();

    Livewire::test('pages::admin.products.index')
        ->set('selectAll', true)
        ->assertCount('selected', 3)
        ->set('selectAll', false)
        ->assertCount('selected', 0);
});

it('bulk updates visibility for selected products', function () {
    $products = Product::factory()->count(3)->create(['visibility' => ProductVisibility::VISIBLE]);

    Livewire::test('pages::admin.products.index')
        ->set('selected', $products->pluck('id')->map(fn ($id) => (string) $id)->all())
        ->call('bulkSetVisibility', ProductVisibility::HIDDEN->value)
        ->assertCount('selected', 0);

    expect(Product::where('visibility', ProductVisibility::HIDDEN)->count())->toBe(3);
});

it('bulk updates stock status for selected products', function () {
    $products = Product::factory()->count(2)->create(['stock_status' => StockStatus::IN_STOCK]);

    Livewire::test('pages::admin.products.index')
        ->set('selected', $products->pluck('id')->map(fn ($id) => (string) $id)->all())
        ->call('bulkSetStock', StockStatus::OUT_OF_STOCK->value);

    expect(Product::where('stock_status', StockStatus::OUT_OF_STOCK)->count())->toBe(2);
});

it('bulk deletes selected products', function () {
    $products = Product::factory()->count(3)->create();
    $keep = Product::factory()->create();

    Livewire::test('pages::admin.products.index')
        ->set('selected', $products->pluck('id')->map(fn ($id) => (string) $id)->all())
        ->call('bulkDelete');

    expect(Product::count())->toBe(1)
        ->and(Product::find($keep->id))->not->toBeNull();
});

it('ignores an invalid bulk visibility value', function () {
    $product = Product::factory()->create(['visibility' => ProductVisibility::VISIBLE]);

    Livewire::test('pages::admin.products.index')
        ->set('selected', [(string) $product->id])
        ->call('bulkSetVisibility', 'bogus');

    expect($product->fresh()->visibility)->toBe(ProductVisibility::VISIBLE);
});
