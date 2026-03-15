<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuoteRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    // =========================================================================
    //  Fires when a customer rejects a quotation (QUOTE_REJECTED transition).
    //  Sent to the admin team.
    //
    //  No customer notification here — they've already confirmed their
    //  decision on the portal and see a confirmation message there.
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
        $customerEmail = $this->order->user?->email ?? '';
        $total = format_currency($this->order->total);
        $quotationUrl = route('admin.orders.quotations.show', $this->order);

        // Get rejection note from the most recent status history entry if any
        $rejectionNote = $this->order->statusHistories
            ->where('to_status', 'quote_rejected')
            ->last()
                ?->notes;

        return (new MailMessage)
            ->subject("Quote Rejected — {$this->order->reference}")
            ->greeting('A customer has rejected their quotation')
            ->line("{$customerName} ({$customerEmail}) has rejected quotation **{$this->order->reference}**.")
            ->line("**Quoted total:** {$total}")
            ->when(
                $rejectionNote,
                fn($mail) =>
                $mail->line("**Customer note:** {$rejectionNote}")
            )
            ->line('You may wish to follow up with the customer to understand their concerns or offer a revised quotation.')
            ->action('View Quotation', $quotationUrl)
            ->salutation('Sheffield Africa · Orders Team');
    }
}
