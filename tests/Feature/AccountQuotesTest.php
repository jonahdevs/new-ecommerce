<?php

use App\Enums\QuoteStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\User;
use App\Settings\PaymentSettings;
use Illuminate\Support\Facades\Http;
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
    ]);

    Livewire::test('pages::account.quotes.index')
        ->assertSee('Awaiting quote');
});

it('shows the staff-set price once the quote has been sent', function () {
    Quote::factory()->create([
        'user_id' => $this->user->id,
        'status' => QuoteStatus::SENT,
        'total_cents' => 4500000,
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
    ]);

    Livewire::test('pages::account.quotes.index')
        ->assertSee('Awaiting quote')
        ->assertDontSee('9,999');
});

// ==================================================
// DETAIL PAGE
// ==================================================

/** Build an owned quote with one priced line. */
function ownedQuote(QuoteStatus $status, int $lineCents = 0): Quote
{
    $quote = Quote::factory()->create([
        'user_id' => test()->user->id,
        'status' => $status,
        'total_cents' => $lineCents,
    ]);

    $quote->items()->create([
        'product_snapshot' => ['name' => 'Combi oven', 'sku' => 'OVN-1', 'model_number' => null],
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
        ->assertSee('Pricing pending')
        ->assertDontSee('Approve quote');
});

it('shows line pricing and total on the detail page once quoted', function () {
    $quote = ownedQuote(QuoteStatus::SENT, 4500000);

    Livewire::test('pages::account.quotes.show', ['quote' => $quote])
        ->assertSee('Combi oven')
        ->assertSee('45,000')
        ->assertDontSee('Awaiting quote');
});

it('shows the product thumbnail from the snapshot even without a live product, and labels customer notes', function () {
    $quote = Quote::factory()->create([
        'user_id' => $this->user->id,
        'status' => QuoteStatus::SENT,
        'total_cents' => 4500000,
        'notes' => 'Please deliver to the rear entrance.',
    ]);

    $quote->items()->create([
        'product_snapshot' => [
            'name' => 'Combi oven',
            'sku' => 'OVN-1',
            'model_number' => null,
            'cover_url' => '/storage/products/combi.jpg',
        ],
        'unit_price_cents' => 4500000,
        'quantity' => 1,
        'line_total_cents' => 4500000,
    ]);

    Livewire::test('pages::account.quotes.show', ['quote' => $quote])
        ->assertSeeHtml('/storage/products/combi.jpg')
        ->assertSee('Your notes')
        ->assertSee('Please deliver to the rear entrance.');
});

it('approves a quote, creates an order, and opens the Paystack popup when the gateway is enabled', function () {
    config()->set('services.paystack', ['public_key' => 'pk_test_fake', 'secret_key' => 'sk_test_fake']);
    Http::fake([
        'https://api.paystack.co/transaction/initialize' => Http::response([
            'status' => true,
            'data' => ['access_code' => 'ac_quote', 'reference' => 'ignored'],
        ]),
    ]);

    $quote = ownedQuote(QuoteStatus::AWAITING_APPROVAL, 4500000);

    Livewire::test('pages::account.quotes.show', ['quote' => $quote])
        ->assertSee('Approve quote')
        ->call('approve')
        ->assertHasNoErrors()
        ->assertDispatched('paystack-open')
        ->assertNoRedirect();

    $quote->refresh();

    expect($quote->status)->toBe(QuoteStatus::APPROVED)
        ->and($quote->order_id)->not->toBeNull()
        ->and(Order::count())->toBe(1)
        ->and(Order::find($quote->order_id)->payment_method)->toBe('paystack')
        ->and(Payment::where('order_id', $quote->order_id)->count())->toBe(1);
});

it('re-opens the Paystack popup via complete payment for an approved unpaid quote', function () {
    config()->set('services.paystack', ['public_key' => 'pk_test_fake', 'secret_key' => 'sk_test_fake']);
    Http::fake([
        'https://api.paystack.co/transaction/initialize' => Http::response([
            'status' => true,
            'data' => ['access_code' => 'ac_quote', 'reference' => 'ignored'],
        ]),
    ]);

    $quote = ownedQuote(QuoteStatus::AWAITING_APPROVAL, 4500000);
    $order = Order::factory()->create(['user_id' => $this->user->id]);
    $quote->update(['status' => QuoteStatus::APPROVED, 'order_id' => $order->id]);

    Livewire::test('pages::account.quotes.show', ['quote' => $quote])
        ->assertSee('Complete payment')
        ->call('completePayment')
        ->assertHasNoErrors()
        ->assertDispatched('paystack-open')
        ->assertNoRedirect();

    expect($order->fresh()->payment_method)->toBe('paystack');
});

it('falls back to the payment page on approval when Paystack is disabled', function () {
    app(PaymentSettings::class)->fill(['paystack_enabled' => false])->save();

    $quote = ownedQuote(QuoteStatus::AWAITING_APPROVAL, 4500000);

    Livewire::test('pages::account.quotes.show', ['quote' => $quote])
        ->call('approve')
        ->assertRedirect(route('payment.page', $quote->refresh()->order_id));
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
