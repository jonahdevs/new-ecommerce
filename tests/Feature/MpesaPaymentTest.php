<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Mpesa\MpesaPaymentService;
use App\Services\Stripe\StripePaymentService;
use App\Support\StorefrontSession;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    config()->set('services.mpesa', [
        'env' => 'sandbox',
        'consumer_key' => 'key',
        'consumer_secret' => 'secret',
        'passkey' => 'passkey',
        'shortcode' => '174379',
        'callback_url' => null,
    ]);
});

it('initiates an STK push and stores a pending payment', function () {
    Http::fake([
        '*/oauth/v1/generate*' => Http::response(['access_token' => 'tok']),
        '*/stkpush/*' => Http::response(['MerchantRequestID' => 'mr-1', 'CheckoutRequestID' => 'ws_CO_123', 'ResponseCode' => '0']),
    ]);

    $order = Order::factory()->create(['status' => OrderStatus::PENDING, 'total_cents' => 150000]);
    $payment = app(MpesaPaymentService::class)->initiate($order, '0712345678');

    expect($payment->status)->toBe(PaymentStatus::PENDING)
        ->and($payment->phone)->toBe('254712345678')
        ->and($payment->checkout_request_id)->toBe('ws_CO_123')
        ->and($payment->amount_cents)->toBe(150000);

    Http::assertSent(fn ($request) => str_contains($request->url(), 'stkpush') && $request['Amount'] === 1500);
});

it('marks the payment failed when Daraja rejects the push', function () {
    Http::fake([
        '*/oauth/v1/generate*' => Http::response(['access_token' => 'tok']),
        '*/stkpush/*' => Http::response(['errorCode' => '400.002.02', 'errorMessage' => 'Bad Request']),
    ]);

    $order = Order::factory()->create(['status' => OrderStatus::PENDING]);
    $payment = app(MpesaPaymentService::class)->initiate($order, '0712345678');

    expect($payment->status)->toBe(PaymentStatus::FAILED)
        ->and($payment->result_desc)->toBe('Bad Request');
});

it('marks payment successful and advances the order on a successful query', function () {
    Http::fake([
        '*/oauth/v1/generate*' => Http::response(['access_token' => 'tok']),
        '*/stkpushquery/*' => Http::response(['ResponseCode' => '0', 'ResultCode' => '0', 'ResultDesc' => 'ok']),
    ]);

    $order = Order::factory()->create(['status' => OrderStatus::PENDING]);
    $payment = Payment::factory()->create(['order_id' => $order->id, 'checkout_request_id' => 'ws_CO_9', 'status' => PaymentStatus::PENDING]);

    $status = app(MpesaPaymentService::class)->syncFromQuery($payment);

    expect($status)->toBe(PaymentStatus::SUCCESS)
        ->and($order->fresh()->status)->toBe(OrderStatus::PROCESSING)
        ->and($payment->fresh()->paid_at)->not->toBeNull();
});

it('marks payment cancelled and leaves the order pending when the customer cancels', function () {
    Http::fake([
        '*/oauth/v1/generate*' => Http::response(['access_token' => 'tok']),
        '*/stkpushquery/*' => Http::response(['ResponseCode' => '0', 'ResultCode' => '1032', 'ResultDesc' => 'Request cancelled by user']),
    ]);

    $order = Order::factory()->create(['status' => OrderStatus::PENDING]);
    $payment = Payment::factory()->create(['order_id' => $order->id, 'checkout_request_id' => 'ws_CO_9', 'status' => PaymentStatus::PENDING]);

    expect(app(MpesaPaymentService::class)->syncFromQuery($payment))->toBe(PaymentStatus::CANCELLED)
        ->and($order->fresh()->status)->toBe(OrderStatus::PENDING);
});

it('stays pending while the customer has not yet entered their PIN', function () {
    Http::fake([
        '*/oauth/v1/generate*' => Http::response(['access_token' => 'tok']),
        '*/stkpushquery/*' => Http::response(['errorCode' => '500.001.1001', 'errorMessage' => 'The transaction is being processed']),
    ]);

    $payment = Payment::factory()->create(['checkout_request_id' => 'ws_CO_9', 'status' => PaymentStatus::PENDING]);

    expect(app(MpesaPaymentService::class)->syncFromQuery($payment))->toBe(PaymentStatus::PENDING);
});

