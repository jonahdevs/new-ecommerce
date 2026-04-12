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
        return ['mail', 'database'];
    }

    public function toMail(): MailMessage
    {
        $daysLeft = $this->quote->expires_at?->diffInDays(now()) ?? 0;
        $urgency = $daysLeft <= 1 ? 'expires tomorrow' : "expires in {$daysLeft} days";

        return (new MailMessage)
            ->subject("Reminder: Your Quotation {$urgency} — {$this->quote->reference}")
            ->view('mails.quotes.expiring', [
                'quote' => $this->quote->loadMissing('items'),
                'customerName' => $this->quote->user?->name ?? 'Customer',
                'daysLeft' => $daysLeft,
                'urgency' => $urgency,
                'portalUrl' => route('customer.quotations.show', $this->quote),
            ]);
    }

    public function toArray(): array
    {
        $daysLeft = $this->quote->expires_at?->diffInDays(now()) ?? 0;
        $urgency = $daysLeft <= 1 ? 'expires tomorrow' : "expires in {$daysLeft} days";

        return [
            'quote_id' => $this->quote->id,
            'reference' => $this->quote->reference,
            'title' => 'Quotation Expiring Soon',
            'message' => "Your quotation {$this->quote->reference} {$urgency}. Please respond before it expires.",
            'url' => route('customer.quotations.show', $this->quote),
        ];
    }
}
