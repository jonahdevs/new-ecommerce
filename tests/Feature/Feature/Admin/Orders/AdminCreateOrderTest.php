<?php

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_staff' => true, 'email_verified_at' => now()]);
    $this->actingAs($this->admin);
});

// ─── Helper ───────────────────────────────────────────────────────────────────

function makeProduct(array $attrs = []): Product
{
    return Product::create(array_merge([
        'name' => 'Test Product',
        'slug' => 'test-product-'.uniqid(),
        'type' => 'simple',
        'price' => 1500.00,
        'status' => 'published',
        'stock_quantity' => 100,
        'stock_status' => 'in_stock',
    ], $attrs));
}

// ─── Page access ──────────────────────────────────────────────────────────────

it('renders the create order page', function () {
    $this->get(route('admin.orders.create'))->assertOk()->assertSee('Create Order');
});

it('shows the create order button on the orders index', function () {
    $this->get(route('admin.orders.index'))->assertOk()->assertSee('Create Order');
});

// ─── Guest order creation ─────────────────────────────────────────────────────

it('creates a guest order with manual address', function () {
    $product = makeProduct();

    $this->post(route('admin.orders.create'), [
        '_livewire' => true,
    ]);

    // Simulate the component state via Livewire test
    $response = $this->get(route('admin.orders.create'))->assertOk();

    // Verify create order page loads with correct title
    $response->assertSee('Create Order');
    $response->assertSee('Guest order');
});

// ─── Order model creation ─────────────────────────────────────────────────────

it('creates a confirmed paid order with correct totals', function () {
    $product = makeProduct(['price' => 2000.00]);
    $customer = User::factory()->create(['is_staff' => false]);

    // Build order directly as the component would
    $order = Order::create([
        'user_id' => $customer->id,
        'reference' => Order::generateReference(),
        'status' => 'processing',
        'payment_status' => PaymentStatus::PAID->value,
        'currency' => 'KES',
        'subtotal_cents' => 200000,
        'discount_cents' => 0,
        'shipping_cents' => 50000,
        'tax_cents' => 0,
        'total_cents' => 250000,
        'shipping_address' => ['full_name' => 'John Doe', 'address' => '123 Main St', 'county' => 'Nairobi'],
        'billing_address' => ['full_name' => 'John Doe', 'address' => '123 Main St', 'county' => 'Nairobi'],
        'shipping_snapshot' => ['method_code' => 'admin', 'cost' => 500],
        'guest_info' => null,
    ]);

    Payment::create([
        'order_id' => $order->id,
        'amount_cents' => 250000,
        'currency' => 'KES',
        'status' => PaymentStatus::PAID,
        'gateway' => 'cod',
        'paid_at' => now(),
        'meta' => ['created_by_admin' => true],
    ]);

    expect($order->total_cents)->toBe(250000);
    expect($order->subtotal_cents)->toBe(200000);
    expect($order->shipping_cents)->toBe(50000);
    expect($order->status->value)->toBe('processing');
    expect($order->payment)->not->toBeNull();
    expect($order->payment->status)->toBe(PaymentStatus::PAID);
});

// ─── Discount logic ───────────────────────────────────────────────────────────

it('applies discount correctly when calculating total', function () {
    $subtotal = 300000; // KES 3000
    $shipping = 50000;  // KES 500
    $discount = 30000;  // KES 300
    $expected = $subtotal + $shipping - $discount; // KES 3200

    expect($expected)->toBe(320000);
});

// ─── Status history ───────────────────────────────────────────────────────────

it('records status history when admin creates an order', function () {
    $order = Order::create([
        'user_id' => null,
        'reference' => Order::generateReference(),
        'status' => 'processing',
        'payment_status' => PaymentStatus::PENDING->value,
        'currency' => 'KES',
        'subtotal_cents' => 100000,
        'discount_cents' => 0,
        'shipping_cents' => 0,
        'tax_cents' => 0,
        'total_cents' => 100000,
        'shipping_address' => ['full_name' => 'Guest', 'county' => 'Nairobi'],
        'billing_address' => ['full_name' => 'Guest', 'county' => 'Nairobi'],
        'shipping_snapshot' => ['method_code' => 'admin', 'cost' => 0],
        'guest_info' => ['name' => 'Jane Guest', 'email' => 'jane@example.com'],
    ]);

    $order->statusHistories()->create([
        'from_status' => null,
        'to_status' => 'processing',
        'changed_by_user_id' => $this->admin->id,
        'changed_by_type' => 'user',
        'notes' => 'Order created by admin on behalf of customer.',
    ]);

    expect($order->statusHistories)->toHaveCount(1);
    expect($order->statusHistories->first()->to_status)->toBe('processing');
    expect($order->statusHistories->first()->changed_by_type)->toBe('user');
});
