<?php

use App\Mail\NewsletterConfirmation;
use App\Models\Subscriber;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(fn () => Mail::fake());

// Subscribe

it('persists a new subscriber and queues a confirmation email', function () {
    Livewire::test('storefront.newsletter-signup')
        ->set('email', 'jane@example.com')
        ->set('interests', ['new-products', 'projects'])
        ->call('subscribe')
        ->assertSet('submitted', true);

    expect(Subscriber::where('email', 'jane@example.com')->exists())->toBeTrue();

    $subscriber = Subscriber::where('email', 'jane@example.com')->first();
    expect($subscriber->subscribed_at)->toBeNull()
        ->and($subscriber->interests)->toContain('new-products');

    Mail::assertQueued(NewsletterConfirmation::class);
});

it('requires a valid email', function () {
    Livewire::test('storefront.newsletter-signup')
        ->set('email', 'not-an-email')
        ->call('subscribe')
        ->assertHasErrors(['email']);

    Mail::assertNothingQueued();
});

it('silently updates interests for a confirmed subscriber without resending', function () {
    $subscriber = Subscriber::factory()->confirmed()->create(['email' => 'confirmed@example.com']);

    Livewire::test('storefront.newsletter-signup')
        ->set('email', 'confirmed@example.com')
        ->set('interests', ['projects'])
        ->call('subscribe')
        ->assertSet('submitted', true);

    Mail::assertNothingQueued();
    expect($subscriber->fresh()->interests)->toContain('projects');
});

it('re-subscribes an unsubscribed email and sends a new confirmation', function () {
    Subscriber::factory()->unsubscribed()->create(['email' => 'gone@example.com']);

    Livewire::test('storefront.newsletter-signup')
        ->set('email', 'gone@example.com')
        ->call('subscribe')
        ->assertSet('submitted', true);

    Mail::assertQueued(NewsletterConfirmation::class);

    $subscriber = Subscriber::where('email', 'gone@example.com')->first();
    expect($subscriber->unsubscribed_at)->toBeNull()
        ->and($subscriber->subscribed_at)->toBeNull();
});

it('resends confirmation to a pending subscriber', function () {
    Subscriber::factory()->pending()->create(['email' => 'pending@example.com']);

    Livewire::test('storefront.newsletter-signup')
        ->set('email', 'pending@example.com')
        ->call('subscribe')
        ->assertSet('submitted', true);

    Mail::assertQueued(NewsletterConfirmation::class);
});

// Confirm

it('confirms a subscriber via the token link', function () {
    $subscriber = Subscriber::factory()->pending()->create();

    $this->get(route('newsletter.confirm', $subscriber->token))
        ->assertOk()
        ->assertViewIs('pages.newsletter.confirmed');

    expect($subscriber->fresh()->subscribed_at)->not->toBeNull();
});

it('redirects home when the confirm token is invalid', function () {
    $this->get(route('newsletter.confirm', 'bad-token'))
        ->assertRedirect(route('home'));
});

it('does not confirm an already-confirmed subscriber again', function () {
    $subscriber = Subscriber::factory()->confirmed()->create();

    $this->get(route('newsletter.confirm', $subscriber->token))
        ->assertRedirect(route('home'));
});

// Unsubscribe

it('unsubscribes a confirmed subscriber via the token link', function () {
    $subscriber = Subscriber::factory()->confirmed()->create();

    $this->get(route('newsletter.unsubscribe', $subscriber->token))
        ->assertOk()
        ->assertViewIs('pages.newsletter.unsubscribed');

    expect($subscriber->fresh()->unsubscribed_at)->not->toBeNull();
});

it('shows the unsubscribed page even for an invalid token', function () {
    $this->get(route('newsletter.unsubscribe', 'bad-token'))
        ->assertOk()
        ->assertViewIs('pages.newsletter.unsubscribed');
});
