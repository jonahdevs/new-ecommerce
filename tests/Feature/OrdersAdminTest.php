<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use Livewire\Livewire;

beforeEach(function () {
    actingAsAdmin();
});

it('loads the orders admin index', function () {
    $this->get(route('admin.orders.index'))->assertOk();
});

it('lists orders and filters by status', function () {
    Order::factory()->create(['status' => OrderStatus::PENDING, 'order_number' => 'SHF-AAA']);
    Order::factory()->create(['status' => OrderStatus::COMPLETED, 'order_number' => 'SHF-BBB']);

    Livewire::test('pages::admin.orders.index')
        ->assertSee('SHF-AAA')
        ->assertSee('SHF-BBB')
        ->set('filterStatus', OrderStatus::PENDING->value)
        ->assertSee('SHF-AAA')
        ->assertDontSee('SHF-BBB');
});

it('searches orders by order number', function () {
    Order::factory()->create(['order_number' => 'SHF-FINDME']);
    Order::factory()->create(['order_number' => 'SHF-OTHER']);

    Livewire::test('pages::admin.orders.index')
        ->set('search', 'FINDME')
        ->assertSee('SHF-FINDME')
        ->assertDontSee('SHF-OTHER');
});

it('updates an order status from the show page', function () {
    $order = Order::factory()->create(['status' => OrderStatus::PENDING]);
    OrderItem::factory()->create(['order_id' => $order->id]);

    Livewire::test('pages::admin.orders.show', ['order' => $order])
        ->set('status', OrderStatus::COMPLETED->value)
        ->call('updateStatus')
        ->assertHasNoErrors();

    expect($order->fresh()->status)->toBe(OrderStatus::COMPLETED);
});
