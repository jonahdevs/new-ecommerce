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
use App\Services\Paystack\PaystackPaymentService;
use App\Support\StorefrontSession;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    config()->set('services.paystack', [
        'public_key' => 'pk_test_fake',
        'secret_key' => 'sk_test_fake',
    ]);
});

/** Fake a successful verify response for the given reference and amount. */
function fakeVerify(string $reference, int $amountCents, string $status = 'success', string $currency = 'KES'): void
{
    Http::fake([
        'https://api.paystack.co/transaction/verify/*' => Http::response([
            'status' => true,
            'data' => [
                'status' => $status,
                'reference' => $reference,
                'amount' => $amountCents,
                'currency' => $currency,
                'channel' => 'mobile_money',
                'authorization' => ['authorization_code' => 'AUTH_x', 'card_type' => 'visa', 'last4' => '4242'],
            ],
        ]),
    ]);
}

it('initializes a transaction and stores a pending payment', function () {
    Http::fake([
        'https://api.paystack.co/transaction/initialize' => Http::response([
            'status' => true,
            'data' => ['access_code' => 'ac_123', 'reference' => 'ignored'],
        ]),
    ]);

    $order = Order::factory()->create(['status' => OrderStatus::PENDING, 'total_cents' => 150000]);

    $payment = app(PaystackPaymentService::class)->initialize($order, 'mpesa');

    expect($payment->status)->toBe(PaymentStatus::PENDING)
        ->and($payment->provider)->toBe('paystack')
        ->and($payment->amount_cents)->toBe(150000)
        ->and($payment->channel)->toBe('mpesa')
        ->and($payment->paystack_reference)->not->toBeNull()
        ->and($payment->paystack_access_code)->toBe('ac_123');

    Http::assertSent(fn ($request) => str_contains($request->url(), '/transaction/initialize')
        && $request['amount'] === 150000
        && $request['currency'] === 'KES'
        && $request['channels'] === ['mobile_money']);
});

it('throws when Paystack rejects the initialization', function () {
    Http::fake([
        'https://api.paystack.co/transaction/initialize' => Http::response(['status' => false, 'message' => 'Invalid key']),
    ]);

    $order = Order::factory()->create(['status' => OrderStatus::PENDING]);

    expect(fn () => app(PaystackPaymentService::class)->initialize($order, 'card'))
        ->toThrow(RuntimeException::class);
});

it('verifies a transaction, marks it paid, and advances the order', function () {
    $order = Order::factory()->create(['status' => OrderStatus::PENDING, 'total_cents' => 150000]);
    $payment = Payment::factory()->paystack()->create([
        'order_id' => $order->id,
        'status' => PaymentStatus::PENDING,
        'amount_cents' => 150000,
        'paystack_reference' => 'ref-ok',
    ]);

    fakeVerify('ref-ok', 150000);

    $result = app(PaystackPaymentService::class)->verify('ref-ok');

    expect($result)->not->toBeNull()
        ->and($payment->fresh()->status)->toBe(PaymentStatus::SUCCESS)
        ->and($payment->fresh()->paid_at)->not->toBeNull()
        ->and($payment->fresh()->channel)->toBe('mobile_money')
        ->and($order->fresh()->status)->toBe(OrderStatus::PROCESSING);
});

it('rejects a verification whose amount does not match the order', function () {
    $order = Order::factory()->create(['status' => OrderStatus::PENDING, 'total_cents' => 150000]);
    $payment = Payment::factory()->paystack()->create([
        'order_id' => $order->id,
        'status' => PaymentStatus::PENDING,
        'amount_cents' => 150000,
        'paystack_reference' => 'ref-tampered',
    ]);

    fakeVerify('ref-tampered', 100); // tampered down to KES 1

    $result = app(PaystackPaymentService::class)->verify('ref-tampered');

    expect($result)->toBeNull()
        ->and($payment->fresh()->status)->toBe(PaymentStatus::FAILED)
        ->and($payment->fresh()->paid_at)->toBeNull()
        ->and($order->fresh()->status)->toBe(OrderStatus::PENDING);
});

