<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Notifications\Orders\NewOrderReceived;
use App\Notifications\Orders\OrderConfirmed;
use App\Notifications\Orders\OrderStatusChanged;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    $this->seed(PermissionSeeder::class);

    $this->staff = User::factory()->create();
    $this->staff->assignRole('staff');
});

it('notifies the customer and staff when a paid order is confirmed', function () {
    $customer = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $customer->id, 'status' => OrderStatus::PENDING]);

    $order->markConfirmed();

    expect($order->fresh()->status)->toBe(OrderStatus::PROCESSING);
    Notification::assertSentTo($customer, OrderConfirmed::class);
    Notification::assertSentTo($this->staff, NewOrderReceived::class);
});

it('also alerts a super-admin who has no explicit permissions', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super-admin');

    $order = Order::factory()->create(['status' => OrderStatus::PENDING]);
    $order->markConfirmed();

    Notification::assertSentTo($superAdmin, NewOrderReceived::class);
});

it('does not re-notify when confirming an order that is no longer pending', function () {
    $order = Order::factory()->create(['status' => OrderStatus::PROCESSING]);

    $order->markConfirmed();

    Notification::assertNothingSent();
});

it('emails the customer when the admin marks an order out for delivery', function () {
    $customer = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $customer->id, 'status' => OrderStatus::PROCESSING]);

    Livewire::test('pages::admin.orders.show', ['order' => $order])
        ->set('status', OrderStatus::OUT_FOR_DELIVERY->value)
        ->call('updateStatus');

    Notification::assertSentTo($customer, OrderStatusChanged::class);
});

it('does not notify when the status is saved unchanged', function () {
    $customer = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $customer->id, 'status' => OrderStatus::DELIVERED]);

    Livewire::test('pages::admin.orders.show', ['order' => $order])
        ->set('status', OrderStatus::DELIVERED->value)
        ->call('updateStatus');

    Notification::assertNotSentTo($customer, OrderStatusChanged::class);
});

it('sends order status emails only for fulfilment milestones', function () {
    $order = Order::factory()->make(['status' => OrderStatus::PROCESSING]);

    // Processing is not a customer-facing milestone.
    expect((new OrderStatusChanged($order))->via($order->user ?? new User))->toBe([]);

    $order->status = OrderStatus::DELIVERED;
    expect((new OrderStatusChanged($order))->via(new User))->toBe(['mail']);
});

it('respects a muted order preference', function () {
    $user = User::factory()->create(['notification_preferences' => ['orders' => ['delivered' => false]]]);
    $order = Order::factory()->make(['user_id' => $user->id, 'status' => OrderStatus::DELIVERED]);

    expect((new OrderStatusChanged($order))->via($user))->toBe([]);
});
