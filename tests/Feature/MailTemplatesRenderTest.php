<?php

use App\Enums\OrderStatus;
use App\Enums\QuoteStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\User;
use App\Notifications\Orders\KraInvoiceReady;
use App\Notifications\Orders\OrderConfirmed;
use App\Notifications\Orders\OrderStatusChanged;
use App\Notifications\Orders\RefundProcessed;
use App\Notifications\Quotes\QuoteReadyForReview;
use App\Notifications\Quotes\QuoteRequestReceived;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Storage;

/**
 * Render the MailMessage view a notification produces, proving the Blade
 * template compiles with the exact variables the notification passes.
 */
function renderMail(MailMessage $mail): string
{
    return view($mail->view, $mail->viewData)->render();
}

it('renders the order confirmation email', function () {
    $customer = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $customer->id,
        'status' => OrderStatus::PROCESSING,
        'payment_method' => 'mpesa',
    ]);
    OrderItem::factory()->count(2)->create(['order_id' => $order->id]);

    $html = renderMail((new OrderConfirmed($order->load('items')))->toMail($customer));

    expect($html)
        ->toContain($order->order_number)
        ->toContain($customer->name)
        ->toContain('M-Pesa');
});

it('renders the order status update email for each milestone', function (OrderStatus $status) {
    $customer = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $customer->id, 'status' => $status]);
    OrderItem::factory()->create(['order_id' => $order->id]);

    $html = renderMail((new OrderStatusChanged($order->load('items')))->toMail($customer));

    expect($html)
        ->toContain($order->order_number)
        ->toContain($status->label());
})->with([
    'out for delivery' => OrderStatus::OUT_FOR_DELIVERY,
    'completed' => OrderStatus::COMPLETED,
    'cancelled' => OrderStatus::CANCELLED,
]);

it('renders the quote request received email', function () {
    $customer = User::factory()->create();
    $quote = Quote::factory()->create(['user_id' => $customer->id, 'status' => QuoteStatus::DRAFT]);
    QuoteItem::factory()->count(2)->create(['quote_id' => $quote->id]);

    $html = renderMail((new QuoteRequestReceived($quote->load('items')))->toMail($customer));

    expect($html)
        ->toContain($quote->quote_number)
        ->toContain($customer->name);
});

it('renders the quote ready-for-review email', function () {
    $customer = User::factory()->create();
    $quote = Quote::factory()->create([
        'user_id' => $customer->id,
        'status' => QuoteStatus::AWAITING_APPROVAL,
        'total_cents' => 500000,
    ]);
    QuoteItem::factory()->count(2)->create(['quote_id' => $quote->id]);

    $html = renderMail((new QuoteReadyForReview($quote->load('items')))->toMail($customer));

    expect($html)
        ->toContain($quote->quote_number)
        ->toContain($customer->name);
});

it('renders the KRA tax invoice email and attaches the receipt', function () {
    Storage::fake('local');
    $path = 'kra-receipts/test-receipt.pdf';
    Storage::disk('local')->put($path, '%PDF-1.4 fake receipt');

    $customer = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $customer->id,
        'kra_cu_number' => 'KRACU0123456789',
        'kra_receipt_path' => $path,
    ]);

    $mail = (new KraInvoiceReady($order))->toMail($customer);

    expect(renderMail($mail))
        ->toContain($order->order_number)
        ->toContain('KRACU0123456789')
        ->toContain($customer->name);

    // The statutory receipt PDF is attached.
    expect($mail->attachments)->toHaveCount(1);
});

it('renders the refund processed email', function () {
    $customer = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $customer->id]);

    $html = renderMail(
        (new RefundProcessed($order, refundAmountCents: 250000, refundReason: 'Item out of stock'))->toMail($customer)
    );

    expect($html)
        ->toContain($order->order_number)
        ->toContain($customer->name)
        ->toContain('Item out of stock');
});

it('renders the quote ready-for-review email for a guest contact', function () {
    $quote = Quote::factory()->create([
        'user_id' => null,
        'contact_name' => 'Jane Guest',
        'contact_email' => 'jane@example.com',
        'status' => QuoteStatus::AWAITING_APPROVAL,
        'total_cents' => 500000,
    ]);
    QuoteItem::factory()->create(['quote_id' => $quote->id]);

    $html = renderMail((new QuoteReadyForReview($quote->load('items')))->toMail(
        new AnonymousNotifiable
    ));

    expect($html)
        ->toContain($quote->quote_number)
        ->toContain('Jane Guest');
});
