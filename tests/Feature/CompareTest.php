<?php

use App\Enums\CategoryStatus;
use App\Enums\ProductVisibility;
use App\Enums\StockStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Support\StorefrontSession;
use Livewire\Livewire;

beforeEach(function () {
    $this->brand = Brand::create(['name' => 'TestBrand', 'slug' => 'test-brand', 'is_active' => true, 'sort_order' => 1]);
    $this->cat = Category::create(['name' => 'TestCat', 'slug' => 'test-cat', 'status' => CategoryStatus::ACTIVE, 'sort_order' => 1]);
});

function makeCompareProduct(string $slug): Product
{
    return Product::create([
        'name' => 'Wok Range', 'slug' => $slug, 'sku' => 'WK-'.fake()->unique()->numberBetween(1, 99999),
        'brand_id' => test()->brand->id, 'primary_category_id' => test()->cat->id,
        'type' => 'simple', 'price' => 150000, 'stock_status' => StockStatus::IN_STOCK->value,
        'visibility' => ProductVisibility::VISIBLE->value,
    ]);
}

it('renders compared products with add-to-cart and remove actions', function () {
    $a = makeCompareProduct('wok-a');
    $b = makeCompareProduct('wok-b');
    StorefrontSession::toggleCompare($a->slug);
    StorefrontSession::toggleCompare($b->slug);

    Livewire::test('pages::storefront.compare')
        ->assertSee('Buy Now')
        ->assertSee('Brand')
        ->assertSee('Price')
        ->assertSee('Remove')
        ->assertSee($a->name);
});

it('removes a product from the comparison', function () {
    $a = makeCompareProduct('wok-a');
    $b = makeCompareProduct('wok-b');
    StorefrontSession::toggleCompare($a->slug);
    StorefrontSession::toggleCompare($b->slug);

    Livewire::test('pages::storefront.compare')
        ->call('remove', $a->slug)
        ->assertDispatched('compare-updated');

    expect(StorefrontSession::isCompared($a->slug))->toBeFalse()
        ->and(StorefrontSession::isCompared($b->slug))->toBeTrue();
});
