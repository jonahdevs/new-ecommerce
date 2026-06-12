<?php

use App\Models\Order;
use App\Models\Quote;
use App\Support\NumberSequence;

it('increments a sequence atomically per key', function () {
    expect(NumberSequence::next('test:key'))->toBe(1)
        ->and(NumberSequence::next('test:key'))->toBe(2)
        ->and(NumberSequence::next('other:key'))->toBe(1);
});

it('generates sequential, unique order numbers', function () {
    $first = Order::generateNumber();
    $second = Order::generateNumber();

    expect($first)->not->toBe($second)
        ->and($first)->toEndWith('00001')
        ->and($second)->toEndWith('00002');
});

it('does not reuse an order number after rows are deleted', function () {
    Order::factory()->create(['order_number' => Order::generateNumber()]); // …00001
    Order::query()->delete();

    // A count()+1 scheme would hand back …00001 again; the atomic counter must not.
    expect(Order::generateNumber())->toEndWith('00002');
});

it('keeps the order and quote sequences independent', function () {
    expect(Order::generateNumber())->toEndWith('00001')
        ->and(Quote::generateNumber())->toEndWith('00001')
        ->and(Order::generateNumber())->toEndWith('00002')
        ->and(Quote::generateNumber())->toEndWith('00002');
});
