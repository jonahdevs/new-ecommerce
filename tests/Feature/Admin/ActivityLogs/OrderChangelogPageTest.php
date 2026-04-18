<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'email' => 'admin@test.com',
        'is_staff' => true,
    ]);

    $this->actingAs($this->admin);
});

test('order changelog page displays order changes', function () {
    $order = Order::factory()->create(['reference' => 'TEST-001']);

    $order->update(['status' => OrderStatus::PROCESSING]);

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'order', 'id' => $order->id]);

    $activities = $component->get('activities');

    expect($activities)->toHaveCount(1)
        ->and($activities->first()->subject_id)->toBe($order->id)
        ->and($activities->first()->subject_type)->toBe(Order::class);
});

test('order changelog page displays multiple changes', function () {
    $order = Order::factory()->create(['reference' => 'TEST-002']);

    $order->update(['status' => OrderStatus::PROCESSING]);
    $order->update(['payment_status' => PaymentStatus::PAID]);
    $order->update(['customer_notes' => 'Test notes']);

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'order', 'id' => $order->id]);

    $activities = $component->get('activities');

    expect($activities)->toHaveCount(3);
});

test('order changelog page shows empty state when no changes', function () {
    $order = Order::factory()->create(['reference' => 'TEST-003']);

    Activity::where('subject_type', Order::class)
        ->where('subject_id', $order->id)
        ->delete();

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'order', 'id' => $order->id]);

    $component->assertSee('No changes recorded')
        ->assertSee('Changes to this order will appear here');
});

test('order changelog page displays causer information', function () {
    $order = Order::factory()->create(['reference' => 'TEST-004']);

    $this->actingAs($this->admin);

    $order->update(['status' => OrderStatus::PROCESSING]);

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'order', 'id' => $order->id]);

    $component->assertSee($this->admin->name)
        ->assertSee($this->admin->email);
});

test('order changelog page displays system changes', function () {
    $order = Order::factory()->create(['reference' => 'TEST-005']);

    auth()->logout();

    $order->update(['status' => OrderStatus::PROCESSING]);

    $this->actingAs($this->admin);

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'order', 'id' => $order->id]);

    $component->assertSee('System');
});

test('order changelog page throws 404 for non-existent order', function () {
    $this->expectException(ModelNotFoundException::class);

    Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'order', 'id' => 99999]);
});

test('order changelog page formats field labels correctly', function () {
    $order = Order::factory()->create(['reference' => 'TEST-006']);

    $order->update([
        'status' => OrderStatus::PROCESSING,
        'payment_status' => PaymentStatus::PAID,
        'customer_notes' => 'Test notes',
    ]);

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'order', 'id' => $order->id]);

    $component->assertSee('Status:')
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

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'order', 'id' => $order->id]);

    $component->assertSee(OrderStatus::PENDING->label())
        ->assertSee(OrderStatus::PROCESSING->label())
        ->assertSee(PaymentStatus::PENDING->label())
        ->assertSee(PaymentStatus::PAID->label());
});

test('order changelog page paginates results', function () {
    $order = Order::factory()->create(['reference' => 'TEST-008']);

    for ($i = 0; $i < 25; $i++) {
        $order->update(['customer_notes' => "Note {$i}"]);
    }

    $component = Livewire::test('pages::admin.changelog.model-changelog', ['modelType' => 'order', 'id' => $order->id]);

    $activities = $component->get('activities');

    expect($activities)->toHaveCount(20)
        ->and($activities->total())->toBe(25);
});
