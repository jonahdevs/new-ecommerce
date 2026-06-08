<?php

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    Storage::fake('local');
});

it('returns 404 when order has no kra_receipt_path', function () {
    $order = Order::factory()->create(['kra_receipt_path' => null]);

    $this->get(route('admin.orders.kra-receipt', $order))
        ->assertNotFound();
});

it('returns 404 when receipt file does not exist on disk', function () {
    $order = Order::factory()->create(['kra_receipt_path' => 'receipts/missing.pdf']);

    $this->get(route('admin.orders.kra-receipt', $order))
        ->assertNotFound();
});

it('streams the kra receipt pdf inline', function () {
    $path = 'receipts/test-receipt.pdf';
    Storage::disk('local')->put($path, '%PDF-1.4 fake pdf content');

    $order = Order::factory()->create(['kra_receipt_path' => $path]);

    $this->get(route('admin.orders.kra-receipt', $order))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

it('requires authentication to view the receipt', function () {
    auth()->logout();

    $path = 'receipts/test-receipt.pdf';
    Storage::disk('local')->put($path, '%PDF-1.4 fake pdf content');

    $order = Order::factory()->create(['kra_receipt_path' => $path]);

    $this->get(route('admin.orders.kra-receipt', $order))
        ->assertRedirect(route('login'));
});
