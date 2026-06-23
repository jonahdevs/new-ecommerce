<?php

use App\Enums\CouponType;
use App\Models\Coupon;
use App\Models\CouponUse;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Livewire\Livewire;

// ==================================================
// ADMIN CRUD
// ==================================================

it('loads the coupons admin page', function () {
    actingAsAdmin();

    $this->get(route('admin.marketing.coupons.index'))->assertOk();
});

it('creates a percent coupon', function () {
    actingAsAdmin();

    Livewire::test('pages::admin.marketing.coupons.index')
        ->call('openCreate')
        ->set('code', 'SAVE15')
        ->set('type', 'percent')
        ->set('value', '15')
        ->call('save')
        ->assertHasNoErrors();

    $coupon = Coupon::where('code', 'SAVE15')->first();
    expect($coupon)->not->toBeNull()
        ->and($coupon->type)->toBe(CouponType::PERCENT)
        ->and($coupon->value)->toBe(15)
        ->and($coupon->is_active)->toBeTrue();
});

it('creates a fixed coupon and stores value in cents', function () {
    actingAsAdmin();

    Livewire::test('pages::admin.marketing.coupons.index')
        ->call('openCreate')
        ->set('code', 'FLAT500')
        ->set('type', 'fixed')
        ->set('value', '500')
        ->call('save')
        ->assertHasNoErrors();

    $coupon = Coupon::where('code', 'FLAT500')->first();
    expect($coupon->type)->toBe(CouponType::FIXED)
        ->and($coupon->value)->toBe(50000); // 500 KES in cents
});

it('stores code as uppercase', function () {
    actingAsAdmin();

    Livewire::test('pages::admin.marketing.coupons.index')
        ->call('openCreate')
        ->set('code', 'lowercase10')
        ->set('type', 'percent')
        ->set('value', '10')
        ->call('save')
        ->assertHasNoErrors();

    expect(Coupon::where('code', 'LOWERCASE10')->exists())->toBeTrue();
});

it('rejects a duplicate coupon code', function () {
    actingAsAdmin();
    Coupon::factory()->create(['code' => 'DUPE']);

    Livewire::test('pages::admin.marketing.coupons.index')
        ->call('openCreate')
        ->set('code', 'DUPE')
        ->set('type', 'percent')
        ->set('value', '10')
        ->call('save')
        ->assertHasErrors(['code']);
});

it('rejects percent value over 100', function () {
    actingAsAdmin();

    Livewire::test('pages::admin.marketing.coupons.index')
        ->call('openCreate')
        ->set('code', 'TOOBIG')
        ->set('type', 'percent')
        ->set('value', '150')
        ->call('save')
        ->assertHasErrors(['value']);
});

it('blocks deleting a coupon that has been used', function () {
    actingAsAdmin();
    $coupon = Coupon::factory()->create();
    CouponUse::create(['coupon_id' => $coupon->id, 'user_id' => null, 'order_id' => null, 'discount_cents' => 1000, 'used_at' => now()]);

    Livewire::test('pages::admin.marketing.coupons.index')
        ->call('delete', $coupon->id);

    expect(Coupon::find($coupon->id))->not->toBeNull();
});

it('deletes an unused coupon', function () {
    actingAsAdmin();
    $coupon = Coupon::factory()->create();

    Livewire::test('pages::admin.marketing.coupons.index')
        ->call('delete', $coupon->id);

    expect(Coupon::find($coupon->id))->toBeNull();
});

it('blocks staff without marketing permission', function () {
    $this->seed(PermissionSeeder::class);
    $staff = User::factory()->create();
    $staff->assignRole('staff');

    $this->actingAs($staff)
        ->get(route('admin.marketing.coupons.index'))
        ->assertForbidden();
});

// ==================================================
// COUPON VALIDATION
// ==================================================

