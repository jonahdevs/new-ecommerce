<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuoteRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    // =========================================================================
    //  Fires when a customer submits a quote request (PENDING_QUOTE created).
    //  Sent to the admin team email — not to the customer.
    //
    //  The customer already lands on the quote-success confirmation page
    //  so no customer notification is needed at this stage.
    //
    //  Implements ShouldQueue so a slow SMTP server never delays the
    //  checkout redirect. The quote is already saved before this fires.
    //
    //  Triggered from:
    //    QuotationService::notifyRequested($order)
    //  Which is called from:
    //    order-summary component → processQuoteRequest() after DB transaction
    // =========================================================================

    public function __construct(public readonly Order $order)
    {
    }

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(): MailMessage
    {
        $customerName = $this->order->user?->name ?? 'A customer';
        $customerEmail = $this->order->user?->email ?? '';
        $quotationType = ucfirst($this->order->quotation_type ?? 'unknown');
        $itemCount = $this->order->items()->count();
        $subtotal = format_currency($this->order->subtotal);
        $county = $this->order->shipping_address['county'] ?? 'Unknown';
        $zone = $this->order->shipping_address['zone'] ?? 'Unknown zone';
        $weightKg = $this->order->shipping_snapshot['weight_kg'] ?? '—';
        $adminUrl = route('admin.orders.quotations.show', $this->order);

        return (new MailMessage)
            ->subject("New Quote Request — {$this->order->reference}")
            ->greeting('New quotation request received')
            ->line("{$customerName} ({$customerEmail}) has submitted a **{$quotationType} quotation** request.")
            ->line("**Reference:** {$this->order->reference}")
            ->line("**Items:** {$itemCount} item(s) · Subtotal {$subtotal}")
            ->line("**Delivery county:** {$county} ({$zone})")
            ->line("**Package weight:** {$weightKg} kg")
            ->line('Please log in to the admin panel to review the request and send the customer a priced quotation.')
            ->action('Review & Price Quotation', $adminUrl)
            ->salutation('Sheffield Africa · Orders Team');
    }
}
