<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\QuoteStatus;
use App\Enums\ReviewStatus;
use App\Models\Address;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->actingAs($this->admin);
});

it('renders the dashboard for staff', function () {
    Livewire::test('pages::admin.dashboard')
        ->assertOk()
        ->assertSee('store overview')
        // The funnel heading renders "Quotes <icon> orders" — assert the words
        // either side of the arrow icon rather than a literal arrow glyph.
        ->assertSeeInOrder(['Quotes', 'orders'])
        ->assertSee('Recent orders')
        ->assertSee('Low stock report');
});

it('computes period revenue, orders and AOV from paid orders', function () {
    Order::factory()->create(['status' => OrderStatus::PROCESSING, 'total_cents' => 200000, 'created_at' => now()]);

    $m = Livewire::test('pages::admin.dashboard')->instance()->metrics();

    expect($m['revenue_cents'])->toBe(200000)
        ->and($m['paid_orders'])->toBe(1)
        ->and($m['orders'])->toBe(1)
        ->and($m['aov_cents'])->toBe(200000);
});

it('computes an upward revenue trend versus the prior period', function () {
    // Default window is the last 30 days; the prior window is days 31–60 back.
    Order::factory()->create(['status' => OrderStatus::PROCESSING, 'total_cents' => 200000, 'created_at' => now()]);
    Order::factory()->create(['status' => OrderStatus::PROCESSING, 'total_cents' => 100000, 'created_at' => now()->subDays(45)]);

    $m = Livewire::test('pages::admin.dashboard')->instance()->metrics();

    expect($m['revenue_cents'])->toBe(200000)
        ->and($m['revenue_trend'])->toBe(100.0);
});

it('excludes orders outside the rolling window', function () {
    Order::factory()->create(['status' => OrderStatus::PROCESSING, 'total_cents' => 500000, 'created_at' => now()->subDays(60)]);

    $m = Livewire::test('pages::admin.dashboard')->instance()->metrics();

    expect($m['revenue_cents'])->toBe(0)
        ->and($m['paid_orders'])->toBe(0);
});

it('builds the quotes-to-orders funnel', function () {
    Quote::factory()->create(['status' => QuoteStatus::DRAFT, 'created_at' => now()]);
    Quote::factory()->create(['status' => QuoteStatus::SENT, 'created_at' => now()]);

    $order = Order::factory()->create(['status' => OrderStatus::PROCESSING]);
    Payment::factory()->paystack()->successful()->create(['order_id' => $order->id]);
    Quote::factory()->create(['status' => QuoteStatus::APPROVED, 'order_id' => $order->id, 'created_at' => now()]);

    $data = Livewire::test('pages::admin.dashboard')->instance()->chartData();

    expect($data['funnel']['data'])->toBe([3, 2, 1, 1, 1]);
});

it('exposes the satisfaction dataset', function () {
    Review::factory()->create(['status' => ReviewStatus::APPROVED, 'rating' => 5]);
    Review::factory()->create(['status' => ReviewStatus::APPROVED, 'rating' => 4]);

    $data = Livewire::test('pages::admin.dashboard')->instance()->chartData();

    expect($data['satisfaction']['total'])->toBe(2)
        ->and($data['satisfaction']['average'])->toBe(4.5)
        ->and($data)->toHaveKey('categories');
});

it('buckets the revenue series by month for long ranges', function () {
    $component = Livewire::test('pages::admin.dashboard')
        ->set('dateFrom', now()->subDays(120)->toDateString())
        ->set('dateTo', now()->toDateString())
        ->call('applyCustom');

    $labels = $component->instance()->chartData()['revenue']['labels'];

    // 120 days → monthly buckets: far fewer labels than days, each carrying a year.
    expect(count($labels))->toBeLessThanOrEqual(6)
        ->and($labels[0])->toMatch('/\d{4}/');
});

it('buckets the revenue series by hour for a single day', function () {
    $labels = Livewire::test('pages::admin.dashboard')
        ->set('dateFrom', now()->toDateString())
        ->set('dateTo', now()->toDateString())
        ->call('applyCustom')
        ->instance()->chartData()['revenue']['labels'];

    expect($labels)->toHaveCount(24)
        ->and($labels[0])->toBe('00:00');
});

it('aggregates sales by county from address pins', function () {
    $user = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id, 'county' => 'Nakuru']);
    Order::factory()->create([
        'user_id' => $user->id, 'address_id' => $address->id,
        'status' => OrderStatus::PROCESSING, 'total_cents' => 80000, 'created_at' => now(),
    ]);

    $data = Livewire::test('pages::admin.dashboard')->instance()->chartData();

    expect($data['countyMap'])->toBe(['Nakuru' => 800]);
});

it('labels and tones a payment activity by its outcome', function () {
    $order = Order::factory()->create();
    $payment = Payment::factory()->create(['order_id' => $order->id, 'status' => PaymentStatus::PENDING]);
    $payment->update(['status' => PaymentStatus::SUCCESS, 'paid_at' => now()]);

    $activity = Activity::where('log_name', 'payment')
        ->where('description', 'updated')->latest('id')->first();
    $dashboard = Livewire::test('pages::admin.dashboard')->instance();

    expect($dashboard->activityLabel($activity))->toBe('Payment received')
        ->and($dashboard->activityTone($activity))->toBe('green');
});

it('changes the preset and dispatches refreshed charts', function () {
    Livewire::test('pages::admin.dashboard')
        ->call('setPreset', '7d')
        ->assertSet('preset', '7d')
        ->assertDispatched('dashboard-updated');
});

it('applies a custom date range', function () {
    Livewire::test('pages::admin.dashboard')
        ->set('dateFrom', now()->subDays(10)->toDateString())
        ->set('dateTo', now()->toDateString())
        ->call('applyCustom')
        ->assertHasNoErrors()
        ->assertSet('preset', 'custom')
        ->assertDispatched('dashboard-updated');
});

it('rejects a custom range whose end precedes its start', function () {
    Livewire::test('pages::admin.dashboard')
        ->set('dateFrom', now()->toDateString())
        ->set('dateTo', now()->subDays(5)->toDateString())
        ->call('applyCustom')
        ->assertHasErrors('dateTo');
});
