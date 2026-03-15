<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuoteAcceptedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    // =========================================================================
    //  Fires when a customer accepts a quotation (QUOTE_ACCEPTED transition).
    //  Sent to the admin team.
    //
    //  Receives both the quotation AND the newly created sales order so
    //  the email can link directly to the sales order for quick action.
    // =========================================================================

    public function __construct(
        public readonly Order $quotation,
        public readonly Order $salesOrder,
    ) {
    }

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(): MailMessage
    {
        $customerName = $this->quotation->user?->name ?? 'Customer';
        $customerEmail = $this->quotation->user?->email ?? '';
        $total = format_currency($this->salesOrder->total);
        $orderUrl = route('admin.orders.show', $this->salesOrder);

        return (new MailMessage)
            ->subject("Quote Accepted — {$this->salesOrder->reference} Created")
            ->greeting('A customer has accepted their quotation')
            ->line("{$customerName} ({$customerEmail}) has accepted quotation **{$this->quotation->reference}**.")
            ->line("A sales order has been automatically created.")
            ->line("**Sales order:** {$this->salesOrder->reference}")
            ->line("**Total:** {$total}")
            ->line('The customer has been routed to the payment page. You will receive another notification once payment is confirmed.')
            ->action('View Sales Order', $orderUrl)
            ->salutation('Sheffield Africa · Orders Team');
    }
}
