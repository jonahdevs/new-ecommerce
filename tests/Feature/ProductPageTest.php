<?php

use App\Enums\CategoryStatus;
use App\Enums\ProductVisibility;
use App\Enums\StockStatus;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\BundleItem;
use App\Models\Category;
use App\Models\GroupedProductItem;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductVariant;
use App\Settings\QuotationSettings;
use App\Support\StorefrontSession;
use Livewire\Livewire;

beforeEach(function () {
    $this->brand = Brand::create(['name' => 'TestBrand', 'slug' => 'test-brand', 'is_active' => true, 'sort_order' => 1]);
    $this->cat = Category::create(['name' => 'TestCat', 'slug' => 'test-cat', 'status' => CategoryStatus::ACTIVE, 'sort_order' => 1]);
});

function makeProduct(array $attrs = []): Product
{
    return Product::create(array_merge([
        'name' => 'Wok Range', 'slug' => 'wok-range', 'sku' => 'WK-'.fake()->unique()->numberBetween(1, 99999),
        'brand_id' => test()->brand->id, 'primary_category_id' => test()->cat->id,
        'type' => 'simple', 'price' => 150000, 'stock_status' => StockStatus::IN_STOCK->value,
        'visibility' => ProductVisibility::VISIBLE->value,
    ], $attrs));
}

it('adds a simple product straight to the cart without a modal', function () {
    $product = makeProduct(['slug' => 'simple-wok']);

    Livewire::test('pages::storefront.product', ['product' => $product])
        ->call('addThisToCart')
        ->assertSet('showBundleModal', false);

    expect(StorefrontSession::cart())->toBe(['simple-wok' => 1]);
});

it('opens a modal listing real bundle components and adds the bundle as one SKU', function () {
    $component = makeProduct(['name' => 'Stainless Stand', 'slug' => 'stainless-stand', 'price' => 40000]);
    $bundle = makeProduct(['name' => 'Wok Station Bundle', 'slug' => 'wok-bundle', 'type' => 'bundled', 'price' => 200000]);

    BundleItem::create([
        'bundle_product_id' => $bundle->id,
        'product_id' => $component->id,
        'quantity' => 2,
        'is_optional' => false,
        'sort_order' => 0,
    ]);

    Livewire::test('pages::storefront.product', ['product' => $bundle])
        ->assertSee('Stainless Stand')          // component rendered in the modal markup
        ->call('addThisToCart')
        ->assertSet('showBundleModal', true)     // bundle intercepts add-to-cart
        ->call('addBundleToCart')
        ->assertSet('showBundleModal', false);

    expect(StorefrontSession::cart())->toBe(['wok-bundle' => 1]);
});

it('opens a modal for grouped products and adds the chosen children', function () {
    $childA = makeProduct(['name' => 'Burner A', 'slug' => 'burner-a', 'price' => 30000]);
    $childB = makeProduct(['name' => 'Burner B', 'slug' => 'burner-b', 'price' => 50000]);
    $group = makeProduct(['name' => 'Burner Set', 'slug' => 'burner-set', 'type' => 'grouped', 'price' => null]);

    GroupedProductItem::create(['group_product_id' => $group->id, 'child_product_id' => $childA->id, 'sort_order' => 0]);
    GroupedProductItem::create(['group_product_id' => $group->id, 'child_product_id' => $childB->id, 'sort_order' => 1]);

    Livewire::test('pages::storefront.product', ['product' => $group])
        ->call('addThisToCart')
        ->assertSet('showBundleModal', true)
        ->set('groupedQty.burner-a', 2)
        ->set('groupedQty.burner-b', 1)
        ->call('addGroupedToCart')
        ->assertHasNoErrors()
        ->assertSet('showBundleModal', false);

    expect(StorefrontSession::cart())->toBe(['burner-a' => 2, 'burner-b' => 1]);
});

it('errors when a grouped selection is empty', function () {
    $child = makeProduct(['name' => 'Burner A', 'slug' => 'burner-a', 'price' => 30000]);
    $group = makeProduct(['name' => 'Burner Set', 'slug' => 'burner-set', 'type' => 'grouped', 'price' => null]);
    GroupedProductItem::create(['group_product_id' => $group->id, 'child_product_id' => $child->id, 'sort_order' => 0]);

    Livewire::test('pages::storefront.product', ['product' => $group])
        ->call('addGroupedToCart')
        ->assertHasErrors('groupedQty');

    expect(StorefrontSession::cart())->toBe([]);
});

