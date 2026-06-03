<?php

use App\Enums\CategoryStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductVisibility;
use App\Enums\StockStatus;
use App\Models\Address;
use App\Models\Brand;
use App\Models\CarrierRate;
use App\Models\CarrierZone;
use App\Models\Category;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ShippingCarrier;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\Stripe\StripePaymentService;
use App\Settings\PaymentSettings;
use App\Support\StorefrontSession;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Stripe\Exception\SignatureVerificationException;

beforeEach(function () {
    config()->set('services.stripe', [
        'key' => 'pk_test_fake',
        'secret' => 'sk_test_fake',
        'webhook_secret' => 'whsec_test_fake',
    ]);
});

it('checkout creates the order and redirects to the payment page', function () {
    $brand = Brand::create(['name' => 'B', 'slug' => 'b', 'is_active' => true, 'sort_order' => 1]);
    $cat = Category::create(['name' => 'C', 'slug' => 'c', 'status' => CategoryStatus::ACTIVE, 'sort_order' => 1]);
    Product::create([
        'name' => 'Wok Range', 'slug' => 'wok-range', 'sku' => 'WK-1',
        'brand_id' => $brand->id, 'primary_category_id' => $cat->id,
        'type' => 'simple', 'price' => 150000, 'stock_status' => StockStatus::IN_STOCK->value,
        'visibility' => ProductVisibility::VISIBLE->value,
    ]);
    $zone = DeliveryZone::factory()->centeredAt(-1.29, 36.81, 12000)->create(['name' => 'Metro']);
    $carrier = ShippingCarrier::create([
        'name' => 'Sheffield', 'slug' => 'sheffield-stripe', 'driver' => 'self_managed',
        'priority' => 10, 'is_active' => true, 'sort_order' => 1,
    ]);
    $method = ShippingMethod::create([
        'name' => 'Standard', 'slug' => 'standard-stripe', 'type' => 'delivery', 'is_active' => true, 'sort_order' => 1,
    ]);
    CarrierZone::create(['carrier_id' => $carrier->id, 'delivery_zone_id' => $zone->id, 'is_active' => true]);
    CarrierRate::create([
        'carrier_id' => $carrier->id, 'delivery_zone_id' => $zone->id,
        'shipping_method_id' => $method->id, 'rate_type' => 'free',
        'base_rate_cents' => 0, 'is_active' => true, 'sort_order' => 1,
    ]);

    $user = User::factory()->create();
    Address::factory()->create(['user_id' => $user->id, 'is_default' => true, 'latitude' => -1.2921, 'longitude' => 36.8219]);
    $this->actingAs($user);

    StorefrontSession::addToCart('wok-range', 1);

    $component = Livewire::test('pages::storefront.checkout')
        ->call('placeOrder')
        ->assertHasNoErrors();

    $order = Order::first();
    expect($order)->not->toBeNull()
        ->and($order->status)->toBe(OrderStatus::PENDING)
        ->and($order->payment_method)->toBeNull();

    $component->assertRedirect(route('payment.page', $order));

    // Cart stays until payment confirms.
    expect(StorefrontSession::cart())->not->toBeEmpty();
});

it('payment page creates a stripe payment intent on mount', function () {
    $order = Order::factory()->create(['status' => OrderStatus::PENDING, 'total_cents' => 174000]);

    $this->mock(StripePaymentService::class)
        ->shouldReceive('createPaymentIntent')
        ->once()
        ->andReturnUsing(function (Order $o) {
            return Payment::factory()->stripe()->create([
                'order_id' => $o->id,
                'status' => PaymentStatus::PENDING,
            ])->withStripeClientSecret('pi_test_abc_secret_xyz');
        });

    $component = Livewire::actingAs($order->user)
        ->test('pages::storefront.payment', ['order' => $order]);

    expect($component->get('stripeClientSecret'))->toBe('pi_test_abc_secret_xyz');
});

it('hides card payments and skips the stripe intent when card is disabled', function () {
    app(PaymentSettings::class)->fill([
        'card_enabled' => false,
        'mpesa_enabled' => true,
    ])->save();

    $order = Order::factory()->create(['status' => OrderStatus::PENDING]);

    $component = Livewire::actingAs($order->user)
        ->test('pages::storefront.payment', ['order' => $order]);

    expect($component->get('stripeClientSecret'))->toBeNull();

    $component->assertSet('selectedMethod', 'mpesa')
        ->assertDontSee('Card Payment')
        ->assertSee('M-Pesa');
});

