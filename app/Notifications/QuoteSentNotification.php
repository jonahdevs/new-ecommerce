<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuoteSentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    // =========================================================================
    //  Fires when admin prices and sends a quotation (QUOTE_SENT transition).
    //  Sent to the customer — this is their prompt to log in and respond.
    //
    //  This email IS the customer's entry point to the portal accept/reject
    //  page, so the action URL must link directly to their quotation.
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
        $customerName = $this->order->user?->name ?? 'Customer';
        $total = format_currency($this->order->total);
        $shipping = $this->order->shipping_cents > 0
            ? format_currency($this->order->shipping)
            : 'Free';
        $expiresAt = $this->order->expires_at?->format('M d, Y') ?? 'N/A';
        $portalUrl = route('customer.orders.show', $this->order);

        // Build a readable item list for the email body
        $itemLines = $this->order->items->map(function ($item) {
            $name = $item->product_snapshot['name'] ?? 'Product';
            $qty = $item->quantity;
            $price = format_currency($item->unit_price_cents / 100);
            $total = format_currency($item->total_cents / 100);
            return "• {$name} × {$qty} @ {$price} = {$total}";
        })->join("\n");

        return (new MailMessage)
            ->subject("Your Quotation is Ready — {$this->order->reference}")
            ->greeting("Hello {$customerName},")
            ->line('Your quotation from Sheffield Africa is ready for your review.')
            ->line("**Quotation reference:** {$this->order->reference}")
            ->line("**Items:**")
            ->line($itemLines)
            ->line("**Delivery:** {$shipping}")
            ->line("**Total:** {$total}")
            ->line("**Valid until:** {$expiresAt}")
            ->line('Please log in to your account to review the full quotation and either accept or reject it.')
            ->action('View & Respond to Quotation', $portalUrl)
            ->line('If you have any questions about this quotation, please reply to this email or contact our team.')
            ->salutation('Sheffield Africa · Sales Team');
    }
}
