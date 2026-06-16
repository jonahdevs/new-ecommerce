<?php

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

it('encrypts the payload at rest but reads it back transparently', function () {
    $order = Order::factory()->create();
    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'payload' => ['event' => 'charge.success', 'data' => ['last4' => '4242']],
    ]);

    // Round-trips through the model cast.
    expect($payment->fresh()->payload)->toBe(['event' => 'charge.success', 'data' => ['last4' => '4242']]);

    // Stored ciphertext is not the plaintext JSON.
    $raw = DB::table('payments')->where('id', $payment->id)->value('payload');
    expect($raw)->not->toContain('charge.success')
        ->and($raw)->not->toContain('4242');
});

it('prunes payloads older than the retention window but keeps recent ones and structured columns', function () {
    $order = Order::factory()->create();

    $old = Payment::factory()->create([
        'order_id' => $order->id,
        'paystack_reference' => 'SHF-OLD-REF',
        'payload' => ['event' => 'charge.success'],
        'created_at' => now()->subYears(6),
    ]);
    $recent = Payment::factory()->create([
        'order_id' => $order->id,
        'payload' => ['event' => 'charge.success'],
        'created_at' => now()->subMonths(2),
    ]);

    $this->artisan('payments:prune-payloads')->assertSuccessful();

    $old->refresh();
    expect($old->payload)->toBeNull()
        // Structured financial columns survive the prune.
        ->and($old->paystack_reference)->toBe('SHF-OLD-REF')
        ->and($recent->fresh()->payload)->not->toBeNull();
});

it('respects a custom retention window', function () {
    $order = Order::factory()->create();
    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'payload' => ['event' => 'charge.success'],
        'created_at' => now()->subYears(2),
    ]);

    $this->artisan('payments:prune-payloads', ['--years' => 1])->assertSuccessful();

    expect($payment->fresh()->payload)->toBeNull();
});
