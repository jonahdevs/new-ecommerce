<?php

use App\Enums\OrderStatus;
use App\Enums\QuoteStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use App\Notifications\Inventory\LowStockAlert;
use App\Notifications\Messages\WhatsAppMessage;
use App\Notifications\Orders\NewOrderReceived;
use App\Notifications\Orders\OrderStatusChanged;
use App\Notifications\Orders\RefundProcessed;
use App\Notifications\Quotes\NewQuoteRequested;
use App\Notifications\Quotes\QuoteDecisionReceived;
use App\Notifications\Quotes\QuoteReadyForReview;
use App\Notifications\Quotes\QuoteRequestReceived;

/**
 * @param  WhatsAppMessage  $message
 * @return array<int, string>
 */
function whatsappBodyParams($message): array
{
    expect($message->components)->toHaveCount(1)
        ->and($message->components[0]['type'])->toBe('body');

    return array_map(fn ($p) => $p['text'], $message->components[0]['parameters']);
}

it('builds the order_status_update template', function () {
    $customer = User::factory()->create(['name' => 'Jonah']);
    $order = Order::factory()->create(['user_id' => $customer->id, 'status' => OrderStatus::OUT_FOR_DELIVERY]);

    $message = (new OrderStatusChanged($order))->toWhatsapp($customer);

    expect($message->template)->toBe('order_status_update');
    expect(whatsappBodyParams($message))->toBe(['Jonah', $order->order_number, 'Out for delivery']);
});

it('builds the refund_processed template', function () {
    $customer = User::factory()->create(['name' => 'Jonah']);
    $order = Order::factory()->create(['user_id' => $customer->id]);

    $message = (new RefundProcessed($order, 250000))->toWhatsapp($customer);

    expect($message->template)->toBe('refund_processed');
    expect(whatsappBodyParams($message))->toBe(['Jonah', $order->order_number, money(250000)]);
});

it('builds the quote_ready template, falling back to contact name for guests', function () {
    $quote = Quote::factory()->create(['user_id' => null, 'contact_name' => 'Guest Buyer']);

    $message = (new QuoteReadyForReview($quote))->toWhatsapp(new User);

    expect($message->template)->toBe('quote_ready');
    expect(whatsappBodyParams($message))->toBe(['Guest Buyer', $quote->quote_number]);
});

it('builds the quote_received template', function () {
    $customer = User::factory()->create(['name' => 'Jonah']);
    $quote = Quote::factory()->create(['user_id' => $customer->id]);

    $message = (new QuoteRequestReceived($quote))->toWhatsapp($customer);

    expect($message->template)->toBe('quote_received');
    expect(whatsappBodyParams($message))->toBe(['Jonah', $quote->quote_number]);
});

it('builds the staff_new_order template', function () {
    $customer = User::factory()->create(['name' => 'Jonah']);
    $order = Order::factory()->create(['user_id' => $customer->id]);

    $message = (new NewOrderReceived($order))->toWhatsapp(new User);

    expect($message->template)->toBe('staff_new_order');
    expect(whatsappBodyParams($message))->toBe(['Jonah', $order->order_number, money($order->total_cents)]);
});

it('builds the staff_low_stock template', function () {
    $product = Product::factory()->create(['name' => 'Steel Beam']);

    $message = (new LowStockAlert($product, 3))->toWhatsapp(new User);

    expect($message->template)->toBe('staff_low_stock');
    expect(whatsappBodyParams($message))->toBe(['Steel Beam', '3']);
});

it('builds the staff_new_quote template with item count', function () {
    $quote = Quote::factory()->create(['contact_name' => 'Acme Ltd']);

    $message = (new NewQuoteRequested($quote))->toWhatsapp(new User);

    expect($message->template)->toBe('staff_new_quote');
    expect(whatsappBodyParams($message))->toBe(['Acme Ltd', $quote->quote_number, '0']);
});

it('builds the staff_quote_decision template reflecting the decision', function () {
    $quote = Quote::factory()->create(['contact_name' => 'Acme Ltd', 'status' => QuoteStatus::APPROVED]);

    $message = (new QuoteDecisionReceived($quote))->toWhatsapp(new User);

    expect($message->template)->toBe('staff_quote_decision');
    expect(whatsappBodyParams($message))->toBe(['Acme Ltd', $quote->quote_number, 'approved']);
});