it('accepts a valid coupon code', function () {
    $coupon = Coupon::factory()->percent(10)->create(['code' => 'VALID10']);

    $error = $coupon->validateFor(100_000);

    expect($error)->toBeNull();
});

it('rejects an inactive coupon', function () {
    $coupon = Coupon::factory()->inactive()->create();

    expect($coupon->validateFor(100_000))->not->toBeNull();
});

it('rejects an expired coupon', function () {
    $coupon = Coupon::factory()->expired()->create();

    expect($coupon->validateFor(100_000))->not->toBeNull();
});

it('rejects a coupon not yet started', function () {
    $coupon = Coupon::factory()->notStarted()->create();

    expect($coupon->validateFor(100_000))->not->toBeNull();
});

it('rejects when cart subtotal is below minimum', function () {
    $coupon = Coupon::factory()->create(['min_subtotal_cents' => 500_000]); // KES 5,000

    expect($coupon->validateFor(100_000))->not->toBeNull(); // KES 1,000 — too low
});

it('rejects an exhausted coupon', function () {
    $coupon = Coupon::factory()->exhausted()->create();

    expect($coupon->validateFor(100_000))->not->toBeNull();
});

it('rejects when a user has reached their per-user limit', function () {
    $user = User::factory()->create();
    $coupon = Coupon::factory()->create(['max_uses_per_user' => 1]);
    CouponUse::create(['coupon_id' => $coupon->id, 'user_id' => $user->id, 'order_id' => null, 'discount_cents' => 500, 'used_at' => now()]);

    expect($coupon->validateFor(100_000, $user->id))->not->toBeNull();
});

// ==================================================
// DISCOUNT CALCULATION
// ==================================================

it('calculates a percentage discount', function () {
    $coupon = Coupon::factory()->percent(15)->create();

    expect($coupon->discountFor(100_000))->toBe(15_000); // 15% of KES 1,000
});

it('calculates a fixed discount and caps at subtotal', function () {
    $coupon = Coupon::factory()->fixed(200_000)->create(); // KES 2,000 off

    expect($coupon->discountFor(100_000))->toBe(100_000); // capped at subtotal
    expect($coupon->discountFor(500_000))->toBe(200_000); // full discount
});

// ==================================================
// CHECKOUT INTEGRATION
// ==================================================

it('applies a valid coupon at checkout', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $coupon = Coupon::factory()->percent(10)->create(['code' => 'CHECKOUT10']);
    Product::factory()->create(['price' => 100_000, 'slug' => 'test-product', 'visibility' => 'visible', 'status' => 'published']);

    session()->put('cart', ['test-product' => 1]);

    Livewire::test('pages::storefront.checkout')
        ->set('couponInput', 'checkout10') // lowercase — should still work
        ->call('applyCoupon')
        ->assertHasNoErrors()
        ->assertSet('appliedCouponCode', 'CHECKOUT10')
        ->assertSet('discountCents', 10_000);
});

it('shows an error for an unknown coupon code', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Product::factory()->create(['price' => 100_000, 'slug' => 'test-product2', 'visibility' => 'visible', 'status' => 'published']);

    session()->put('cart', ['test-product2' => 1]);

    Livewire::test('pages::storefront.checkout')
        ->set('couponInput', 'NOTREAL')
        ->call('applyCoupon')
        ->assertHasErrors(['couponInput']);
});

it('removes an applied coupon', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $coupon = Coupon::factory()->percent(10)->create(['code' => 'REMOVE10']);
    Product::factory()->create(['price' => 100_000, 'slug' => 'test-product3', 'visibility' => 'visible', 'status' => 'published']);

    session()->put('cart', ['test-product3' => 1]);

    Livewire::test('pages::storefront.checkout')
        ->set('appliedCouponId', $coupon->id)
        ->set('appliedCouponCode', 'REMOVE10')
        ->set('discountCents', 10_000)
        ->call('removeCoupon')
        ->assertSet('appliedCouponCode', '')
        ->assertSet('discountCents', 0);
});
