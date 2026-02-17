<?php

use App\Models\Order;
use App\Services\PesawiseService;

describe('PesawiseService', function () {
    it('can build a payment payload for an order', function () {
        $order = Order::factory()->make();
        $service = new PesawiseService();
        $payload = (new ReflectionClass($service))->getMethod('buildPayload')->invoke($service, $order);
        expect($payload)->toBeArray();
        expect($payload['amount'])->toBe($order->total);
        expect($payload['externalId'])->toBe($order->reference);
    });
});
