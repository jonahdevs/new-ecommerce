<?php

use App\Enums\QuoteStatus;
use App\Models\Order;
use App\Models\Quote;
use App\Models\User;
use App\Settings\PaymentSettings;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

function guestQuote(QuoteStatus $status, int $totalCents = 0): Quote
{
    $quote = Quote::factory()->create([
        'user_id' => null,
        'contact_name' => 'Jane Doe',
        'contact_email' => 'jane@example.com',
        'status' => $status,
        'total_cents' => $totalCents,
        'subtotal_cents' => $totalCents,
    ]);

    $quote->items()->create([
        'product_snapshot' => ['name' => 'Industrial Oven', 'sku' => 'OVN-2', 'model_number' => null],
        'unit_price_cents' => $totalCents,
        'quantity' => 1,
        'line_total_cents' => $totalCents,
    ]);

    return $quote->load('items');
}

function signedReviewUrl(Quote $quote): string
{
    return URL::temporarySignedRoute('quotes.guest-review', now()->addDays(60), ['quote' => $quote]);
}

// ==================================================
// SIGNED URL SECURITY
// ==================================================

it('loads the guest review page via a valid signed URL', function () {
    $quote = guestQuote(QuoteStatus::AWAITING_APPROVAL, 5000000);

    $this->get(signedReviewUrl($quote))->assertOk();
});

it('rejects a tampered signed URL with 403', function () {
    $quote = guestQuote(QuoteStatus::AWAITING_APPROVAL, 5000000);

    $this->get(signedReviewUrl($quote).'&tampered=1')->assertForbidden();
});

it('rejects an expired signed URL with 403', function () {
    $quote = guestQuote(QuoteStatus::AWAITING_APPROVAL, 5000000);

    $expiredUrl = URL::temporarySignedRoute('quotes.guest-review', now()->subDay(), ['quote' => $quote]);

    $this->get($expiredUrl)->assertForbidden();
});

it('forbids accessing an authenticated-user quote on the guest review page', function () {
    $quote = Quote::factory()->create([
        'user_id' => User::factory(),
        'status' => QuoteStatus::AWAITING_APPROVAL,
    ]);

    $this->get(URL::temporarySignedRoute('quotes.guest-review', now()->addDays(60), ['quote' => $quote]))
        ->assertForbidden();
});

// ==================================================
// GUEST APPROVE → REGISTER FLOW
// ==================================================

it('shows the approve button for an awaiting-approval guest quote', function () {
    $quote = guestQuote(QuoteStatus::AWAITING_APPROVAL, 5000000);

    Livewire::test('pages::storefront.quote-review', ['quote' => $quote])
        ->assertSee('Approve quote')
        ->assertSee('Industrial Oven');
});

it('guest approve stores session and redirects to register', function () {
    $quote = guestQuote(QuoteStatus::AWAITING_APPROVAL, 5000000);

    Livewire::test('pages::storefront.quote-review', ['quote' => $quote])
        ->call('approve')
        ->assertRedirect('/register');

    expect($quote->refresh()->status)->toBe(QuoteStatus::AWAITING_APPROVAL);
});

it('links quote to user and creates order after registration', function () {
    config()->set('services.paystack.secret_key', 'sk_test_fake');
    $quote = guestQuote(QuoteStatus::AWAITING_APPROVAL, 5000000);

    // Paystack active → land on the quote page where the popup runs in-context.
    $this->withSession(['quote_approval_pending' => $quote->id])
        ->post('/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => 'on',
        ])
        ->assertRedirect(route('account.quotes.show', $quote));

    $quote->refresh();

    expect($quote->status)->toBe(QuoteStatus::APPROVED)
        ->and($quote->user_id)->not->toBeNull()
        ->and(Order::count())->toBe(1);
});

it('links all guest quotes sharing the email when registering', function () {
    $otherQuote = guestQuote(QuoteStatus::APPROVED, 1000000);
    $pendingQuote = guestQuote(QuoteStatus::AWAITING_APPROVAL, 5000000);

    $this->withSession(['quote_approval_pending' => $pendingQuote->id])
        ->post('/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => 'on',
        ]);

    $user = User::where('email', 'jane@example.com')->first();

    expect($otherQuote->refresh()->user_id)->toBe($user->id)
        ->and($pendingQuote->refresh()->user_id)->toBe($user->id);
});

it('links quote to user and creates order after login', function () {
    config()->set('services.paystack.secret_key', 'sk_test_fake');
    $user = User::factory()->create();
    $quote = guestQuote(QuoteStatus::AWAITING_APPROVAL, 5000000);

    $this->withSession(['quote_approval_pending' => $quote->id])
        ->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('account.quotes.show', $quote));

    $quote->refresh();

    expect($quote->status)->toBe(QuoteStatus::APPROVED)
        ->and($quote->user_id)->toBe($user->id)
        ->and(Order::count())->toBe(1);
});

// ==================================================
// ALREADY-AUTHENTICATED APPROVE
// ==================================================

it('authenticated user approving a guest quote lands on the quote page to pay with Paystack', function () {
    config()->set('services.paystack.secret_key', 'sk_test_fake');
    $user = User::factory()->create();
    $quote = guestQuote(QuoteStatus::AWAITING_APPROVAL, 5000000);

    Livewire::actingAs($user)
        ->test('pages::storefront.quote-review', ['quote' => $quote])
        ->call('approve')
        ->assertRedirect(route('account.quotes.show', $quote));

    expect($quote->refresh()->status)->toBe(QuoteStatus::APPROVED)
        ->and($quote->refresh()->user_id)->toBe($user->id)
        ->and(Order::count())->toBe(1);
});

it('authenticated approve falls back to the payment page when Paystack is disabled', function () {
    app(PaymentSettings::class)->fill(['paystack_enabled' => false])->save();
    $user = User::factory()->create();
    $quote = guestQuote(QuoteStatus::AWAITING_APPROVAL, 5000000);

    Livewire::actingAs($user)
        ->test('pages::storefront.quote-review', ['quote' => $quote])
        ->call('approve')
        ->assertRedirect(route('payment.page', Order::first()));

    expect($quote->refresh()->status)->toBe(QuoteStatus::APPROVED);
});

// ==================================================
// DECLINE
// ==================================================

it('guest can decline a quote awaiting approval', function () {
    $quote = guestQuote(QuoteStatus::AWAITING_APPROVAL, 5000000);

    Livewire::test('pages::storefront.quote-review', ['quote' => $quote])
        ->call('decline');

    expect($quote->refresh()->status)->toBe(QuoteStatus::DECLINED);
});

it('cannot approve a quote that is not awaiting approval', function () {
    $quote = guestQuote(QuoteStatus::APPROVED, 5000000);

    Livewire::test('pages::storefront.quote-review', ['quote' => $quote])
        ->call('approve');

    expect($quote->refresh()->status)->toBe(QuoteStatus::APPROVED);
});

it('shows the approved banner for an approved quote', function () {
    $quote = guestQuote(QuoteStatus::APPROVED, 5000000);

    Livewire::test('pages::storefront.quote-review', ['quote' => $quote])
        ->assertSee('Our team will be in touch')
        ->assertDontSee('Approve quote');
});

it('shows the declined banner for a declined quote', function () {
    $quote = guestQuote(QuoteStatus::DECLINED);

    Livewire::test('pages::storefront.quote-review', ['quote' => $quote])
        ->assertSee('Quote declined')
        ->assertDontSee('Approve quote');
});