it('confirms payment through the Safaricom callback endpoint', function () {
    $order = Order::factory()->create(['status' => OrderStatus::PENDING]);
    $payment = Payment::factory()->create(['order_id' => $order->id, 'checkout_request_id' => 'ws_CO_55', 'status' => PaymentStatus::PENDING]);

    $payload = ['Body' => ['stkCallback' => [
        'MerchantRequestID' => 'mr',
        'CheckoutRequestID' => 'ws_CO_55',
        'ResultCode' => 0,
        'ResultDesc' => 'The service request is processed successfully.',
        'CallbackMetadata' => ['Item' => [
            ['Name' => 'Amount', 'Value' => 1500],
            ['Name' => 'MpesaReceiptNumber', 'Value' => 'QABC123XYZ'],
            ['Name' => 'PhoneNumber', 'Value' => 254712345678],
        ]],
    ]]];

    $this->postJson(route('payments.mpesa.callback'), $payload)
        ->assertOk()
        ->assertJson(['ResultCode' => 0]);

    expect($payment->fresh()->status)->toBe(PaymentStatus::SUCCESS)
        ->and($payment->fresh()->mpesa_receipt)->toBe('QABC123XYZ')
        ->and($order->fresh()->status)->toBe(OrderStatus::PROCESSING);
});

it('drives the M-Pesa payment flow from STK push to confirmation on the payment page', function () {
    $order = Order::factory()->create(['status' => OrderStatus::PENDING, 'total_cents' => 174000]);

    Http::fake([
        '*/oauth/v1/generate*' => Http::response(['access_token' => 'tok']),
        '*/stkpush/*' => Http::response(['MerchantRequestID' => 'mr', 'CheckoutRequestID' => 'ws_CO_77', 'ResponseCode' => '0']),
        '*/stkpushquery/*' => Http::response(['ResponseCode' => '0', 'ResultCode' => '0', 'ResultDesc' => 'ok']),
    ]);

    $stripePayment = Payment::factory()->stripe()->create([
        'order_id' => $order->id, 'status' => PaymentStatus::PENDING,
    ]);

    $this->mock(StripePaymentService::class)
        ->shouldReceive('createPaymentIntent')->andReturn($stripePayment);

    StorefrontSession::addToCart('wok-range', 1);

    $component = Livewire::actingAs($order->user)
        ->test('pages::storefront.payment', ['order' => $order])
        ->set('selectedMethod', 'mpesa')
        ->set('mpesaPhone', '0712345678')
        ->call('payWithMpesa')
        ->assertHasNoErrors()
        ->assertSet('awaitingPayment', true);

    $payment = Payment::where('provider', 'mpesa')->first();
    expect($payment->checkout_request_id)->toBe('ws_CO_77')
        ->and($payment->status)->toBe(PaymentStatus::PENDING);

    // Cart stays until payment confirms.
    expect(StorefrontSession::cart())->not->toBeEmpty();

    $component->call('pollPayment')
        ->assertRedirect(route('account.orders.show', $payment->order_id));

    expect($payment->fresh()->status)->toBe(PaymentStatus::SUCCESS)
        ->and(StorefrontSession::cart())->toBeEmpty();
});

it('rejects an invalid M-Pesa number on the payment page', function () {
    $order = Order::factory()->create(['status' => OrderStatus::PENDING]);
    $stripePayment = Payment::factory()->stripe()->create([
        'order_id' => $order->id, 'status' => PaymentStatus::PENDING,
    ]);

    $this->mock(StripePaymentService::class)
        ->shouldReceive('createPaymentIntent')->andReturn($stripePayment);

    Livewire::actingAs($order->user)
        ->test('pages::storefront.payment', ['order' => $order])
        ->set('selectedMethod', 'mpesa')
        ->set('mpesaPhone', '123')
        ->call('payWithMpesa')
        ->assertHasErrors('mpesaPhone');

    // No new M-Pesa payment created.
    expect(Payment::where('provider', 'mpesa')->count())->toBe(0);
});
