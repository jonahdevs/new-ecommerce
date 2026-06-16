<?php

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Livewire\Livewire;

beforeEach(function () {
    actingAsAdmin();
});

it('loads the payments admin index', function () {
    $this->get(route('admin.payments.index'))->assertOk();
});

it('filters payments by status and provider', function () {
    Payment::factory()->successful()->create(['mpesa_receipt' => 'AAA111']);
    Payment::factory()->stripe()->create(['stripe_payment_intent_id' => 'pi_BBB']);

    Livewire::test('pages::admin.payments.index')
        ->set('filterProvider', 'mpesa')
        ->assertSee('AAA111')
        ->assertDontSee('pi_BBB')
        ->set('filterProvider', '')
        ->set('filterStatus', PaymentStatus::PENDING->value)
        ->assertSee('pi_BBB')
        ->assertDontSee('AAA111');
});

it('searches payments by order number', function () {
    $order = Order::factory()->create(['order_number' => 'SHF-PAYME']);
    Payment::factory()->create(['order_id' => $order->id]);
    Payment::factory()->create(['mpesa_receipt' => 'OTHER999']);

    Livewire::test('pages::admin.payments.index')
        ->set('search', 'PAYME')
        ->assertSee('SHF-PAYME')
        ->assertDontSee('OTHER999');
});

it('shows a payment detail page', function () {
    $payment = Payment::factory()->successful()->create();

    $this->get(route('admin.payments.show', $payment))
        ->assertOk()
        ->assertSee($payment->mpesa_receipt);
});

it('totals the refunded amount in the KPI tiles', function () {
    Payment::factory()->successful()->create(['amount_cents' => 500000, 'refund_cents' => 0]);
    Payment::factory()->successful()->create(['amount_cents' => 300000, 'refund_cents' => 120000]);

    Livewire::test('pages::admin.payments.index')
        ->assertSee('Refunded')
        ->assertSeeHtml(money(120000));
});
