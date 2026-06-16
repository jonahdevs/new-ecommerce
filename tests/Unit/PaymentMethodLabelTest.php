<?php

use App\Models\Payment;

it('labels the Paystack settlement channel rather than the gateway', function (?string $channel, string $provider, string $expected) {
    $payment = new Payment(['provider' => $provider, 'channel' => $channel]);

    expect($payment->methodLabel())->toBe($expected);
})->with([
    'card channel' => ['card', 'paystack', 'Card'],
    'mobile money channel' => ['mobile_money', 'paystack', 'M-Pesa / Mobile money'],
    'bank transfer channel' => ['bank_transfer', 'paystack', 'Bank transfer'],
    'paystack without channel' => [null, 'paystack', 'Paystack'],
    'direct mpesa' => [null, 'mpesa', 'M-Pesa'],
    'stripe' => [null, 'stripe', 'Card'],
]);
