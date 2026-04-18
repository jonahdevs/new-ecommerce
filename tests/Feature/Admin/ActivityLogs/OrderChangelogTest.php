<?php

use App\Models\Order;
use App\Models\User;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Spatie\Activitylog\Models\Activity;

/**
 * Integration tests for Order model changelog tracking
 * 
 * **Validates: Requirements 1.3, 1.8, 1.9**
 * 
 * Tests that the Order model correctly tracks changes to:
 * - status
 * - payment_status
 * - customer_notes
 */

beforeEach(function () {
    // Create a staff user for testing
    $this->admin = User::factory()->create([
        'email' => 'admin@test.com',
        'is_staff' => true,
    ]);

    $this->actingAs($this->admin);
});

test('order model logs status changes', function () {
    $order = Order::factory()->create([
        'reference' => 'ORD-2026-000001',
        'status' => OrderStatus::PENDING,
    ]);

    $order->update(['status' => OrderStatus::PROCESSING]);

    $activity = Activity::forSubject($order)->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['old']['status'])->toBe(OrderStatus::PENDING->value)
        ->and($activity->properties['attributes']['status'])->toBe(OrderStatus::PROCESSING->value)
        ->and($activity->causer_id)->toBe($this->admin->id);
});

test('order model logs payment_status changes', function () {
    $order = Order::factory()->create([
        'reference' => 'ORD-2026-000002',
        'payment_status' => PaymentStatus::PENDING,
    ]);

    $order->update(['payment_status' => PaymentStatus::PAID]);

    $activity = Activity::forSubject($order)->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['old']['payment_status'])->toBe(PaymentStatus::PENDING->value)
        ->and($activity->properties['attributes']['payment_status'])->toBe(PaymentStatus::PAID->value)
        ->and($activity->causer_id)->toBe($this->admin->id);
});

test('order model logs customer_notes changes', function () {
    $order = Order::factory()->create([
        'reference' => 'ORD-2026-000003',
        'customer_notes' => 'Original notes',
    ]);

    $order->update(['customer_notes' => 'Updated notes']);

    $activity = Activity::forSubject($order)->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['old']['customer_notes'])->toBe('Original notes')
        ->and($activity->properties['attributes']['customer_notes'])->toBe('Updated notes')
        ->and($activity->causer_id)->toBe($this->admin->id);
});

test('order model logs multiple field changes in single update', function () {
    $order = Order::factory()->create([
        'reference' => 'ORD-2026-000004',
        'status' => OrderStatus::PENDING,
        'payment_status' => PaymentStatus::PENDING,
        'customer_notes' => 'Original notes',
    ]);

    $order->update([
        'status' => OrderStatus::PROCESSING,
        'payment_status' => PaymentStatus::PAID,
        'customer_notes' => 'Updated notes',
    ]);

    $activity = Activity::forSubject($order)->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['old'])->toHaveKeys(['status', 'payment_status', 'customer_notes'])
        ->and($activity->properties['attributes'])->toHaveKeys(['status', 'payment_status', 'customer_notes'])
        ->and($activity->properties['old']['status'])->toBe(OrderStatus::PENDING->value)
        ->and($activity->properties['attributes']['status'])->toBe(OrderStatus::PROCESSING->value)
        ->and($activity->properties['old']['payment_status'])->toBe(PaymentStatus::PENDING->value)
        ->and($activity->properties['attributes']['payment_status'])->toBe(PaymentStatus::PAID->value)
        ->and($activity->properties['old']['customer_notes'])->toBe('Original notes')
        ->and($activity->properties['attributes']['customer_notes'])->toBe('Updated notes');
});

test('order model does not log changes to non-tracked fields', function () {
    $order = Order::factory()->create([
        'reference' => 'ORD-2026-000005',
    ]);

    $order->update(['tracking_number' => 'TRACK123']);

    $activity = Activity::forSubject($order)->first();

    // No activity should be logged for non-tracked fields
    expect($activity)->toBeNull();
});

test('order model does not create log entry when no tracked fields change', function () {
    $order = Order::factory()->create([
        'reference' => 'ORD-2026-000006',
        'status' => OrderStatus::PENDING,
    ]);

    // Update only non-tracked field
    $order->update(['tracking_number' => 'TRACK456']);

    $activity = Activity::forSubject($order)->first();

    // No activity should be logged
    expect($activity)->toBeNull();
});

test('order model uses correct log name', function () {
    $order = Order::factory()->create([
        'reference' => 'ORD-2026-000007',
        'status' => OrderStatus::PENDING,
    ]);

    $order->update(['status' => OrderStatus::PROCESSING]);

    $activity = Activity::forSubject($order)->first();

    expect($activity->log_name)->toBe('order');
});

test('order model logs changes without causer when not authenticated', function () {
    // Log out to simulate system change
    auth()->logout();

    $order = Order::factory()->create([
        'reference' => 'ORD-2026-000008',
        'status' => OrderStatus::PENDING,
    ]);

    $order->update(['status' => OrderStatus::PROCESSING]);

    $activity = Activity::forSubject($order)->first();

    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBeNull()
        ->and($activity->properties['old']['status'])->toBe(OrderStatus::PENDING->value)
        ->and($activity->properties['attributes']['status'])->toBe(OrderStatus::PROCESSING->value);
});
