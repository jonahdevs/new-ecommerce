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

    $this->productA = Product::create([
        'name' => 'Wok Range', 'slug' => 'wok-range', 'sku' => 'WK-1',
        'brand_id' => $this->brand->id, 'primary_category_id' => $this->cat->id,
        'type' => 'simple', 'price' => 150000, 'stock_status' => StockStatus::IN_STOCK->value,
        'visibility' => ProductVisibility::VISIBLE->value,
    ]);
    $this->productB = Product::create([
        'name' => 'Pasta Cooker', 'slug' => 'pasta-cooker', 'sku' => 'PC-1',
        'brand_id' => $this->brand->id, 'primary_category_id' => $this->cat->id,
        'type' => 'simple', 'price' => 95000, 'stock_status' => StockStatus::IN_STOCK->value,
        'visibility' => ProductVisibility::VISIBLE->value,
    ]);
});

// ==================================================
// CART PAGE
// ==================================================

it('renders the cart page in its empty state', function () {
    $response = $this->get(route('cart'));

    $response->assertOk();
    $response->assertSee('Your cart is empty.');
    $response->assertSee('Shop the catalog');
});

it('renders cart items when products are in session', function () {
    StorefrontSession::addToCart('wok-range', 2);
    StorefrontSession::addToCart('pasta-cooker', 1);

    Livewire::test('pages::storefront.cart')
        ->assertSee('Wok Range')
        ->assertSee('Pasta Cooker')
        ->assertSee('Cart summary');
});

it('increments and decrements cart quantity', function () {
    StorefrontSession::addToCart('wok-range', 1);

    Livewire::test('pages::storefront.cart')
        ->call('increment', 'wok-range')
        ->call('increment', 'wok-range');

    expect(StorefrontSession::cart()['wok-range'])->toBe(3);

    Livewire::test('pages::storefront.cart')
        ->call('decrement', 'wok-range');

    expect(StorefrontSession::cart()['wok-range'])->toBe(2);
});

it('removes an item from the cart', function () {
    StorefrontSession::addToCart('wok-range', 1);
    StorefrontSession::addToCart('pasta-cooker', 1);

    Livewire::test('pages::storefront.cart')
        ->call('remove', 'wok-range');

    expect(StorefrontSession::cart())->toHaveKeys(['pasta-cooker'])
        ->and(StorefrontSession::cart())->not->toHaveKey('wok-range');
});

// ==================================================
// WISHLIST PAGE
// ==================================================

it('renders the wishlist page in its empty state', function () {
    $response = $this->get(route('wishlist'));

    $response->assertOk();
    $response->assertSee('No saved items yet.');
});

it('toggles a product into and out of the wishlist', function () {
    expect(StorefrontSession::wishlist())->toBeEmpty();

    StorefrontSession::toggleWishlist('wok-range');
    expect(StorefrontSession::wishlist())->toContain('wok-range');

    StorefrontSession::toggleWishlist('wok-range');
    expect(StorefrontSession::wishlist())->not->toContain('wok-range');
});

it('renders wishlist rows and supports add-all-to-cart', function () {
    StorefrontSession::toggleWishlist('wok-range');
    StorefrontSession::toggleWishlist('pasta-cooker');

    Livewire::test('pages::storefront.wishlist')
        ->assertSee('Wok Range')
        ->assertSee('Pasta Cooker')
        ->call('addAllToCart');

    expect(StorefrontSession::cart())->toHaveKeys(['wok-range', 'pasta-cooker']);
});