it('confirms card payment and advances order when stripe reports success', function () {
    $order = Order::factory()->create(['status' => OrderStatus::PENDING]);
    $payment = Payment::factory()->stripe()->create([
        'order_id' => $order->id,
        'status' => PaymentStatus::PENDING,
        'stripe_payment_intent_id' => 'pi_test_111',
    ]);

    $this->mock(StripePaymentService::class)
        ->shouldReceive('createPaymentIntent')->andReturn($payment)
        ->shouldReceive('confirmPaymentIntent')
        ->with('pi_test_111')
        ->andReturnUsing(function () use ($payment, $order) {
            $payment->update(['status' => PaymentStatus::SUCCESS, 'paid_at' => now()]);
            $order->update(['status' => OrderStatus::PROCESSING]);

            return $payment->fresh();
        });

    Livewire::actingAs($order->user)
        ->test('pages::storefront.payment', ['order' => $order])
        ->dispatch('stripe-payment-confirmed', paymentIntentId: 'pi_test_111')
        ->assertRedirect(route('account.orders.show', $order->id));

    expect($payment->fresh()->status)->toBe(PaymentStatus::SUCCESS)
        ->and($order->fresh()->payment_method)->toBe('card')
        ->and($order->fresh()->status)->toBe(OrderStatus::PROCESSING);
});

it('initiates mpesa stk push from the payment page', function () {
    config()->set('services.mpesa', [
        'env' => 'sandbox', 'consumer_key' => 'key', 'consumer_secret' => 'secret',
        'passkey' => 'passkey', 'shortcode' => '174379', 'callback_url' => null,
    ]);

    $order = Order::factory()->create(['status' => OrderStatus::PENDING, 'total_cents' => 174000]);
    $stripePayment = Payment::factory()->stripe()->create([
        'order_id' => $order->id, 'status' => PaymentStatus::PENDING,
    ]);

    Http::fake([
        '*/oauth/v1/generate*' => Http::response(['access_token' => 'tok']),
        '*/stkpush/*' => Http::response(['MerchantRequestID' => 'mr', 'CheckoutRequestID' => 'ws_CO_99', 'ResponseCode' => '0']),
    ]);

    $this->mock(StripePaymentService::class)
        ->shouldReceive('createPaymentIntent')->andReturn($stripePayment);

    Livewire::actingAs($order->user)
        ->test('pages::storefront.payment', ['order' => $order])
        ->set('selectedMethod', 'mpesa')
        ->set('mpesaPhone', '0712345678')
        ->call('payWithMpesa')
        ->assertHasNoErrors()
        ->assertSet('awaitingPayment', true);

    expect(Payment::where('provider', 'mpesa')->first())->not->toBeNull()
        ->and($order->fresh()->payment_method)->toBe('mpesa');
});

it('confirms payment through the stripe webhook endpoint', function () {
    $order = Order::factory()->create(['status' => OrderStatus::PENDING]);
    $payment = Payment::factory()->stripe()->create([
        'order_id' => $order->id,
        'status' => PaymentStatus::PENDING,
        'stripe_payment_intent_id' => 'pi_test_wh',
    ]);

    $this->mock(StripePaymentService::class)
        ->shouldReceive('handleWebhook')
        ->once()
        ->andReturnUsing(function () use ($payment, $order) {
            $payment->update(['status' => PaymentStatus::SUCCESS, 'paid_at' => now()]);
            $order->update(['status' => OrderStatus::PROCESSING]);
        });

    $this->postJson(route('payments.stripe.webhook'), [], ['Stripe-Signature' => 'fake'])
        ->assertOk()
        ->assertJson(['received' => true]);

    expect($payment->fresh()->status)->toBe(PaymentStatus::SUCCESS)
        ->and($order->fresh()->status)->toBe(OrderStatus::PROCESSING);
});

it('returns 400 for an invalid stripe webhook signature', function () {
    $this->mock(StripePaymentService::class)
        ->shouldReceive('handleWebhook')
        ->andThrow(new SignatureVerificationException('Invalid signature', null, null));

    $this->postJson(route('payments.stripe.webhook'), [], ['Stripe-Signature' => 'bad'])
        ->assertStatus(400)
        ->assertJson(['error' => 'Invalid signature']);
});
