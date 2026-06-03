<?php

namespace App\Notifications\Quotes;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Staff alert that a customer has approved or declined a quotation, so the
 * team can act (convert to order, or follow up).
 */
class QuoteDecisionReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Quote $quote) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $approved = $this->quote->status === QuoteStatus::APPROVED;
        $who = $this->quote->contact_name ?: ($this->quote->user?->name ?? 'The customer');

        return (new MailMessage)
            ->subject('Quote '.$this->quote->quote_number.' '.($approved ? 'approved' : 'declined'))
            ->greeting('Quote '.($approved ? 'approved' : 'declined'))
            ->line($who.' has '.($approved ? 'approved' : 'declined').' quotation '.$this->quote->quote_number.'.')
            ->line($approved ? 'You can now convert it to an order.' : 'You may want to follow up with the customer.')
            ->action('Open quote', route('admin.quotes.show', $this->quote));
    }
}
