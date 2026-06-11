<?php

use App\Enums\CategoryStatus;
use App\Enums\ProductStatus;
use App\Enums\ProductVisibility;
use App\Enums\StockStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
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
        'status' => ProductStatus::PUBLISHED->value,
    ]);
    Product::create([
        'name' => 'Bananas', 'slug' => 'bananas', 'sku' => 'BN-1',
        'brand_id' => $brandB->id, 'primary_category_id' => $cat->id,
        'type' => 'simple', 'price' => 20000, 'stock_status' => StockStatus::IN_STOCK->value,
        'visibility' => ProductVisibility::VISIBLE->value,
        'status' => ProductStatus::PUBLISHED->value,
    ]);

    Livewire::test('pages::storefront.catalog')
        ->assertSee('Apples')
        ->assertSee('Bananas')
        ->set('selectedBrands', [$brandA->id])
        ->assertSee('Apples')
        ->assertDontSee('Bananas');
});

it('filters by price min and max bounds', function () {
    $brand = Brand::create(['name' => 'PriceBrand', 'slug' => 'price-brand', 'is_active' => true, 'sort_order' => 1]);
    $cat = Category::create(['name' => 'PriceCat', 'slug' => 'price-cat', 'status' => CategoryStatus::ACTIVE, 'sort_order' => 1]);

    // Prices stored in cents: 1k, 100k, 500k KES.
    $cheap = Product::create([
        'name' => 'Cheap Whisk', 'slug' => 'cheap-whisk', 'sku' => 'CW-1',
        'brand_id' => $brand->id, 'primary_category_id' => $cat->id,
        'type' => 'simple', 'price' => 100_000, 'stock_status' => StockStatus::IN_STOCK->value,
        'visibility' => ProductVisibility::VISIBLE->value, 'status' => ProductStatus::PUBLISHED->value,
    ]);
    $mid = Product::create([
        'name' => 'Mid Whisk', 'slug' => 'mid-whisk', 'sku' => 'MW-1',
        'brand_id' => $brand->id, 'primary_category_id' => $cat->id,
        'type' => 'simple', 'price' => 10_000_000, 'stock_status' => StockStatus::IN_STOCK->value,
        'visibility' => ProductVisibility::VISIBLE->value, 'status' => ProductStatus::PUBLISHED->value,
    ]);
    $pricey = Product::create([
        'name' => 'Pricey Whisk', 'slug' => 'pricey-whisk', 'sku' => 'PW-1',
        'brand_id' => $brand->id, 'primary_category_id' => $cat->id,
        'type' => 'simple', 'price' => 50_000_000, 'stock_status' => StockStatus::IN_STOCK->value,
        'visibility' => ProductVisibility::VISIBLE->value, 'status' => ProductStatus::PUBLISHED->value,
    ]);

    // Window 50k–200k KES keeps only the mid product (100k KES).
    Livewire::test('pages::storefront.catalog')
        ->set('priceMin', 50_000)
        ->set('priceMax', 200_000)
        ->assertDontSee('Cheap Whisk')
        ->assertSee('Mid Whisk')
        ->assertDontSee('Pricey Whisk');
});

it('filters by minimum review rating', function () {
    $brand = Brand::create(['name' => 'BrandA', 'slug' => 'brand-a', 'is_active' => true, 'sort_order' => 1]);
    $cat = Category::create(['name' => 'TestCat', 'slug' => 'test-cat', 'status' => CategoryStatus::ACTIVE, 'sort_order' => 1]);

    $highlyRated = Product::create([
        'name' => 'Highly Rated Mixer', 'slug' => 'highly-rated-mixer', 'sku' => 'HR-1',
        'brand_id' => $brand->id, 'primary_category_id' => $cat->id,
        'type' => 'simple', 'price' => 10000, 'stock_status' => StockStatus::IN_STOCK->value,
        'visibility' => ProductVisibility::VISIBLE->value,
        'status' => ProductStatus::PUBLISHED->value,
    ]);
    $poorlyRated = Product::create([
        'name' => 'Poorly Rated Mixer', 'slug' => 'poorly-rated-mixer', 'sku' => 'PR-1',
        'brand_id' => $brand->id, 'primary_category_id' => $cat->id,
        'type' => 'simple', 'price' => 20000, 'stock_status' => StockStatus::IN_STOCK->value,
        'visibility' => ProductVisibility::VISIBLE->value,
        'status' => ProductStatus::PUBLISHED->value,
    ]);

    Review::factory()->approved()->create(['product_id' => $highlyRated->id, 'rating' => 5]);
    Review::factory()->approved()->create(['product_id' => $poorlyRated->id, 'rating' => 2]);

    Livewire::test('pages::storefront.catalog')
        ->assertSee('Highly Rated Mixer')
        ->assertSee('Poorly Rated Mixer')
        ->set('minRating', 4)
        ->assertSee('Highly Rated Mixer')
        ->assertDontSee('Poorly Rated Mixer');
});

it('excludes products with only pending reviews from the rating filter', function () {
    $brand = Brand::create(['name' => 'BrandB', 'slug' => 'brand-b', 'is_active' => true, 'sort_order' => 1]);
    $cat = Category::create(['name' => 'TestCat2', 'slug' => 'test-cat-2', 'status' => CategoryStatus::ACTIVE, 'sort_order' => 1]);

    $product = Product::create([
        'name' => 'Pending Review Mixer', 'slug' => 'pending-review-mixer', 'sku' => 'PE-1',
        'brand_id' => $brand->id, 'primary_category_id' => $cat->id,
        'type' => 'simple', 'price' => 10000, 'stock_status' => StockStatus::IN_STOCK->value,
        'visibility' => ProductVisibility::VISIBLE->value,
        'status' => ProductStatus::PUBLISHED->value,
    ]);

    // A 5-star review that is NOT approved must not count toward the filter.
    Review::factory()->create(['product_id' => $product->id, 'rating' => 5]);

    Livewire::test('pages::storefront.catalog')
        ->set('minRating', 4)
        ->assertDontSee('Pending Review Mixer');
});

it('routes /shop/{category} to the category page', function () {
    $cat = Category::create(['name' => 'Ranges', 'slug' => 'ranges', 'status' => CategoryStatus::ACTIVE, 'sort_order' => 1]);

    $response = $this->get(route('category.show', $cat));

    $response->assertOk();
    $response->assertSee('Ranges');
});
