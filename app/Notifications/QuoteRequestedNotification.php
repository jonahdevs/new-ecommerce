<?php

namespace App\Notifications;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuoteRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    // =========================================================================
    //  Fires when a customer submits a quote request (PENDING status created).
    //  Sent to the admin team email — not to the customer.
    //
    //  The customer already lands on the quote-success confirmation page
    //  so no customer notification is needed at this stage.
    //
    //  Implements ShouldQueue so a slow SMTP server never delays the
    //  checkout redirect. The quote is already saved before this fires.
    //
    //  Triggered from:
    //    QuotationService::notifyRequested($quote)
    // =========================================================================

    public function __construct(public readonly Quote $quote) {}

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(): MailMessage
    {
        $customerName  = $this->quote->customerName();
        $customerEmail = $this->quote->customerEmail();
        $itemCount     = $this->quote->items()->count();
        $subtotal      = format_currency($this->quote->subtotal);
        $adminUrl      = route('admin.quotations.show', $this->quote);

        $county   = $this->quote->preferred_county ?? 'Not specified';
        $area     = $this->quote->preferred_area ?? '';
        $location = $area ? "{$county}, {$area}" : $county;

        $mail = (new MailMessage)
            ->subject("New Quote Request — {$this->quote->reference}")
            ->greeting('New quotation request received')
            ->line("{$customerName} ({$customerEmail}) has submitted a quotation request.")
            ->line("**Reference:** {$this->quote->reference}")
            ->line("**Items:** {$itemCount} item(s) · Subtotal {$subtotal}")
            ->line("**Preferred location:** {$location}");

        if ($this->quote->customer_notes) {
            $mail->line("**Customer notes:** {$this->quote->customer_notes}");
        }

        return $mail
            ->line('Please log in to the admin panel to review and send a priced quotation.')
            ->action('Review & Price Quotation', $adminUrl)
            ->salutation('Sheffield Africa · Orders Team');
    }
}
