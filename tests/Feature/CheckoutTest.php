<?php

use App\Enums\CategoryStatus;
use App\Enums\OrderStatus;
use App\Enums\ProductVisibility;
use App\Enums\StockStatus;
use App\Models\Address;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\CarrierRate;
use App\Models\CarrierZone;
use App\Models\Category;
use App\Models\DeliveryPromotion;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingCarrier;
use App\Models\ShippingMethod;
use App\Models\TaxClass;
use App\Models\User;
use App\Settings\PaymentSettings;
use App\Settings\TaxSettings;
use App\Support\StorefrontSession;
use Livewire\Livewire;

beforeEach(function () {
    $this->brand = Brand::create(['name' => 'TestBrand', 'slug' => 'test-brand', 'is_active' => true, 'sort_order' => 1]);
    $this->cat = Category::create(['name' => 'TestCat', 'slug' => 'test-cat', 'status' => CategoryStatus::ACTIVE, 'sort_order' => 1]);

    // A zone + carrier + rate covering all factory-generated Nairobi addresses.
    $this->zone = DeliveryZone::factory()->centeredAt(-1.29, 36.81, 12000)->create(['name' => 'Nairobi Metro']);

    $carrier = ShippingCarrier::create([
        'name' => 'Sheffield', 'slug' => 'sheffield-test', 'driver' => 'self_managed',
        'priority' => 10, 'is_active' => true, 'sort_order' => 1,
    ]);

    $this->standardMethod = ShippingMethod::create([
        'name' => 'Standard Delivery', 'slug' => 'standard-test', 'type' => 'delivery', 'is_active' => true, 'sort_order' => 1,
    ]);

    CarrierZone::create(['carrier_id' => $carrier->id, 'delivery_zone_id' => $this->zone->id, 'is_active' => true]);

    CarrierRate::create([
        'carrier_id' => $carrier->id,
        'delivery_zone_id' => $this->zone->id,
        'shipping_method_id' => $this->standardMethod->id,
        'rate_type' => 'fixed',
        'base_rate_cents' => 50000,
        'eta_label' => '1–2 days',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    Product::create([
        'name' => 'Wok Range', 'slug' => 'wok-range', 'sku' => 'WK-1',
        'brand_id' => $this->brand->id, 'primary_category_id' => $this->cat->id,
        'type' => 'simple', 'price' => 150000, 'stock_status' => StockStatus::IN_STOCK->value,
        'visibility' => ProductVisibility::VISIBLE->value,
    ]);
    Product::create([
        'name' => 'Pasta Cooker', 'slug' => 'pasta-cooker', 'sku' => 'PC-1',
        'brand_id' => $this->brand->id, 'primary_category_id' => $this->cat->id,
        'type' => 'simple', 'price' => 95000, 'stock_status' => StockStatus::IN_STOCK->value,
        'visibility' => ProductVisibility::VISIBLE->value,
    ]);

    // Products carry no class of their own, so set a 16% store default class.
    $standard = TaxClass::create(['name' => 'Standard rated', 'slug' => 'standard-rated', 'rate' => 16, 'is_active' => true]);
    $settings = app(TaxSettings::class);
    $settings->default_tax_class_id = $standard->id;
    $settings->save();

    // These tests cover order creation, not payment. Disable Paystack so
    // placeOrder takes the payment-page fallback instead of calling the gateway.
    app(PaymentSettings::class)->fill(['paystack_enabled' => false])->save();
});

it('redirects guests to the login page', function () {
    $this->get(route('checkout'))->assertRedirect(route('login'));
});

it('redirects to the cart when the cart is empty', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('checkout'))->assertRedirect(route('cart'));
});

