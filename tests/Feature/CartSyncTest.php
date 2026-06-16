<?php

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use App\Support\StorefrontSession;
use Illuminate\Support\Facades\Auth;

it('persists the cart to the database when an authenticated user adds an item', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['slug' => 'combi-oven']);

    $this->actingAs($user);

    StorefrontSession::addToCart('combi-oven', 2);

    $cart = $user->fresh()->cart;

    expect($cart)->not->toBeNull()
        ->and($cart->items)->toHaveCount(1)
        ->and($cart->items->first()->product_id)->toBe($product->id)
        ->and($cart->items->first()->quantity)->toBe(2);
});

it('removes the persisted line when an authenticated user clears it', function () {
    $user = User::factory()->create();
    Product::factory()->create(['slug' => 'blast-chiller']);

    $this->actingAs($user);

    StorefrontSession::addToCart('blast-chiller', 1);
    StorefrontSession::removeFromCart('blast-chiller');

    expect($user->fresh()->cart->items)->toHaveCount(0);
});

it('does not persist a cart for a guest', function () {
    Product::factory()->create(['slug' => 'wok-range']);

    StorefrontSession::addToCart('wok-range', 1);

    expect(Cart::count())->toBe(0)
        ->and(StorefrontSession::cart())->toHaveKey('wok-range');
});

it('merges the guest session cart into the saved cart on login, keeping the larger quantity', function () {
    $user = User::factory()->create();
    $oven = Product::factory()->create(['slug' => 'oven']);
    $fridge = Product::factory()->create(['slug' => 'fridge']);
    $mixer = Product::factory()->create(['slug' => 'mixer']);

    // The user already has a saved cart from a previous session.
    $cart = Cart::create(['user_id' => $user->id]);
    $cart->items()->create(['product_id' => $oven->id, 'quantity' => 1]);
    $cart->items()->create(['product_id' => $fridge->id, 'quantity' => 3]);

    // As a guest, they build up a different cart this session.
    StorefrontSession::addToCart('oven', 2);   // overlaps the saved oven (qty 1)
    StorefrontSession::addToCart('mixer', 1);  // brand new line

    // Logging in fires the Login event → SyncCartOnLogin → mergeIntoUserCart.
    Auth::login($user);

    $merged = $cart->fresh()->load('items.product')->items
        ->mapWithKeys(fn ($item) => [$item->product->slug => $item->quantity]);

    expect($merged)->toHaveCount(3)
        ->and($merged['oven'])->toBe(2)    // max(1, 2)
        ->and($merged['fridge'])->toBe(3)  // untouched
        ->and($merged['mixer'])->toBe(1);  // added from the guest cart

    // The live session cart now reflects the full merged cart.
    expect(StorefrontSession::cart())->toHaveKeys(['oven', 'fridge', 'mixer']);
});

it('is idempotent when a user logs in twice', function () {
    $user = User::factory()->create();
    Product::factory()->create(['slug' => 'oven']);

    StorefrontSession::addToCart('oven', 2);

    Auth::login($user);
    Auth::logout();
    Auth::login($user);

    $items = $user->fresh()->cart->items;

    expect($items)->toHaveCount(1)
        ->and($items->first()->quantity)->toBe(2);
});

it('loads a saved cart into the session for a returning user with an empty guest cart', function () {
    $user = User::factory()->create();
    $oven = Product::factory()->create(['slug' => 'oven']);

    $cart = Cart::create(['user_id' => $user->id]);
    $cart->items()->create(['product_id' => $oven->id, 'quantity' => 4]);

    // No guest items in the session this time.
    Auth::login($user);

    expect(StorefrontSession::cart())->toBe(['oven' => 4]);
});
