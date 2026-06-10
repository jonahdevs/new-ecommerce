<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('renders the order show page for the owner', function () {
    $order = Order::factory()->for($this->user)->create([
        'order_number' => 'SHF-TEST01',
        'status' => OrderStatus::PROCESSING,
    ]);

    $this->get(route('account.orders.show', $order))->assertOk();
});

it('shows the order number and status', function () {
    $order = Order::factory()->for($this->user)->create([
        'order_number' => 'SHF-TEST02',
        'status' => OrderStatus::PENDING,
    ]);

    Livewire::test('pages::account.orders.show', ['order' => $order])
        ->assertSee('SHF-TEST02')
        ->assertSee($order->status->label());
});

it('shows a track order button linking to the tracking page', function () {
    $order = Order::factory()->for($this->user)->create([
        'status' => OrderStatus::PROCESSING,
    ]);

    Livewire::test('pages::account.orders.show', ['order' => $order])
        ->assertSee('Track order')
        ->assertSee(route('account.orders.tracking', $order));
});

it('shows a cancelled status badge when the order is cancelled', function () {
    $order = Order::factory()->for($this->user)->create([
        'status' => OrderStatus::CANCELLED,
    ]);

    Livewire::test('pages::account.orders.show', ['order' => $order])
        ->assertSee(OrderStatus::CANCELLED->label());
});

it('returns 403 for an order belonging to a different user', function () {
    $otherUser = User::factory()->create();
    $order = Order::factory()->for($otherUser)->create();

    $this->get(route('account.orders.show', $order))->assertForbidden();
});

it('redirects guests to login', function () {
    auth()->logout();
    $order = Order::factory()->for($this->user)->create();

    $this->get(route('account.orders.show', $order))->assertRedirect(route('login'));
});