it('places an order, snapshots totals, and redirects to the payment page', function () {
    $user = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id, 'is_default' => true]);
    $this->actingAs($user);

    StorefrontSession::addToCart('wok-range', 2);
    StorefrontSession::addToCart('pasta-cooker', 1);

    Livewire::test('pages::storefront.checkout')
        ->set('selectedAddressId', $address->id)
        ->call('placeOrder')
        ->assertHasNoErrors();

    $order = Order::where('user_id', $user->id)->first();

    // Catalog prices include VAT (TaxSettings::prices_include_tax), so the VAT is
    // the embedded 16% portion of the 395,000 subtotal, not added on top.
    expect($order)->not->toBeNull()
        ->and($order->status)->toBe(OrderStatus::PENDING)
        ->and($order->subtotal_cents)->toBe(395000)
        ->and($order->vat_cents)->toBe(54482)
        ->and($order->payment_method)->toBeNull()
        ->and($order->items)->toHaveCount(2);

    // Each line snapshots the rate it was taxed at.
    expect($order->items->pluck('tax_rate')->unique()->all())->toBe(['16.00'])
        ->and((int) $order->items->sum('tax_cents'))->toBe(54482);

    // Cart stays intact until payment is confirmed on the payment page.
    expect(StorefrontSession::cart())->not->toBeEmpty();
});

it('records the chosen variant on the order line', function () {
    $user = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id, 'is_default' => true]);
    $this->actingAs($user);

    $attr = Attribute::create(['name' => 'Color', 'slug' => 'color', 'type' => 'color', 'is_active' => true, 'sort_order' => 1]);
    $blue = AttributeValue::create(['attribute_id' => $attr->id, 'value' => 'Blue', 'label' => 'Blue', 'slug' => 'blue', 'sort_order' => 1, 'is_active' => true]);

    $apron = Product::create([
        'name' => 'Chef Apron', 'slug' => 'apron', 'sku' => 'APR',
        'brand_id' => $this->brand->id, 'primary_category_id' => $this->cat->id,
        'type' => 'variable', 'price' => 150000, 'stock_status' => StockStatus::IN_STOCK->value,
        'visibility' => ProductVisibility::VISIBLE->value,
    ]);
    $variant = ProductVariant::create([
        'product_id' => $apron->id, 'sku' => 'APR-BLUE', 'price' => 150000, 'compare_at_price' => 129900,
        'stock_status' => StockStatus::IN_STOCK->value, 'stock_quantity' => 5, 'is_active' => true, 'sort_order' => 1,
    ]);
    $variant->attributeValues()->attach($blue->id);

    StorefrontSession::addToCart('apron', 1, $variant->id);

    Livewire::test('pages::storefront.checkout')
        ->set('selectedAddressId', $address->id)
        ->call('placeOrder')
        ->assertHasNoErrors();

    $item = Order::where('user_id', $user->id)->first()->items->first();

    expect($item->product_variant_id)->toBe($variant->id)
        ->and($item->product_sku)->toBe('APR-BLUE')
        ->and($item->unit_price_cents)->toBe(129900)
        ->and($item->product_name)->toContain('Blue');
});

it('requires a delivery address when delivering', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    StorefrontSession::addToCart('wok-range', 1);

    Livewire::test('pages::storefront.checkout')
        ->set('selectedAddressId', null)
        ->set('deliveryMethod', 'delivery')

        ->call('placeOrder')
        ->assertHasErrors('selectedAddressId');

    expect(Order::count())->toBe(0);
});

it('prices delivery from the resolved zone and snapshots it on the order', function () {
    $user = User::factory()->create();
    $address = Address::factory()->create([
        'user_id' => $user->id,
        'is_default' => true,
        'latitude' => -1.2921,
        'longitude' => 36.8219,
    ]);
    $this->actingAs($user);

    StorefrontSession::addToCart('wok-range', 1);

    Livewire::test('pages::storefront.checkout')
        ->set('selectedAddressId', $address->id)
        ->set('deliveryMethod', 'delivery')

        ->call('placeOrder')
        ->assertHasNoErrors();

    $order = Order::where('user_id', $user->id)->first();

    expect($order->delivery_cents)->toBe(50000)
        ->and($order->delivery_zone_id)->toBe($this->zone->id);
});

