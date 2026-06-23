<?php

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use App\Settings\CartReminderSettings;
use Database\Seeders\PermissionSeeder;
use Livewire\Livewire;

it('loads the cart recovery page', function () {
    actingAsAdmin();

    $this->get(route('admin.marketing.cart-recovery'))->assertOk();
});

it('saves cart recovery settings', function () {
    actingAsAdmin();

    Livewire::test('pages::admin.marketing.cart-recovery')
        ->set('enabled', true)
        ->set('first_delay_hours', 6)
        ->set('second_delay_hours', 48)
        ->set('min_subtotal', 5000)
        ->set('stop_after_days', 10)
        ->call('save')
        ->assertHasNoErrors();

    $settings = app(CartReminderSettings::class);

    expect($settings->enabled)->toBeTrue()
        ->and($settings->first_delay_hours)->toBe(6)
        ->and($settings->second_delay_hours)->toBe(48)
        ->and($settings->min_subtotal_cents)->toBe(500_000)
        ->and($settings->stop_after_hours)->toBe(240);
});

it('counts open abandoned carts and their recoverable value', function () {
    actingAsAdmin();
    app(CartReminderSettings::class)->fill(['first_delay_hours' => 4])->save();

    $product = Product::factory()->create(['price' => 500_000]); // KES 5,000

    // Idle 5h (past the 4h first delay), not recovered → abandoned.
    $abandoned = Cart::create(['user_id' => User::factory()->create()->id, 'last_activity_at' => now()->subHours(5)]);
    $abandoned->items()->create(['product_id' => $product->id, 'quantity' => 2]);

    // A still-fresh cart must not count.
    $fresh = Cart::create(['user_id' => User::factory()->create()->id, 'last_activity_at' => now()->subHour()]);
    $fresh->items()->create(['product_id' => $product->id, 'quantity' => 1]);

    // A recovered cart must not count as open.
    $recovered = Cart::create(['user_id' => User::factory()->create()->id, 'last_activity_at' => now()->subHours(6), 'recovered_at' => now()]);
    $recovered->items()->create(['product_id' => $product->id, 'quantity' => 1]);

    $stats = Livewire::test('pages::admin.marketing.cart-recovery')->instance()->stats();

    expect($stats['open'])->toBe(1)
        ->and($stats['recoverable_cents'])->toBe(1_000_000) // 5,000 * 2 * 100
        ->and($stats['recovered'])->toBe(1);
});

it('blocks staff without the marketing permission', function () {
    $this->seed(PermissionSeeder::class);
    $staff = User::factory()->create();
    $staff->assignRole('staff');

    $this->actingAs($staff)
        ->get(route('admin.marketing.cart-recovery'))
        ->assertForbidden();
});
