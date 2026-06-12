<?php

use App\Enums\QuoteStatus;
use App\Models\Order;
use App\Models\Quote;
use App\Models\User;
use Livewire\Livewire;

/** An awaiting-approval quote with one priced line and the given validity. */
function awaitingQuote(array $attrs = []): Quote
{
    $quote = Quote::factory()->create(array_merge([
        'status' => QuoteStatus::AWAITING_APPROVAL,
        'expires_at' => now()->subDay(),
        'total_cents' => 500000,
        'subtotal_cents' => 500000,
    ], $attrs));

    $quote->items()->create([
        'product_snapshot' => ['name' => 'Combi oven', 'sku' => 'OVN-9', 'model_number' => null],
        'unit_price_cents' => 500000,
        'quantity' => 1,
        'line_total_cents' => 500000,
    ]);

    return $quote->load('items');
}

it('reports an awaiting quote past its validity window as expired and not approvable', function () {
    $quote = awaitingQuote();

    expect($quote->hasExpired())->toBeTrue()
        ->and($quote->isApprovable())->toBeFalse();
});

it('treats a future-dated awaiting quote as approvable', function () {
    $quote = awaitingQuote(['expires_at' => now()->addDays(5)]);

    expect($quote->hasExpired())->toBeFalse()
        ->and($quote->isApprovable())->toBeTrue();
});

it('expires sent quotes past their validity window via the scheduled command', function () {
    $expired = awaitingQuote();
    $future = awaitingQuote(['expires_at' => now()->addDays(10)]);
    $draft = Quote::factory()->create(['status' => QuoteStatus::DRAFT, 'expires_at' => now()->subDay()]);

    $this->artisan('quotes:expire')->assertSuccessful();

    expect($expired->fresh()->status)->toBe(QuoteStatus::EXPIRED)
        ->and($future->fresh()->status)->toBe(QuoteStatus::AWAITING_APPROVAL)
        ->and($draft->fresh()->status)->toBe(QuoteStatus::DRAFT)
        ->and($expired->statusHistories()->where('to_status', 'expired')->exists())->toBeTrue();
});

it('forbids a guest from approving an expired quote', function () {
    $quote = awaitingQuote(['user_id' => null, 'contact_email' => 'jane@example.com']);

    Livewire::test('pages::storefront.quote-review', ['quote' => $quote])
        ->call('approve')
        ->assertForbidden();

    expect($quote->fresh()->status)->toBe(QuoteStatus::AWAITING_APPROVAL)
        ->and(Order::count())->toBe(0);
});

it('forbids an account customer from approving an expired quote', function () {
    $user = User::factory()->create();
    $quote = awaitingQuote(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test('pages::account.quotes.show', ['quote' => $quote])
        ->call('approve')
        ->assertForbidden();

    expect($quote->fresh()->status)->toBe(QuoteStatus::AWAITING_APPROVAL)
        ->and(Order::count())->toBe(0);
});

it('records a status-history entry when a customer approves', function () {
    $user = User::factory()->create();
    $quote = awaitingQuote(['user_id' => $user->id, 'expires_at' => now()->addDays(5)]);

    Livewire::actingAs($user)
        ->test('pages::account.quotes.show', ['quote' => $quote])
        ->call('approve');

    expect($quote->statusHistories()->where('to_status', 'approved')->exists())->toBeTrue()
        ->and($quote->fresh()->status)->toBe(QuoteStatus::APPROVED);
});
