<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Services\PackingListService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('generates a packing list when order transitions to processing', function () {
    Storage::fake('local');

    $this->mock(PackingListService::class)
        ->shouldReceive('generate')
        ->once();

    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create([
        'status' => OrderStatus::PENDING->value,
        'quote_id' => null,
    ]);

    $order->transitionTo(OrderStatus::PROCESSING, changedByType: 'user');

    expect($order->fresh()->status)->toBe(OrderStatus::PROCESSING);
});

it('does not generate a packing list for other status transitions', function () {
    Storage::fake('local');

    $this->mock(PackingListService::class)
        ->shouldReceive('generate')
        ->never();

    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create([
        'status' => OrderStatus::PENDING->value,
        'quote_id' => null,
    ]);

    $order->transitionTo(OrderStatus::CANCELLED, changedByType: 'user');

    expect($order->fresh()->status)->toBe(OrderStatus::CANCELLED);
});

it('stores packing_list_path on the order after generation', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create([
        'status' => OrderStatus::PENDING->value,
        'quote_id' => null,
    ]);

    $path = 'packing-lists/PackingSlip-'.$order->reference.'.pdf';
    Storage::disk('local')->put($path, 'fake-pdf-content');
    $order->update(['packing_list_path' => $path]);

    $service = new PackingListService;

    expect($order->fresh()->packing_list_path)->toBe($path);
    expect($service->exists($order->fresh()))->toBeTrue();
});

it('reports packing list does not exist when path is null', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create([
        'status' => OrderStatus::PENDING->value,
        'quote_id' => null,
    ]);

    $service = new PackingListService;

    expect($service->exists($order))->toBeFalse();
});

it('does not block status transition when packing list generation fails', function () {
    Storage::fake('local');

    $this->mock(PackingListService::class)
        ->shouldReceive('generate')
        ->andThrow(new RuntimeException('PDF generation failed'));

    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create([
        'status' => OrderStatus::PENDING->value,
        'quote_id' => null,
    ]);

    $order->transitionTo(OrderStatus::PROCESSING, changedByType: 'user');

    expect($order->fresh()->status)->toBe(OrderStatus::PROCESSING);
});
