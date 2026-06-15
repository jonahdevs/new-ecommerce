<?php

use App\Enums\ProductLinkType;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\Product;
use App\Models\ProductLink;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('exposes typed link relationships on the model', function () {
    $product = Product::factory()->create();
    $accessory = Product::factory()->create();
    $upsell = Product::factory()->create();

    ProductLink::create(['product_id' => $product->id, 'linked_product_id' => $accessory->id, 'type' => ProductLinkType::ACCESSORY, 'sort_order' => 0]);
    ProductLink::create(['product_id' => $product->id, 'linked_product_id' => $upsell->id, 'type' => ProductLinkType::UPSELL, 'sort_order' => 0]);

    expect($product->accessories->pluck('id')->all())->toBe([$accessory->id])
        ->and($product->upsells->pluck('id')->all())->toBe([$upsell->id])
        ->and($product->spareParts)->toBeEmpty()
        ->and($product->crossSells)->toBeEmpty();
});

it('saves typed product links from the form', function () {
    $product = Product::factory()->create(['status' => ProductStatus::DRAFT]);
    $accessory = Product::factory()->create();
    $spare = Product::factory()->create();

    Livewire::test('pages::admin.products.form', ['product' => $product])
        ->call('addProductLink', 'accessory', $accessory->id)
        ->call('addProductLink', 'spare_part', $spare->id)
        ->call('save');

    expect(ProductLink::where('product_id', $product->id)->count())->toBe(2)
        ->and($product->accessories()->pluck('products.id')->all())->toBe([$accessory->id])
        ->and($product->spareParts()->pluck('products.id')->all())->toBe([$spare->id]);
});

it('does not link the same product twice for one type', function () {
    $product = Product::factory()->create(['status' => ProductStatus::DRAFT]);
    $accessory = Product::factory()->create();

    Livewire::test('pages::admin.products.form', ['product' => $product])
        ->call('addProductLink', 'accessory', $accessory->id)
        ->call('addProductLink', 'accessory', $accessory->id)
        ->assertCount('productLinks.accessory', 1);
});

it('hydrates product links when editing', function () {
    $product = Product::factory()->create(['status' => ProductStatus::DRAFT]);
    $crossSell = Product::factory()->create();

    ProductLink::create(['product_id' => $product->id, 'linked_product_id' => $crossSell->id, 'type' => ProductLinkType::CROSS_SELL, 'sort_order' => 0]);

    Livewire::test('pages::admin.products.form', ['product' => $product])
        ->assertSet('productLinks.cross_sell.0.product_id', $crossSell->id);
});

it('adds via the shared picker for both components and links', function () {
    $bundle = Product::factory()->create(['type' => ProductType::BUNDLE, 'status' => ProductStatus::DRAFT]);
    $component = Product::factory()->create();
    $accessory = Product::factory()->create();

    Livewire::test('pages::admin.products.form', ['product' => $bundle])
        ->call('openLinkPicker', 'component')
        ->assertSet('showLinkPicker', true)
        ->assertSet('linkPickerTarget', 'component')
        ->call('pickLink', $component->id)
        ->assertCount('linkedProducts', 1)
        ->call('openLinkPicker', 'accessory')
        ->call('pickLink', $accessory->id)
        ->assertCount('productLinks.accessory', 1);
});

it('hides already-added and grouped/bundle products from the picker', function () {
    $product = Product::factory()->create(['status' => ProductStatus::DRAFT]);
    $accessory = Product::factory()->create(['name' => 'Pickable Widget']);
    $bundle = Product::factory()->create(['name' => 'Pickable Bundle', 'type' => ProductType::BUNDLE]);

    $component = Livewire::test('pages::admin.products.form', ['product' => $product])
        ->call('openLinkPicker', 'accessory')
        ->set('linkPickerSearch', 'Pickable');

    $ids = $component->get('linkPickerResults')->getCollection()->pluck('id')->all();
    expect($ids)->toContain($accessory->id)
        ->and($ids)->not->toContain($bundle->id)
        ->and($ids)->not->toContain($product->id);

    // Once added, the accessory is no longer offered by the picker.
    $component->call('pickLink', $accessory->id)->set('linkPickerSearch', 'Pickable');
    expect($component->get('linkPickerResults')->getCollection()->pluck('id')->all())->not->toContain($accessory->id);
});

it('paginates the picker and loads more on demand', function () {
    $product = Product::factory()->create(['status' => ProductStatus::DRAFT]);
    Product::factory()->count(25)->create();

    $component = Livewire::test('pages::admin.products.form', ['product' => $product])
        ->call('openLinkPicker', 'accessory');

    expect($component->get('linkPickerResults')->getCollection())->toHaveCount(18)
        ->and($component->get('linkPickerResults')->hasMorePages())->toBeTrue();

    $component->call('loadMoreLinks');

    expect($component->get('linkPickerResults')->getCollection())->toHaveCount(25)
        ->and($component->get('linkPickerResults')->hasMorePages())->toBeFalse();
});

it('persists the required flag and default quantity for accessories', function () {
    $product = Product::factory()->create(['status' => ProductStatus::DRAFT]);
    $accessory = Product::factory()->create();

    Livewire::test('pages::admin.products.form', ['product' => $product])
        ->call('addProductLink', 'accessory', $accessory->id)
        ->set('productLinks.accessory.0.is_required', true)
        ->set('productLinks.accessory.0.default_quantity', 12)
        ->call('save');

    $link = ProductLink::where('product_id', $product->id)->first();

    expect($link->is_required)->toBeTrue()
        ->and($link->default_quantity)->toBe(12);
});

it('hydrates the required flag and default quantity when editing', function () {
    $product = Product::factory()->create(['status' => ProductStatus::DRAFT]);
    $accessory = Product::factory()->create();

    ProductLink::create([
        'product_id' => $product->id,
        'linked_product_id' => $accessory->id,
        'type' => ProductLinkType::ACCESSORY,
        'is_required' => true,
        'default_quantity' => 6,
        'sort_order' => 0,
    ]);

    Livewire::test('pages::admin.products.form', ['product' => $product])
        ->assertSet('productLinks.accessory.0.is_required', true)
        ->assertSet('productLinks.accessory.0.default_quantity', 6);
});

it('does not apply required semantics to non-accessory links', function () {
    $product = Product::factory()->create(['status' => ProductStatus::DRAFT]);
    $upsell = Product::factory()->create();

    Livewire::test('pages::admin.products.form', ['product' => $product])
        ->call('addProductLink', 'upsell', $upsell->id)
        ->set('productLinks.upsell.0.is_required', true)
        ->set('productLinks.upsell.0.default_quantity', 9)
        ->call('save');

    $link = ProductLink::where('product_id', $product->id)->first();

    expect($link->is_required)->toBeFalse()
        ->and($link->default_quantity)->toBe(1);
});

it('removes a product link', function () {
    $product = Product::factory()->create(['status' => ProductStatus::DRAFT]);
    $accessory = Product::factory()->create();

    Livewire::test('pages::admin.products.form', ['product' => $product])
        ->call('addProductLink', 'accessory', $accessory->id)
        ->assertCount('productLinks.accessory', 1)
        ->call('removeProductLink', 'accessory', 0)
        ->assertCount('productLinks.accessory', 0);
});
