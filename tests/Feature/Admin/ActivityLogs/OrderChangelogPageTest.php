<?php

use App\Models\Order;
use App\Models\User;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

/**
 * Integration tests for Order Changelog Page
 * 
 * **Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8**
 * 
 * Tests the Order changelog Livewire component that displays
 * change history for orders.
 */

beforeEach(function () {
    // Create a staff user for testing
    $this->admin = User::factory()->create([
        'email' => 'admin@test.com',
        'is_staff' => true,
    ]);

    $this->actingAs($this->admin);
});

test('order changelog page displays order changes', function () {
    $order = Order::factory()->create(['reference' => 'TEST-001']);

    // Make a change to create activity log
    $order->update(['status' => OrderStatus::PROCESSING]);

    $component = Livewire::test('admin.changelog.order-changelog', ['id' => $order->id]);

    $activities = $component->get('activities');

    expect($activities)->toHaveCount(1)
        ->and($activities->first()->subject_id)->toBe($order->id)
        ->and($activities->first()->subject_type)->toBe(Order::class);
});

test('order changelog page displays multiple changes', function () {
    $order = Order::factory()->create(['reference' => 'TEST-002']);

    // Make multiple changes
    $order->update(['status' => OrderStatus::PROCESSING]);
    $order->update(['payment_status' => PaymentStatus::PAID]);
    $order->update(['customer_notes' => 'Test notes']);

    $component = Livewire::test('admin.changelog.order-changelog', ['id' => $order->id]);

    $activities = $component->get('activities');

    expect($activities)->toHaveCount(3);
});

test('order changelog page shows empty state when no changes', function () {
    $order = Order::factory()->create(['reference' => 'TEST-003']);

    // Delete any auto-generated activities
    Activity::where('subject_type', Order::class)
        ->where('subject_id', $order->id)
        ->delete();

    $component = Livewire::test('admin.changelog.order-changelog', ['id' => $order->id]);

    $component->assertSee('No changes recorded')
        ->assertSee('Changes to this order will appear here');
});

test('order changelog page displays causer information', function () {
    $order = Order::factory()->create(['reference' => 'TEST-004']);

    $this->actingAs($this->admin);

    $order->update(['status' => OrderStatus::PROCESSING]);

    $component = Livewire::test('admin.changelog.order-changelog', ['id' => $order->id]);

    $component->assertSee($this->admin->name)
        ->assertSee($this->admin->email);
});

test('order changelog page displays system changes', function () {
    $order = Order::factory()->create(['reference' => 'TEST-005']);

    // Log out to simulate system change
    auth()->logout();

    $order->update(['status' => OrderStatus::PROCESSING]);

    $this->actingAs($this->admin);

    $component = Livewire::test('admin.changelog.order-changelog', ['id' => $order->id]);

    $component->assertSee('System');
});

test('order changelog page throws 404 for non-existent order', function () {
    $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    Livewire::test('admin.changelog.order-changelog', ['id' => 99999]);
});

test('order changelog page formats field labels correctly', function () {
    $order = Order::factory()->create(['reference' => 'TEST-006']);

    $order->update([
        'status' => OrderStatus::PROCESSING,
        'payment_status' => PaymentStatus::PAID,
        'customer_notes' => 'Test notes',
    ]);

    $component = Livewire::test('admin.changelog.order-changelog', ['id' => $order->id]);

    $component->assertSee('Order Status:')
        ->assertSee('Payment Status:')
        ->assertSee('Notes:');
});

test('order changelog page formats enum values correctly', function () {
    $order = Order::factory()->create([
        'reference' => 'TEST-007',
        'status' => OrderStatus::PENDING,
        'payment_status' => PaymentStatus::PENDING,
    ]);

    $order->update([
        'status' => OrderStatus::PROCESSING,
        'payment_status' => PaymentStatus::PAID,
    ]);

    $component = Livewire::test('admin.changelog.order-changelog', ['id' => $order->id]);

    // Check that enum labels are displayed (not raw values)
    $component->assertSee(OrderStatus::PENDING->label())
        ->assertSee(OrderStatus::PROCESSING->label())
        ->assertSee(PaymentStatus::PENDING->label())
        ->assertSee(PaymentStatus::PAID->label());
});

test('order changelog page paginates results', function () {
    $order = Order::factory()->create(['reference' => 'TEST-008']);

    // Create 25 changes (more than the 20 per page limit)
    for ($i = 0; $i < 25; $i++) {
        $order->update(['customer_notes' => "Note {$i}"]);
    }

    $component = Livewire::test('admin.changelog.order-changelog', ['id' => $order->id]);

    $activities = $component->get('activities');

    // Should only show 20 per page
    expect($activities)->toHaveCount(20)
        ->and($activities->total())->toBe(25);
});
