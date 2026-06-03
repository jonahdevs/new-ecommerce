<?php

namespace App\Notifications\Quotes;

use App\Models\Quote;
use App\Notifications\Concerns\RespectsPreferences;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Acknowledgement to the customer (or guest) that their quote request was
 * received and is being prepared. Carries no pricing.
 */
class QuoteRequestReceived extends Notification implements ShouldQueue
{
    use Queueable;
    use RespectsPreferences;

    public function __construct(public Quote $quote) {}

    protected function preferenceKey(): ?array
    {
        return ['quotes', 'received'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('We\'ve received your quote request — '.$this->quote->quote_number)
            ->greeting('Thanks for your request')
            ->line('We\'ve received your request for quotation ('.$this->quote->quote_number.') and our team is preparing your formal quotation.')
            ->line('We\'ll be in touch shortly with pricing, delivery and lead times.');
    }
}
