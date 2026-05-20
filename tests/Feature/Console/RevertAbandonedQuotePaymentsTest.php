<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\QuoteStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

function makeQuoteOrder(User $user, array $orderOverrides = [], array $quoteOverrides = []): array
{
    static $seq = 0;
    $seq++;

    $quote = Quote::factory()->create(array_merge([
        'user_id' => $user->id,
        'status' => QuoteStatus::ACCEPTED,
        'accepted_at' => now()->subHour(),
        'expires_at' => now()->addDays(5),
        'subtotal_cents' => 10000,
        'total_cents' => 10000,
    ], $quoteOverrides));

    $order = Order::factory()->create(array_merge([
        'user_id' => $user->id,
        'quote_id' => $quote->id,
        'reference' => sprintf('ORD-TEST-%06d', $seq),
        'status' => OrderStatus::PENDING,
        'payment_status' => PaymentStatus::PENDING,
        'expires_at' => now()->subMinutes(5),
    ], $orderOverrides));

    $payment = Payment::create([
        'order_id' => $order->id,
        'status' => PaymentStatus::PENDING,
        'amount_cents' => 10000,
        'currency' => 'KES',
        'gateway' => 'stripe',
        'meta' => [],
    ]);

    return compact('quote', 'order', 'payment');
}

it('reverts an abandoned quote payment — cancels order, reverts quote to SENT', function () {
    ['quote' => $quote, 'order' => $order, 'payment' => $payment] = makeQuoteOrder($this->user);

    $this->artisan('quotations:revert-abandoned-payments')
        ->assertSuccessful();

    expect($order->fresh()->status)->toBe(OrderStatus::CANCELLED)
        ->and($order->fresh()->payment_status)->toBe(PaymentStatus::CANCELLED)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::CANCELLED)
        ->and($quote->fresh()->status)->toBe(QuoteStatus::SENT)
        ->and($quote->fresh()->accepted_at)->toBeNull();
});

it('creates status history entries for both the order and the quote', function () {
    ['quote' => $quote, 'order' => $order] = makeQuoteOrder($this->user);

    $this->artisan('quotations:revert-abandoned-payments')->assertSuccessful();

    expect($order->statusHistories()->where('to_status', OrderStatus::CANCELLED->value)->exists())->toBeTrue()
        ->and($quote->statusHistories()->where('from_status', QuoteStatus::ACCEPTED->value)
            ->where('to_status', QuoteStatus::SENT->value)->exists())->toBeTrue();
});

it('skips orders that have already been paid', function () {
    ['quote' => $quote, 'order' => $order] = makeQuoteOrder($this->user, [
        'payment_status' => PaymentStatus::PAID,
        'status' => OrderStatus::PROCESSING,
    ]);

    $this->artisan('quotations:revert-abandoned-payments')->assertSuccessful();

    expect($order->fresh()->status)->toBe(OrderStatus::PROCESSING)
        ->and($quote->fresh()->status)->toBe(QuoteStatus::ACCEPTED);
});

it('skips orders whose payment window has not expired yet', function () {
    ['quote' => $quote, 'order' => $order] = makeQuoteOrder($this->user, [
        'expires_at' => now()->addMinutes(10),
    ]);

    $this->artisan('quotations:revert-abandoned-payments')->assertSuccessful();

    expect($order->fresh()->status)->toBe(OrderStatus::PENDING)
        ->and($quote->fresh()->status)->toBe(QuoteStatus::ACCEPTED);
});

it('skips non-quote orders', function () {
    $order = Order::factory()->create([
        'user_id' => $this->user->id,
        'quote_id' => null,
        'status' => OrderStatus::PENDING,
        'payment_status' => PaymentStatus::PENDING,
        'expires_at' => now()->subMinutes(5),
    ]);

    $this->artisan('quotations:revert-abandoned-payments')->assertSuccessful();

    expect($order->fresh()->status)->toBe(OrderStatus::PENDING);
});

it('dry run reports eligible orders without making changes', function () {
    ['quote' => $quote, 'order' => $order] = makeQuoteOrder($this->user);

    $this->artisan('quotations:revert-abandoned-payments --dry-run')
        ->expectsOutputToContain($order->reference)
        ->assertSuccessful();

    expect($order->fresh()->status)->toBe(OrderStatus::PENDING)
        ->and($quote->fresh()->status)->toBe(QuoteStatus::ACCEPTED);
});

it('handles multiple abandoned orders in one run', function () {
    $sets = collect(range(1, 3))->map(fn () => makeQuoteOrder($this->user));

    $this->artisan('quotations:revert-abandoned-payments')->assertSuccessful();

    foreach ($sets as ['quote' => $quote, 'order' => $order]) {
        expect($order->fresh()->status)->toBe(OrderStatus::CANCELLED)
            ->and($quote->fresh()->status)->toBe(QuoteStatus::SENT);
    }
});
