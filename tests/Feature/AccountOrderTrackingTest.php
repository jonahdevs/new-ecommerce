<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('renders the order tracking page for the owner', function () {
    $order = Order::factory()->for($this->user)->create([
        'status' => OrderStatus::PROCESSING,
    ]);

    $this->get(route('account.orders.tracking', $order))->assertOk();
});

it('shows all fulfillment timeline steps on the tracking page', function () {
    $order = Order::factory()->for($this->user)->create([
        'status' => OrderStatus::PROCESSING,
    ]);

    Livewire::test('pages::account.orders.tracking', ['order' => $order])
        ->assertSee('Order Placed')
        ->assertSee('Being Prepared')
        ->assertSee('Out for Delivery')
        ->assertSee('Delivered');
});

it('shows the cancelled terminal step when the order is cancelled', function () {
    $order = Order::factory()->for($this->user)->create([
        'status' => OrderStatus::CANCELLED,
    ]);

    Livewire::test('pages::account.orders.tracking', ['order' => $order])
        ->assertSee('Order Cancelled');
});

it('returns 403 on the tracking page for an order belonging to a different user', function () {
    $otherUser = User::factory()->create();
    $order = Order::factory()->for($otherUser)->create();

    $this->get(route('account.orders.tracking', $order))->assertForbidden();
});

it('redirects guests to login from the tracking page', function () {
    auth()->logout();
    $order = Order::factory()->for($this->user)->create();

    $this->get(route('account.orders.tracking', $order))->assertRedirect(route('login'));
});
