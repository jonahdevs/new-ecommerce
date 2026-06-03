<?php

use App\Enums\QuoteStatus;
use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use App\Notifications\Quotes\NewQuoteRequested;
use App\Notifications\Quotes\QuoteDecisionReceived;
use App\Notifications\Quotes\QuoteReadyForReview;
use App\Notifications\Quotes\QuoteRequestReceived;
use App\Settings\QuotationSettings;
use App\Support\StorefrontSession;
use Database\Seeders\PermissionSeeder;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    $this->seed(PermissionSeeder::class);
    app(QuotationSettings::class)->fill(['quotes_enabled' => true])->save();

    $this->staff = User::factory()->create();
    $this->staff->assignRole('staff');
});

/** A draft quote with one line, ready for staff to price. */
function pricedDraft(array $attrs = []): Quote
{
    $quote = Quote::factory()->create(array_merge([
        'status' => QuoteStatus::DRAFT,
        'total_cents' => 0,
    ], $attrs));

    $quote->items()->create([
        'product_name' => 'Combi oven',
        'product_sku' => 'OVN-1',
        'unit_price_cents' => 0,
        'quantity' => 1,
        'line_total_cents' => 0,
    ]);

    return $quote->load('items');
}

it('acknowledges a guest request and alerts staff', function () {
    $product = Product::factory()->create();
    StorefrontSession::addToCart($product->slug, 2);

    Livewire::test('pages::storefront.request-quote')
        ->set('contact_name', 'Jane Guest')
        ->set('contact_email', 'jane@example.com')
        ->call('submit')
        ->assertHasNoErrors();

    Notification::assertSentOnDemand(QuoteRequestReceived::class);
    Notification::assertSentTo($this->staff, NewQuoteRequested::class);
});

it('acknowledges a registered customer on their account', function () {
    $customer = User::factory()->create();
    $this->actingAs($customer);

    $product = Product::factory()->create();
    StorefrontSession::addToCart($product->slug, 1);

    Livewire::test('pages::storefront.request-quote')
        ->call('submit')
        ->assertHasNoErrors();

    Notification::assertSentTo($customer, QuoteRequestReceived::class);
});

it('emails the customer when staff send the quote for approval', function () {
    $customer = User::factory()->create();
    $quote = pricedDraft(['user_id' => $customer->id, 'total_cents' => 500000]);

    Livewire::test('pages::admin.quotes.show', ['quote' => $quote])
        ->call('sendToCustomer');

    expect($quote->fresh()->status)->toBe(QuoteStatus::AWAITING_APPROVAL);
    Notification::assertSentTo($customer, QuoteReadyForReview::class);
});

it('routes the ready-for-review email to a guest contact', function () {
    $quote = pricedDraft(['user_id' => null, 'contact_email' => 'guest@example.com', 'total_cents' => 500000]);

    Livewire::test('pages::admin.quotes.show', ['quote' => $quote])
        ->call('sendToCustomer');

    Notification::assertSentOnDemand(QuoteReadyForReview::class);
});

it('alerts staff when a customer approves a quote', function () {
    $customer = User::factory()->create();
    $this->actingAs($customer);

    $quote = pricedDraft(['user_id' => $customer->id, 'status' => QuoteStatus::AWAITING_APPROVAL, 'total_cents' => 500000]);

    Livewire::test('pages::account.quotes.show', ['quote' => $quote])
        ->call('approve');

    expect($quote->fresh()->status)->toBe(QuoteStatus::APPROVED);
    Notification::assertSentTo($this->staff, QuoteDecisionReceived::class);
});

it('mutes a quote notification when the customer disabled it but always sends to guests', function () {
    $user = User::factory()->create(['notification_preferences' => ['quotes' => ['received' => false]]]);
    $quote = Quote::factory()->make(['user_id' => $user->id]);

    expect((new QuoteRequestReceived($quote))->via($user))->toBe([])
        ->and((new QuoteRequestReceived($quote))->via(new AnonymousNotifiable))->toBe(['mail']);
});
