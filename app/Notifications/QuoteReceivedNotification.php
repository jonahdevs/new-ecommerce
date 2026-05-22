<?php

namespace App\Notifications;

use App\Models\Quote;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuoteReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    // =========================================================================
    //  Fires immediately when a customer submits a quote request (PENDING).
    //  Sent to the customer — confirms we received their request and sets
    //  expectations on next steps.
    //
    //  Respects the per-user 'quote_received.email' preference when the
    //  notifiable is an authenticated User. Anonymous (guest) notifiables
    //  always receive the email while the system setting is on.
    //
    //  Triggered from:
    //    QuotationService::notifyCustomerReceived($quote)
    // =========================================================================

    public function __construct(public readonly Quote $quote) {}

    public function via(object $notifiable): array
    {
        if ($notifiable instanceof User) {
            $prefs = $notifiable->notification_preferences ?? [];

            return ($prefs['quote_received']['email'] ?? true) ? ['mail'] : [];
        }

        return ['mail'];
    }

    public function toMail(): MailMessage
    {
        $quote = $this->quote->loadMissing(['items']);

        return (new MailMessage)
            ->subject("Quote Request Received — {$quote->reference}")
            ->view('mails.quotes.received', [
                'quote' => $quote,
                'customerName' => $quote->customerName(),
                'quotationsUrl' => route('customer.quotations.index'),
            ]);
    }

    public function toArray(): array
    {
        return [
            'quote_id' => $this->quote->id,
            'reference' => $this->quote->reference,
            'title' => 'Quote Request Received',
            'message' => "Your quote request {$this->quote->reference} is in — our team will review it and send you a priced quotation within 1 business day.",
            'url' => route('customer.quotations.index'),
        ];
    }
}