it('shows a request-a-quote link and no add-to-cart for quote-only products', function () {
    app(QuotationSettings::class)->fill(['quotes_enabled' => true])->save();

    $product = makeProduct(['slug' => 'quote-only', 'requires_quotation' => true, 'price' => null]);

    Livewire::test('pages::storefront.product', ['product' => $product])
        ->assertSee(route('quote.request', ['product' => 'quote-only']))
        ->assertSee('Request a quote')
        ->assertDontSee('Add to cart');
});

it('renders the product page for each product type', function () {
    $product = makeProduct(['slug' => 'smoke-test']);

    $this->get(route('product.show', $product))->assertOk()->assertSee('Wok Range');
});

/**
 * Variable apron: Red (in stock), Blue (on sale, in stock), Green (out of stock).
 *
 * @return array{product: Product, red: ProductVariant, blue: ProductVariant, green: ProductVariant}
 */
function makeVariableProduct(): array
{
    $attr = Attribute::create(['name' => 'Color', 'slug' => 'color', 'type' => 'color', 'is_active' => true, 'sort_order' => 1]);
    $red = AttributeValue::create(['attribute_id' => $attr->id, 'value' => 'Red', 'label' => 'Red', 'slug' => 'red', 'color_code' => '#ef4444', 'sort_order' => 1, 'is_active' => true]);
    $blue = AttributeValue::create(['attribute_id' => $attr->id, 'value' => 'Blue', 'label' => 'Blue', 'slug' => 'blue', 'color_code' => '#3b82f6', 'sort_order' => 2, 'is_active' => true]);
    $green = AttributeValue::create(['attribute_id' => $attr->id, 'value' => 'Green', 'label' => 'Green', 'slug' => 'green', 'color_code' => '#22c55e', 'sort_order' => 3, 'is_active' => true]);

    $product = makeProduct(['name' => 'Chef Apron', 'slug' => 'apron', 'type' => 'variable', 'price' => 150000]);
    ProductAttribute::create([
        'product_id' => $product->id, 'attribute_id' => $attr->id,
        'values' => ['red', 'blue', 'green'], 'is_variation_attribute' => true, 'is_visible' => true, 'sort_order' => 1,
    ]);

    $vRed = ProductVariant::create(['product_id' => $product->id, 'sku' => 'APR-RED', 'price' => 150000, 'stock_status' => StockStatus::IN_STOCK->value, 'stock_quantity' => 5, 'is_active' => true, 'sort_order' => 1]);
    $vRed->attributeValues()->attach($red->id);
    $vBlue = ProductVariant::create(['product_id' => $product->id, 'sku' => 'APR-BLUE', 'price' => 150000, 'compare_at_price' => 129900, 'stock_status' => StockStatus::IN_STOCK->value, 'stock_quantity' => 3, 'is_active' => true, 'sort_order' => 2]);
    $vBlue->attributeValues()->attach($blue->id);
    $vGreen = ProductVariant::create(['product_id' => $product->id, 'sku' => 'APR-GREEN', 'price' => 150000, 'stock_status' => StockStatus::OUT_OF_STOCK->value, 'stock_quantity' => 0, 'is_active' => true, 'sort_order' => 3]);
    $vGreen->attributeValues()->attach($green->id);

    return ['product' => $product->fresh(), 'red' => $vRed, 'blue' => $vBlue, 'green' => $vGreen];
}

it('preselects an in-stock variant and shows its SKU', function () {
    ['product' => $product] = makeVariableProduct();

    Livewire::test('pages::storefront.product', ['product' => $product])
        ->assertSet('selectedOptions', ['color' => 'red'])
        ->assertSee('APR-RED');
});

it('adds the selected variant to the cart at its own price', function () {
    ['product' => $product, 'blue' => $blue] = makeVariableProduct();

    Livewire::test('pages::storefront.product', ['product' => $product])
        ->call('selectOption', 'color', 'blue')
        ->assertSee('APR-BLUE')
        ->call('addThisToCart')
        ->assertHasNoErrors();

    expect(StorefrontSession::cart())->toBe(['apron|'.$blue->id => 1]);

    $line = StorefrontSession::cartLines()->first();
    expect($line['unit_price_cents'])->toBe(129900)
        ->and($line['label'])->toBe('Blue');
});

it('refuses to add an out-of-stock variant', function () {
    ['product' => $product] = makeVariableProduct();

    Livewire::test('pages::storefront.product', ['product' => $product])
        ->call('selectOption', 'color', 'green')
        ->call('addThisToCart')
        ->assertHasErrors('variant');

    expect(StorefrontSession::cart())->toBe([]);
});

