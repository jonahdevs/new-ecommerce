<?php

namespace App\Notifications;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuoteExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    // =========================================================================
    //  Fires X days before a quotation expires (configurable in settings).
    //  Sent to the customer as a reminder to accept or reject.
    //
    //  Only sent for quotes in SENT status that haven't been responded to.
    //
    //  Triggered from:
    //    QuotationService::sendExpiringReminders() (scheduled command)
    // =========================================================================

    public function __construct(public readonly Quote $quote) {}

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(): MailMessage
    {
        $customerName = $this->quote->user?->name ?? 'Customer';
        $total = format_currency($this->quote->total);
        $expiresAt = $this->quote->expires_at?->format('M d, Y') ?? 'N/A';
        $daysLeft = $this->quote->expires_at?->diffInDays(now()) ?? 0;
        $portalUrl = route('customer.quotations.show', $this->quote);

        $urgency = $daysLeft <= 1 ? 'expires tomorrow' : "expires in {$daysLeft} days";

        return (new MailMessage)
            ->subject("Reminder: Your Quotation {$urgency} — {$this->quote->reference}")
            ->greeting("Hello {$customerName},")
            ->line("This is a friendly reminder that your quotation from Sheffield Africa {$urgency}.")
            ->line("**Quotation reference:** {$this->quote->reference}")
            ->line("**Total:** {$total}")
            ->line("**Expires on:** {$expiresAt}")
            ->line('Please log in to your account to review and respond to this quotation before it expires.')
            ->action('View Quotation', $portalUrl)
            ->line('If you have any questions, please reply to this email or contact our sales team.')
            ->salutation('Sheffield Africa · Sales Team');
    }
}
