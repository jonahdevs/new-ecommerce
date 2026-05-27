<?php

use App\Enums\CategoryStatus;
use App\Enums\ProductVisibility;
use App\Enums\StockStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Livewire\Livewire;

it('renders the shop page', function () {
    $response = $this->get(route('catalog'));

    $response->assertOk();
    $response->assertSee('Catalog');
    $response->assertSee('Most popular');
    $response->assertSee('In stock — ships now', escape: false);
});

it('filters by selected brand', function () {
    $brandA = Brand::create(['name' => 'BrandA', 'slug' => 'brand-a', 'is_active' => true, 'sort_order' => 1]);
    $brandB = Brand::create(['name' => 'BrandB', 'slug' => 'brand-b', 'is_active' => true, 'sort_order' => 2]);
    $cat = Category::create(['name' => 'TestCat', 'slug' => 'test-cat', 'status' => CategoryStatus::ACTIVE, 'sort_order' => 1]);

    Product::create([
        'name' => 'Apples', 'slug' => 'apples', 'sku' => 'AP-1',
        'brand_id' => $brandA->id, 'primary_category_id' => $cat->id,
        'type' => 'simple', 'price' => 10000, 'stock_status' => StockStatus::IN_STOCK->value,
        'visibility' => ProductVisibility::VISIBLE->value,
    ]);
    Product::create([
        'name' => 'Bananas', 'slug' => 'bananas', 'sku' => 'BN-1',
        'brand_id' => $brandB->id, 'primary_category_id' => $cat->id,
        'type' => 'simple', 'price' => 20000, 'stock_status' => StockStatus::IN_STOCK->value,
        'visibility' => ProductVisibility::VISIBLE->value,
    ]);

    Livewire::test('pages::storefront.catalog')
        ->assertSee('Apples')
        ->assertSee('Bananas')
        ->set('selectedBrands', [$brandA->id])
        ->assertSee('Apples')
        ->assertDontSee('Bananas');
});

it('routes /shop/{category} to the category page', function () {
    $cat = Category::create(['name' => 'Ranges', 'slug' => 'ranges', 'status' => CategoryStatus::ACTIVE, 'sort_order' => 1]);

    $response = $this->get(route('category.show', $cat));

    $response->assertOk();
    $response->assertSee('Ranges');
});