it('delivers free while a launch promotion is live', function () {
    DeliveryPromotion::factory()->create(['name' => 'Launch free delivery']);

    $user = User::factory()->create();
    $address = Address::factory()->create([
        'user_id' => $user->id,
        'is_default' => true,
        'latitude' => -1.2921,
        'longitude' => 36.8219,
    ]);
    $this->actingAs($user);

    StorefrontSession::addToCart('wok-range', 1);

    Livewire::test('pages::storefront.checkout')
        ->set('selectedAddressId', $address->id)

        ->call('placeOrder')
        ->assertHasNoErrors();

    expect(Order::where('user_id', $user->id)->first()->delivery_cents)->toBe(0);
});

it('blocks delivery to an unserviceable location', function () {
    $user = User::factory()->create();
    $address = Address::factory()->create([
        'user_id' => $user->id,
        'is_default' => true,
        'latitude' => -4.0435, // Mombasa — outside every zone.
        'longitude' => 39.6682,
    ]);
    $this->actingAs($user);

    StorefrontSession::addToCart('wok-range', 1);

    Livewire::test('pages::storefront.checkout')
        ->set('selectedAddressId', $address->id)
        ->set('deliveryMethod', 'delivery')

        ->call('placeOrder')
        ->assertHasErrors('selectedAddressId');

    expect(Order::count())->toBe(0);
});

it('allows pickup without an address', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    StorefrontSession::addToCart('wok-range', 1);

    Livewire::test('pages::storefront.checkout')
        ->set('deliveryMethod', 'pickup')
        ->call('placeOrder')
        ->assertHasNoErrors();

    $order = Order::where('user_id', $user->id)->first();

    expect($order)->not->toBeNull()
        ->and($order->address_id)->toBeNull()
        ->and($order->delivery_cents)->toBe(0);
});

it('defaults the selected address to the user default', function () {
    $user = User::factory()->create();
    Address::factory()->create(['user_id' => $user->id, 'is_default' => false]);
    $default = Address::factory()->create(['user_id' => $user->id, 'is_default' => true]);
    $this->actingAs($user);

    StorefrontSession::addToCart('wok-range', 1);

    Livewire::test('pages::storefront.checkout')
        ->assertSet('selectedAddressId', $default->id);
});

it('selects a different address through the picker modal', function () {
    $user = User::factory()->create();
    $default = Address::factory()->create(['user_id' => $user->id, 'is_default' => true]);
    $other = Address::factory()->create(['user_id' => $user->id, 'is_default' => false]);
    $this->actingAs($user);

    StorefrontSession::addToCart('wok-range', 1);

    Livewire::test('pages::storefront.checkout')
        ->call('openAddressModal', 'select')
        ->assertSet('showAddressModal', true)
        ->assertSet('addressModalMode', 'select')
        ->call('selectAddress', $other->id)
        ->assertSet('selectedAddressId', $other->id)
        ->assertSet('showAddressModal', false);
});

it('changes delivery method through the modal', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    StorefrontSession::addToCart('wok-range', 1);

    Livewire::test('pages::storefront.checkout')
        ->call('selectDelivery', 'pickup')
        ->assertSet('deliveryMethod', 'pickup')
        ->assertSet('showDeliveryModal', false);
});

it('adds a new address from checkout and selects it as default', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    StorefrontSession::addToCart('wok-range', 1);

    Livewire::test('pages::storefront.checkout')
        ->call('openAddressModal', 'create')
        ->assertSet('addressModalMode', 'create')
        ->set('name', 'Anita Wanjiru')
        ->set('line1', '12 Riverside Drive')
        ->set('latitude', -1.2921)
        ->set('longitude', 36.8219)
        ->call('saveAddress')
        ->assertHasNoErrors()
        ->assertSet('showAddressModal', false);

    $address = $user->addresses()->first();

    expect($address)->not->toBeNull()
        ->and($address->is_default)->toBeTrue()
        ->and($address->name)->toBe('Anita Wanjiru')
        ->and($address->latitude)->toEqual(-1.2921)
        ->and($address->longitude)->toEqual(36.8219);
});
