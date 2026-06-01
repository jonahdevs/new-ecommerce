<?php

use App\Enums\StockStatus;
use App\Models\Product;
use App\Models\User;
use App\Settings\InventorySettings;
use Livewire\Livewire;

it('hides out-of-stock products from listings only when configured to hide', function () {
    Product::factory()->create(['stock_status' => StockStatus::IN_STOCK]);
    Product::factory()->create(['stock_status' => StockStatus::OUT_OF_STOCK]);

    app(InventorySettings::class)->fill(['out_of_stock_behavior' => 'show'])->save();
    expect(Product::query()->honorStockVisibility()->count())->toBe(2);

    app(InventorySettings::class)->fill(['out_of_stock_behavior' => 'hide'])->save();
    expect(Product::query()->honorStockVisibility()->count())->toBe(1);
});

it('keeps backorderable products visible when hiding out-of-stock', function () {
    Product::factory()->create(['stock_status' => StockStatus::BACKORDER]);

    app(InventorySettings::class)->fill(['out_of_stock_behavior' => 'hide'])->save();

    expect(Product::query()->honorStockVisibility()->count())->toBe(1);
});

it('applies inventory defaults to a new product form', function () {
    $this->actingAs(User::factory()->create());

    app(InventorySettings::class)->fill([
        'allow_backorders_by_default' => true,
        'low_stock_threshold' => 7,
        'track_stock_by_default' => true,
    ])->save();

    Livewire::test('pages::admin.products.form')
        ->assertSet('allow_backorder', true)
        ->assertSet('low_stock_threshold', 7)
        ->assertSet('stock_quantity', 0);
});
