<?php

use App\Enums\CategoryStatus;
use App\Enums\OrderStatus;
use App\Enums\ProductVisibility;
use App\Enums\StockStatus;
use App\Models\Address;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\StorefrontSession;
use Livewire\Livewire;

beforeEach(function () {
    $this->brand = Brand::create(['name' => 'TestBrand', 'slug' => 'test-brand', 'is_active' => true, 'sort_order' => 1]);
    $this->cat = Category::create(['name' => 'TestCat', 'slug' => 'test-cat', 'status' => CategoryStatus::ACTIVE, 'sort_order' => 1]);

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
});

it('redirects guests to the login page', function () {
    $this->get(route('checkout'))->assertRedirect(route('login'));
});

it('redirects to the cart when the cart is empty', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('checkout'))->assertRedirect(route('cart'));
});

it('places an order, snapshots totals, and clears the cart', function () {
    $user = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id, 'is_default' => true]);
    $this->actingAs($user);

    StorefrontSession::addToCart('wok-range', 2);
    StorefrontSession::addToCart('pasta-cooker', 1);

    Livewire::test('pages::storefront.checkout')
        ->set('selectedAddressId', $address->id)
        ->set('paymentMethod', 'mpesa')
        ->call('placeOrder')
        ->assertHasNoErrors();

    $order = Order::where('user_id', $user->id)->first();

    expect($order)->not->toBeNull()
        ->and($order->status)->toBe(OrderStatus::PENDING)
        ->and($order->subtotal_cents)->toBe(395000)
        ->and($order->vat_cents)->toBe(63200)
        ->and($order->payment_method)->toBe('mpesa')
        ->and($order->items)->toHaveCount(2);

    expect(StorefrontSession::cart())->toBeEmpty();
});

it('requires a delivery address when delivering', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    StorefrontSession::addToCart('wok-range', 1);

    Livewire::test('pages::storefront.checkout')
        ->set('selectedAddressId', null)
        ->set('deliveryMethod', 'delivery')
        ->set('paymentMethod', 'mpesa')
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
        ->set('paymentMethod', 'card')
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

it('changes delivery and payment methods through their modals', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    StorefrontSession::addToCart('wok-range', 1);

    Livewire::test('pages::storefront.checkout')
        ->call('selectDelivery', 'pickup')
        ->assertSet('deliveryMethod', 'pickup')
        ->assertSet('showDeliveryModal', false)
        ->call('selectPayment', 'bank_transfer')
        ->assertSet('paymentMethod', 'bank_transfer')
        ->assertSet('showPaymentModal', false);
});

it('adds a new address from checkout and selects it as default', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    StorefrontSession::addToCart('wok-range', 1);

    Livewire::test('pages::storefront.checkout')
        ->call('openAddressModal', 'create')
        ->assertSet('addressModalMode', 'create')
        ->set('first_name', 'Anita')
        ->set('last_name', 'Wanjiru')
        ->set('line1', '12 Riverside Drive')
        ->set('city', 'Nairobi')
        ->set('latitude', -1.2921)
        ->set('longitude', 36.8219)
        ->call('saveAddress')
        ->assertHasNoErrors()
        ->assertSet('showAddressModal', false);

    $address = $user->addresses()->first();

    expect($address)->not->toBeNull()
        ->and($address->is_default)->toBeTrue()
        ->and($address->first_name)->toBe('Anita')
        ->and($address->latitude)->toEqual(-1.2921)
        ->and($address->longitude)->toEqual(36.8219);
});
