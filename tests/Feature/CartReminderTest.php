<?php

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use App\Notifications\Marketing\AbandonedCartReminder;
use App\Settings\CartReminderSettings;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    Notification::fake();
    app(CartReminderSettings::class)->fill([
        'enabled' => true,
        'first_delay_hours' => 4,
        'second_delay_hours' => 24,
        'min_subtotal_cents' => 0,
        'stop_after_hours' => 168,
    ])->save();
});

/** A marketing-opted-in customer with an idle, non-empty cart. */
function abandonedCart(int $idleHours, array $cartAttrs = []): Cart
{
    $user = User::factory()->create(['notification_preferences' => ['marketing' => true]]);
    $product = Product::factory()->create(['price' => 500000]);

    $cart = Cart::create(array_merge([
        'user_id' => $user->id,
        'last_activity_at' => now()->subHours($idleHours),
    ], $cartAttrs));

    $cart->items()->create(['product_id' => $product->id, 'quantity' => 1]);

    return $cart->load('user');
}

it('sends the first reminder for a cart idle past the first delay', function () {
    $cart = abandonedCart(idleHours: 5);

    $this->artisan('cart:remind-abandoned')->assertSuccessful();

    Notification::assertSentTo($cart->user, AbandonedCartReminder::class, function ($n) {
        return $n->stage === 1;
    });

    expect($cart->fresh()->reminders_sent)->toBe(1)
        ->and($cart->fresh()->last_reminded_at)->not->toBeNull();
});

it('does not remind a cart that is still fresh', function () {
    $cart = abandonedCart(idleHours: 1);

    $this->artisan('cart:remind-abandoned')->assertSuccessful();

    Notification::assertNothingSent();
    expect($cart->fresh()->reminders_sent)->toBe(0);
});

it('does not remind a cart that is too stale', function () {
    $cart = abandonedCart(idleHours: 200); // beyond stop_after_hours (168)

    $this->artisan('cart:remind-abandoned')->assertSuccessful();

    Notification::assertNothingSent();
});

it('skips carts below the minimum subtotal', function () {
    app(CartReminderSettings::class)->fill(['min_subtotal_cents' => 1_000_000])->save();

    $cart = abandonedCart(idleHours: 5); // subtotal is 500,000

    $this->artisan('cart:remind-abandoned')->assertSuccessful();

    Notification::assertNothingSent();
});

it('does not remind customers who have not opted into marketing', function () {
    $user = User::factory()->create(['notification_preferences' => ['marketing' => false]]);
    $product = Product::factory()->create(['price' => 500000]);
    $cart = Cart::create(['user_id' => $user->id, 'last_activity_at' => now()->subHours(5)]);
    $cart->items()->create(['product_id' => $product->id, 'quantity' => 1]);

    $this->artisan('cart:remind-abandoned')->assertSuccessful();

    Notification::assertNothingSent();
    expect($cart->fresh()->reminders_sent)->toBe(0); // stage not consumed
});

it('sends the second reminder once the cart is idle past the second delay', function () {
    $cart = abandonedCart(idleHours: 25, cartAttrs: ['reminders_sent' => 1]);

    $this->artisan('cart:remind-abandoned')->assertSuccessful();

    Notification::assertSentTo($cart->user, AbandonedCartReminder::class, fn ($n) => $n->stage === 2);
    expect($cart->fresh()->reminders_sent)->toBe(2);
});

it('stops after the final reminder', function () {
    $cart = abandonedCart(idleHours: 100, cartAttrs: ['reminders_sent' => 2]);

    $this->artisan('cart:remind-abandoned')->assertSuccessful();

    Notification::assertNothingSent();
});

it('does nothing when the feature is disabled', function () {
    app(CartReminderSettings::class)->fill(['enabled' => false])->save();
    abandonedCart(idleHours: 5);

    $this->artisan('cart:remind-abandoned')->assertSuccessful();

    Notification::assertNothingSent();
});

it('restores a saved cart into the session via the signed link', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['slug' => 'restored-oven']);
    $cart = Cart::create(['user_id' => $user->id]);
    $cart->items()->create(['product_id' => $product->id, 'quantity' => 2]);

    $url = URL::temporarySignedRoute('cart.restore', now()->addDay(), ['cart' => $cart->id]);

    $this->get($url)
        ->assertRedirect(route('cart'))
        ->assertSessionHas('cart', ['restored-oven' => 2]);
});

it('rejects an unsigned restore link', function () {
    $cart = Cart::create(['user_id' => User::factory()->create()->id]);

    $this->get(route('cart.restore', $cart))->assertForbidden();
});
