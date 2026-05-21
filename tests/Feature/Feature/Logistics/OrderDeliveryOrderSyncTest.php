<?php

use App\Enums\DeliveryOrderStatus;
use App\Enums\OrderStatus;
use App\Models\DeliveryOrder;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── Helpers ──────────────────────────────────────────────────────────────────

function makeShippedOrder(): array
{
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create([
        'status' => OrderStatus::SHIPPED->value,
        'quote_id' => null,
    ]);

    $delivery = DeliveryOrder::create([
        'order_id' => $order->id,
        'shipping_cost' => 500,
        'status' => DeliveryOrderStatus::OUT_FOR_DELIVERY,
    ]);

    return [$order, $delivery];
}

// ─── Delivery → Order: DELIVERED ─────────────────────────────────────────────

it('auto-sets order to delivered when delivery order is delivered', function () {
    [$order, $delivery] = makeShippedOrder();

    $delivery->update(['status' => DeliveryOrderStatus::DELIVERED]);

    expect($order->fresh()->status)->toBe(OrderStatus::DELIVERED);
});

it('auto-sets order to delivered when pickup station delivery is collected', function () {
    [$order, $delivery] = makeShippedOrder();

    $delivery->update(['status' => DeliveryOrderStatus::COLLECTED]);

    expect($order->fresh()->status)->toBe(OrderStatus::DELIVERED);
});

// ─── Delivery → Order: RETURNED ───────────────────────────────────────────────

it('auto-sets order to returned when delivery order is returned', function () {
    [$order, $delivery] = makeShippedOrder();

    $delivery->update(['status' => DeliveryOrderStatus::RETURNED]);

    expect($order->fresh()->status)->toBe(OrderStatus::RETURNED);
});

// ─── No spurious transitions ──────────────────────────────────────────────────

it('does not change order status for intermediate delivery states', function (DeliveryOrderStatus $status) {
    [$order, $delivery] = makeShippedOrder();

    $delivery->update(['status' => $status]);

    expect($order->fresh()->status)->toBe(OrderStatus::SHIPPED);
})->with([
    'picked_up' => [DeliveryOrderStatus::PICKED_UP],
    'in_transit' => [DeliveryOrderStatus::IN_TRANSIT],
    'failed' => [DeliveryOrderStatus::FAILED],
    'at_station' => [DeliveryOrderStatus::AT_STATION],
    'returning' => [DeliveryOrderStatus::RETURNING],
]);

it('does not transition order when it is already delivered', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create(['status' => OrderStatus::DELIVERED->value]);

    $delivery = DeliveryOrder::create([
        'order_id' => $order->id,
        'shipping_cost' => 500,
        'status' => DeliveryOrderStatus::IN_TRANSIT,
    ]);

    $delivery->update(['status' => DeliveryOrderStatus::DELIVERED]);

    expect($order->fresh()->status)->toBe(OrderStatus::DELIVERED);
});

// ─── Order CANCELLED → delivery order cancelled ───────────────────────────────

it('cancels an active delivery order when order is cancelled', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create([
        'status' => OrderStatus::PROCESSING->value,
        'quote_id' => null,
    ]);

    $delivery = DeliveryOrder::create([
        'order_id' => $order->id,
        'shipping_cost' => 500,
        'status' => DeliveryOrderStatus::PENDING,
    ]);

    $order->transitionTo(OrderStatus::CANCELLED, changedByType: 'system');

    expect($delivery->fresh()->status)->toBe(DeliveryOrderStatus::CANCELLED);
});

it('does not touch a terminal delivery order when order is cancelled', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create([
        'status' => OrderStatus::PROCESSING->value,
        'quote_id' => null,
    ]);

    $delivery = DeliveryOrder::create([
        'order_id' => $order->id,
        'shipping_cost' => 500,
        'status' => DeliveryOrderStatus::DELIVERED,
    ]);

    $order->transitionTo(OrderStatus::CANCELLED, changedByType: 'system');

    expect($delivery->fresh()->status)->toBe(DeliveryOrderStatus::DELIVERED);
});