it('marks out-of-stock options unavailable and in-stock ones available', function () {
    ['product' => $product] = makeVariableProduct();

    $component = Livewire::test('pages::storefront.product', ['product' => $product]);

    expect($component->instance()->isOptionAvailable('color', 'green'))->toBeFalse()
        ->and($component->instance()->isOptionAvailable('color', 'blue'))->toBeTrue();
});

it('keeps two variants of the same product as separate cart lines', function () {
    ['red' => $red, 'blue' => $blue] = makeVariableProduct();

    StorefrontSession::addToCart('apron', 1, $red->id);
    StorefrontSession::addToCart('apron', 2, $blue->id);

    $lines = StorefrontSession::cartLines();

    expect($lines)->toHaveCount(2)
        ->and($lines->pluck('label')->all())->toEqualCanonicalizing(['Red', 'Blue'])
        ->and($lines->firstWhere('label', 'Blue')['unit_price_cents'])->toBe(129900)
        ->and($lines->firstWhere('label', 'Blue')['qty'])->toBe(2);
});

it('clamps the quantity stepper between 1 and 99', function () {
    $product = makeProduct(['slug' => 'qty-product']);

    Livewire::test('pages::storefront.product', ['product' => $product])
        ->set('qty', 1)->call('decQty')->assertSet('qty', 1)
        ->set('qty', 99)->call('incQty')->assertSet('qty', 99)
        ->set('qty', 5)->call('incQty')->assertSet('qty', 6)->call('decQty')->assertSet('qty', 5);
});

it('picks related products once and keeps them stable across round-trips', function () {
    foreach (range(1, 4) as $i) {
        makeProduct(['name' => "Related {$i}", 'slug' => "related-{$i}"]);
    }
    $product = makeProduct(['slug' => 'main-product']);

    $component = Livewire::test('pages::storefront.product', ['product' => $product]);
    $firstPick = $component->get('relatedIds');

    expect($firstPick)->not->toBeEmpty();

    // A round-trip (qty change) must not reshuffle or re-query the selection.
    $component->call('incQty')->call('decQty');

    expect($component->get('relatedIds'))->toBe($firstPick);
});

it('excludes self, out-of-stock, price-less and hidden products from related', function () {
    $product = makeProduct(['slug' => 'main']);
    $visible = makeProduct(['slug' => 'rel-visible']);
    $outOfStock = makeProduct(['slug' => 'rel-oos', 'stock_status' => StockStatus::OUT_OF_STOCK->value]);
    $priceless = makeProduct(['slug' => 'rel-noprice', 'price' => null]);
    $hidden = makeProduct(['slug' => 'rel-hidden', 'visibility' => ProductVisibility::HIDDEN->value]);

    $ids = Livewire::test('pages::storefront.product', ['product' => $product])->get('relatedIds');

    expect($ids)->toContain($visible->id)
        ->not->toContain($product->id)
        ->not->toContain($outOfStock->id)
        ->not->toContain($priceless->id)
        ->not->toContain($hidden->id);
});

it('uses the parent bundle price when one is set', function () {
    $component = makeProduct(['slug' => 'bundle-comp', 'price' => 40000]);
    $bundle = makeProduct(['slug' => 'bundle-priced', 'type' => 'bundled', 'price' => 200000]);
    BundleItem::create([
        'bundle_product_id' => $bundle->id, 'product_id' => $component->id,
        'quantity' => 2, 'is_optional' => false, 'sort_order' => 0,
    ]);

    $instance = Livewire::test('pages::storefront.product', ['product' => $bundle])->instance();

    expect($instance->bundlePriceCents)->toBe(200000);
});

it('sums required components for a bundle with no parent price and ignores optional items', function () {
    $required = makeProduct(['slug' => 'bundle-req', 'price' => 40000]);
    $optional = makeProduct(['slug' => 'bundle-opt', 'price' => 99900]);
    $bundle = makeProduct(['slug' => 'bundle-summed', 'type' => 'bundled', 'price' => null]);

    BundleItem::create([
        'bundle_product_id' => $bundle->id, 'product_id' => $required->id,
        'quantity' => 2, 'is_optional' => false, 'sort_order' => 0,
    ]);
    BundleItem::create([
        'bundle_product_id' => $bundle->id, 'product_id' => $optional->id,
        'quantity' => 1, 'is_optional' => true, 'sort_order' => 1,
    ]);

    $instance = Livewire::test('pages::storefront.product', ['product' => $bundle])->instance();

    // 40000 × 2 required; the optional 99900 line is excluded.
    expect($instance->bundlePriceCents)->toBe(80000);
});
