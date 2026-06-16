<?php

use App\Enums\OrderStatus;
use App\Enums\QuoteStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductImage;
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
        ->toContain(e($customer->name))
        ->toContain('M-Pesa');
});

it('renders the product cover image in the order confirmation email', function () {
    $product = Product::factory()->create();
    ProductImage::create([
        'product_id' => $product->id,
        'path' => 'products/oven-cover.jpg',
        'is_cover' => true,
        'sort_order' => 0,
    ]);

    $customer = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $customer->id]);
    OrderItem::factory()->forProduct($product->load('images'))->create(['order_id' => $order->id]);

    $html = renderMail((new OrderConfirmed($order->load('items')))->toMail($customer));

    // The src must be an absolute URL so it resolves inside email clients,
    // not a relative /storage path that only works on the app domain.
    expect($html)->toContain(url('/storage/products/oven-cover.jpg'));
    expect(url('/storage/products/oven-cover.jpg'))->toStartWith('http');
});

it('renders the order status update email for each milestone', function (OrderStatus $status) {
    $customer = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $customer->id, 'status' => $status]);
    OrderItem::factory()->create(['order_id' => $order->id]);

    $html = renderMail((new OrderStatusChanged($order->load('items')))->toMail($customer));

    expect($html)
        ->toContain($order->order_number)
        // The heading reflects the actual status, e.g. "Order Processing".
        ->toContain('Order '.$status->label());
})->with([
    'processing' => OrderStatus::PROCESSING,
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
        ->toContain(e($customer->name));
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
        ->toContain(e($customer->name))
        // Shows the quote reference block and the ready message, not the stepper.
        ->toContain('Quote reference')
        ->toContain('Your quotation is ready')
        ->not->toContain('Requested')
        ->not->toContain('Accepted');
});

it('includes the quote terms & conditions on the quotation-ready email when present', function () {
    $customer = User::factory()->create();
    $quote = Quote::factory()->create([
        'user_id' => $customer->id,
        'status' => QuoteStatus::AWAITING_APPROVAL,
        'total_cents' => 500000,
        'terms' => 'Payment due within 30 days. Goods remain our property until paid in full.',
    ]);
    QuoteItem::factory()->create(['quote_id' => $quote->id]);

    $html = renderMail((new QuoteReadyForReview($quote->load('items')))->toMail($customer));

    expect($html)->toContain('Payment due within 30 days');
});

it('omits the terms text on the quotation-ready email when there are no terms', function () {
    $customer = User::factory()->create();
    $quote = Quote::factory()->create([
        'user_id' => $customer->id,
        'status' => QuoteStatus::AWAITING_APPROVAL,
        'total_cents' => 500000,
        'terms' => 'UNIQUE-TERMS-MARKER',
    ]);
    QuoteItem::factory()->create(['quote_id' => $quote->id]);

    expect(renderMail((new QuoteReadyForReview($quote->load('items')))->toMail($customer)))
        ->toContain('UNIQUE-TERMS-MARKER');

    $quote->update(['terms' => null]);

    expect(renderMail((new QuoteReadyForReview($quote->fresh()->load('items')))->toMail($customer)))
        ->not->toContain('UNIQUE-TERMS-MARKER');
});

it('shows the validity note on the quotation-ready email only when not expired', function () {
    $customer = User::factory()->create();

    $valid = Quote::factory()->create([
        'user_id' => $customer->id,
        'status' => QuoteStatus::AWAITING_APPROVAL,
        'total_cents' => 500000,
        'expires_at' => now()->addDays(14),
    ]);
    QuoteItem::factory()->create(['quote_id' => $valid->id]);

    expect(renderMail((new QuoteReadyForReview($valid->load('items')))->toMail($customer)))
        ->toContain('valid until')
        ->toContain($valid->expires_at->format('d F Y'));

    $expired = Quote::factory()->create([
        'user_id' => $customer->id,
        'status' => QuoteStatus::AWAITING_APPROVAL,
        'total_cents' => 500000,
        'expires_at' => now()->subDay(),
    ]);
    QuoteItem::factory()->create(['quote_id' => $expired->id]);

    expect(renderMail((new QuoteReadyForReview($expired->load('items')))->toMail($customer)))
        ->not->toContain('valid until');
});

it('renders the full price breakdown on the quotation-ready email when applicable', function () {
    $customer = User::factory()->create();
    $quote = Quote::factory()->create([
        'user_id' => $customer->id,
        'status' => QuoteStatus::AWAITING_APPROVAL,
        'subtotal_cents' => 1000000,
        'discount_cents' => 50000,
        'shipping_cents' => 30000,
        'vat_rate' => 16,
        'vat_cents' => 156800,
        'tax_inclusive' => false,
        'total_cents' => 1136800,
    ]);
    QuoteItem::factory()->create(['quote_id' => $quote->id]);

    $html = renderMail((new QuoteReadyForReview($quote->load('items')))->toMail($customer));

    expect($html)
        ->toContain('Subtotal')
        ->toContain('Discount')
        ->toContain('Shipping')
        ->toContain('VAT (16%)')
        ->toContain(money(156800));
});

it('omits inapplicable breakdown lines on the quotation-ready email', function () {
    $customer = User::factory()->create();
    $quote = Quote::factory()->create([
        'user_id' => $customer->id,
        'status' => QuoteStatus::AWAITING_APPROVAL,
        'subtotal_cents' => 1000000,
        'discount_cents' => 0,
        'shipping_cents' => 0,
        'vat_rate' => 0,
        'vat_cents' => 0,
        'total_cents' => 1000000,
    ]);
    QuoteItem::factory()->create(['quote_id' => $quote->id]);

    $html = renderMail((new QuoteReadyForReview($quote->load('items')))->toMail($customer));

    expect($html)
        ->toContain('Subtotal')
        ->not->toContain('Discount')
        ->not->toContain('Shipping')
        ->not->toContain('VAT (');
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
        ->toContain(e($customer->name));

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