it('confirms payment through the Paystack webhook with a valid signature', function () {
    $order = Order::factory()->create(['status' => OrderStatus::PENDING, 'total_cents' => 150000]);
    Payment::factory()->paystack()->create([
        'order_id' => $order->id,
        'status' => PaymentStatus::PENDING,
        'amount_cents' => 150000,
        'paystack_reference' => 'ref-wh',
    ]);

    fakeVerify('ref-wh', 150000);

    $payload = ['event' => 'charge.success', 'data' => ['reference' => 'ref-wh']];
    $raw = json_encode($payload);
    $signature = hash_hmac('sha512', $raw, 'sk_test_fake');

    $this->withHeaders(['X-Paystack-Signature' => $signature])
        ->postJson(route('payments.paystack.webhook'), $payload)
        ->assertOk()
        ->assertJson(['received' => true]);

    expect($order->fresh()->status)->toBe(OrderStatus::PROCESSING);
});

it('rejects a webhook with an invalid signature', function () {
    $payload = ['event' => 'charge.success', 'data' => ['reference' => 'ref-x']];

    $this->withHeaders(['X-Paystack-Signature' => 'wrong'])
        ->postJson(route('payments.paystack.webhook'), $payload)
        ->assertStatus(400)
        ->assertJson(['error' => 'Invalid signature']);
});

it('drives the payment flow from the pay button to confirmation on the payment page', function () {
    config()->set('services.paystack.secret_key', 'sk_test_fake');

    Http::fake([
        'https://api.paystack.co/transaction/initialize' => Http::response([
            'status' => true,
            'data' => ['access_code' => 'ac_page', 'reference' => 'ignored'],
        ]),
    ]);

    $order = Order::factory()->create(['status' => OrderStatus::PENDING, 'total_cents' => 174000]);
    StorefrontSession::addToCart('wok-range', 1);

    $component = Livewire::actingAs($order->user)
        ->test('pages::storefront.payment', ['order' => $order])
        ->assertSet('paystackReady', true)
        ->call('pay')
        ->assertHasNoErrors()
        ->assertDispatched('paystack-open');

    $payment = Payment::where('provider', 'paystack')->first();
    expect($payment)->not->toBeNull()
        ->and($payment->status)->toBe(PaymentStatus::PENDING)
        ->and($order->fresh()->payment_method)->toBe('paystack');

    // No channel restriction sent — the popup offers all enabled methods.
    Http::assertSent(fn ($request) => str_contains($request->url(), '/transaction/initialize')
        && ! array_key_exists('channels', $request->data()));

    // Cart stays until payment confirms.
    expect(StorefrontSession::cart())->not->toBeEmpty();

    fakeVerify($payment->paystack_reference, 174000);

    $component->call('verifyPayment', $payment->paystack_reference)
        ->assertRedirect(route('account.orders.show', $payment->order_id));

    expect($payment->fresh()->status)->toBe(PaymentStatus::SUCCESS)
        ->and(StorefrontSession::cart())->toBeEmpty();
});

it('opens the Paystack popup straight from checkout and reuses the order on re-click', function () {
    config()->set('services.paystack.secret_key', 'sk_test_fake');

    Http::fake([
        'https://api.paystack.co/transaction/initialize' => Http::response([
            'status' => true,
            'data' => ['access_code' => 'ac_checkout', 'reference' => 'ignored'],
        ]),
    ]);

    // Minimal serviceable checkout scaffolding.
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
        'name' => 'Sheffield', 'slug' => 'sheffield-ps', 'driver' => 'self_managed',
        'priority' => 10, 'is_active' => true, 'sort_order' => 1,
    ]);
    $method = ShippingMethod::create([
        'name' => 'Standard', 'slug' => 'standard-ps', 'type' => 'delivery', 'is_active' => true, 'sort_order' => 1,
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
        ->assertHasNoErrors()
        ->assertDispatched('paystack-open');

    $order = Order::first();
    expect($order)->not->toBeNull()
        ->and($order->payment_method)->toBe('paystack')
        ->and(Payment::where('order_id', $order->id)->count())->toBe(1);

    // Re-clicking after dismissing the popup reuses the same order — no duplicate.
    $component->call('placeOrder')->assertDispatched('paystack-open');

    expect(Order::count())->toBe(1)
        ->and(Payment::where('order_id', $order->id)->count())->toBe(2);
});
