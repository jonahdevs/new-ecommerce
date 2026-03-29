<?php

namespace App\Notifications;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuoteRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    // =========================================================================
    //  Fires when a customer rejects a quotation (REJECTED transition).
    //  Sent to the admin team.
    //
    //  No customer notification here — they've already confirmed their
    //  decision on the portal and see a confirmation message there.
    // =========================================================================

    public function __construct(public readonly Quote $quote)
    {
    }

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(): MailMessage
    {
        $customerName = $this->quote->user?->name ?? 'Customer';
        $customerEmail = $this->quote->user?->email ?? '';
        $total = format_currency($this->quote->total);
        $quotationUrl = route('admin.quotations.show', $this->quote);

        return (new MailMessage)
            ->subject("Quote Rejected — {$this->quote->reference}")
            ->greeting('A customer has rejected their quotation')
            ->line("{$customerName} ({$customerEmail}) has rejected quotation **{$this->quote->reference}**.")
            ->line("**Quoted total:** {$total}")
            ->when(
                $this->quote->rejection_reason,
                fn($mail) =>
                $mail->line("**Customer note:** {$this->quote->rejection_reason}")
            )
            ->line('You may wish to follow up with the customer to understand their concerns or offer a revised quotation.')
            ->action('View Quotation', $quotationUrl)
            ->salutation('Sheffield Africa · Orders Team');
    }
}
