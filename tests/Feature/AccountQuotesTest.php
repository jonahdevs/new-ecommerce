<?php

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('hides pricing for a draft request that has not been quoted yet', function () {
    Quote::factory()->create([
        'user_id' => $this->user->id,
        'status' => QuoteStatus::DRAFT,
        'total_cents' => 0,
        'title' => 'Pending request',
    ]);

    Livewire::test('pages::account.quotes.index')
        ->assertSee('Pending request')
        ->assertSee('Awaiting quote');
});

it('shows the staff-set price once the quote has been sent', function () {
    Quote::factory()->create([
        'user_id' => $this->user->id,
        'status' => QuoteStatus::SENT,
        'total_cents' => 4500000,
        'title' => 'Priced quote',
    ]);

    Livewire::test('pages::account.quotes.index')
        ->assertSee('45,000')
        ->assertDontSee('Awaiting quote');
});

it('does not show a price for a draft even if a total somehow leaked in', function () {
    Quote::factory()->create([
        'user_id' => $this->user->id,
        'status' => QuoteStatus::DRAFT,
        'total_cents' => 999900,
        'title' => 'Draft with stray total',
    ]);

    Livewire::test('pages::account.quotes.index')
        ->assertSee('Awaiting quote')
        ->assertDontSee('9,999');
});

// ─── Detail page ───────────────────────────────────────────────────────────

/** Build an owned quote with one priced line. */
function ownedQuote(QuoteStatus $status, int $lineCents = 0): Quote
{
    $quote = Quote::factory()->create([
        'user_id' => test()->user->id,
        'status' => $status,
        'total_cents' => $lineCents,
        'title' => 'Project quote',
    ]);

    $quote->items()->create([
        'product_name' => 'Combi oven',
        'product_sku' => 'OVN-1',
        'unit_price_cents' => $lineCents,
        'quantity' => 1,
        'line_total_cents' => $lineCents,
    ]);

    return $quote;
}

it('forbids viewing another customer\'s quote', function () {
    $other = Quote::factory()->create(['user_id' => User::factory()]);

    $this->get(route('account.quotes.show', $other))->assertForbidden();
});

it('hides pricing on the detail page for an unpriced draft', function () {
    $quote = ownedQuote(QuoteStatus::DRAFT);

    Livewire::test('pages::account.quotes.show', ['quote' => $quote])
        ->assertSee('Combi oven')
        ->assertSee('Awaiting quote')
        ->assertDontSee('Approve quote');
});

it('shows line pricing and total on the detail page once quoted', function () {
    $quote = ownedQuote(QuoteStatus::SENT, 4500000);

    Livewire::test('pages::account.quotes.show', ['quote' => $quote])
        ->assertSee('Combi oven')
        ->assertSee('45,000')
        ->assertDontSee('Awaiting quote');
});

it('lets the customer approve a quote awaiting approval', function () {
    $quote = ownedQuote(QuoteStatus::AWAITING_APPROVAL, 4500000);

    Livewire::test('pages::account.quotes.show', ['quote' => $quote])
        ->assertSee('Approve quote')
        ->call('approve');

    expect($quote->refresh()->status)->toBe(QuoteStatus::APPROVED);
});

it('lets the customer decline a quote awaiting approval', function () {
    $quote = ownedQuote(QuoteStatus::AWAITING_APPROVAL, 4500000);

    Livewire::test('pages::account.quotes.show', ['quote' => $quote])
        ->call('decline');

    expect($quote->refresh()->status)->toBe(QuoteStatus::DECLINED);
});

it('ignores approval when the quote is not awaiting approval', function () {
    $quote = ownedQuote(QuoteStatus::SENT, 4500000);

    Livewire::test('pages::account.quotes.show', ['quote' => $quote])
        ->call('approve');

    expect($quote->refresh()->status)->toBe(QuoteStatus::SENT);
});
