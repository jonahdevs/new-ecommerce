<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuoteAcceptedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    // =========================================================================
    //  Fires when a customer accepts a quotation (ACCEPTED transition).
    //  Sent to the admin team.
    //
    //  Receives both the quotation AND the newly created sales order so
    //  the email can link directly to the sales order for quick action.
    // =========================================================================

    public function __construct(
        public readonly Quote $quote,
        public readonly Order $order,
    ) {}

    public function via(): array
    {
        return ['mail', 'database'];
    }

    public function toMail(): MailMessage
    {
        $customerName = $this->quote->user?->name ?? 'Customer';
        $customerEmail = $this->quote->user?->email ?? '';
        $total = format_currency($this->order->total);
        $orderUrl = route('admin.orders.show', $this->order);

        return (new MailMessage)
            ->subject("Quote Accepted — {$this->order->reference} Created")
            ->greeting('A customer has accepted their quotation')
            ->line("{$customerName} ({$customerEmail}) has accepted quotation **{$this->quote->reference}**.")
            ->line('A sales order has been automatically created.')
            ->line("**Sales order:** {$this->order->reference}")
            ->line("**Total:** {$total}")
            ->line('The customer has been routed to the payment page. You will receive another notification once payment is confirmed.')
            ->action('View Sales Order', $orderUrl)
            ->salutation('Sheffield Africa · Orders Team');
    }

    public function toArray(): array
    {
        return [
            'quote_id' => $this->quote->id,
            'order_id' => $this->order->id,
            'reference' => $this->quote->reference,
            'order_reference' => $this->order->reference,
            'title' => 'Quote Accepted',
            'message' => "Quote {$this->quote->reference} accepted. Order {$this->order->reference} created.",
            'url' => route('admin.orders.show', $this->order),
        ];
    }
}
