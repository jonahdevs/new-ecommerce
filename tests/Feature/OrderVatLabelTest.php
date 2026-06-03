<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;

function orderItemFor(Order $order, float $taxRate): OrderItem
{
    $product = Product::factory()->create();

    return OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'product_sku' => $product->sku,
        'unit_price_cents' => 100000,
        'quantity' => 1,
        'line_total_cents' => 100000,
        'tax_rate' => $taxRate,
        'tax_cents' => 0,
    ]);
}

it('labels VAT with the rate snapshotted on the order items', function () {
    $order = Order::factory()->create();
    orderItemFor($order, 16.0);
    orderItemFor($order, 16.0);

    expect($order->load('items')->vatLabel())->toBe('VAT (16%)');
});

it('keeps a fractional rate in the VAT label', function () {
    $order = Order::factory()->create();
    orderItemFor($order, 12.5);

    expect($order->load('items')->vatLabel())->toBe('VAT (12.5%)');
});

it('falls back to a plain VAT label when item rates differ', function () {
    $order = Order::factory()->create();
    orderItemFor($order, 16.0);
    orderItemFor($order, 8.0);

    expect($order->load('items')->vatLabel())->toBe('VAT');
});

it('falls back to a plain VAT label when no rate was charged', function () {
    $order = Order::factory()->create();
    orderItemFor($order, 0.0);

    expect($order->load('items')->vatLabel())->toBe('VAT');
});
