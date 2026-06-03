<?php

namespace App\Notifications\Quotes;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Staff alert that a new quote request has come in and needs pricing.
 */
class NewQuoteRequested extends Notification implements ShouldQueue
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
        $who = $this->quote->contact_name ?: ($this->quote->user?->name ?? 'A customer');

        return (new MailMessage)
            ->subject('New quote request '.$this->quote->quote_number)
            ->greeting('New quote request')
            ->line($who.' submitted quote request '.$this->quote->quote_number.'.')
            ->line($this->quote->items()->count().' item(s) to price.')
            ->action('Prepare quote', route('admin.quotes.show', $this->quote));
    }
}
