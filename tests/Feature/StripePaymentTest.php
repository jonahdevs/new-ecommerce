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
use Livewire\Livewire;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;

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

    // With Paystack off, checkout falls back to redirecting to the payment page.
    app(PaymentSettings::class)->fill(['paystack_enabled' => false])->save();

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

// The storefront payment page now runs on Paystack — its page-level flow
// (method choice → initialize → verify → redirect) is covered by
// PaystackPaymentTest. The Stripe service remains a dormant fallback, so its
// server-side finalize and webhook behaviour is still exercised directly below.

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

it('rejects a stripe payment whose amount does not match the order', function () {
    $order = Order::factory()->create(['status' => OrderStatus::PENDING, 'total_cents' => 174000]);
    $payment = Payment::factory()->stripe()->create([
        'order_id' => $order->id,
        'status' => PaymentStatus::PENDING,
        'amount_cents' => 174000,
        'currency' => 'KES',
        'stripe_payment_intent_id' => 'pi_amount_mismatch',
    ]);

    $intent = PaymentIntent::constructFrom([
        'id' => 'pi_amount_mismatch',
        'amount' => 100, // tampered down to KES 1
        'currency' => 'kes',
        'status' => 'succeeded',
        'latest_charge' => null,
    ]);

    $service = app(StripePaymentService::class);
    (new ReflectionMethod($service, 'finalize'))->invoke($service, $payment, $intent);

    expect($payment->fresh()->status)->toBe(PaymentStatus::FAILED)
        ->and($payment->fresh()->paid_at)->toBeNull()
        ->and($order->fresh()->status)->toBe(OrderStatus::PENDING);
});

it('rejects a stripe payment in the wrong currency', function () {
    $order = Order::factory()->create(['status' => OrderStatus::PENDING, 'total_cents' => 174000]);
    $payment = Payment::factory()->stripe()->create([
        'order_id' => $order->id,
        'status' => PaymentStatus::PENDING,
        'amount_cents' => 174000,
        'currency' => 'KES',
        'stripe_payment_intent_id' => 'pi_currency_mismatch',
    ]);

    $intent = PaymentIntent::constructFrom([
        'id' => 'pi_currency_mismatch',
        'amount' => 174000,
        'currency' => 'usd', // wrong currency
        'status' => 'succeeded',
        'latest_charge' => null,
    ]);

    $service = app(StripePaymentService::class);
    (new ReflectionMethod($service, 'finalize'))->invoke($service, $payment, $intent);

    expect($payment->fresh()->status)->toBe(PaymentStatus::FAILED)
        ->and($order->fresh()->status)->toBe(OrderStatus::PENDING);
});

it('confirms a stripe payment when amount and currency match', function () {
    $order = Order::factory()->create(['status' => OrderStatus::PENDING, 'total_cents' => 174000]);
    $payment = Payment::factory()->stripe()->create([
        'order_id' => $order->id,
        'status' => PaymentStatus::PENDING,
        'amount_cents' => 174000,
        'currency' => 'KES',
        'stripe_payment_intent_id' => 'pi_match',
    ]);

    $intent = PaymentIntent::constructFrom([
        'id' => 'pi_match',
        'amount' => 174000,
        'currency' => 'kes',
        'status' => 'succeeded',
        'latest_charge' => null,
    ]);

    $service = app(StripePaymentService::class);
    (new ReflectionMethod($service, 'finalize'))->invoke($service, $payment, $intent);

    expect($payment->fresh()->status)->toBe(PaymentStatus::SUCCESS)
        ->and($payment->fresh()->paid_at)->not->toBeNull()
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
