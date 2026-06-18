<?php

namespace App\Notifications\Quotes;

use App\Models\Quote;
use App\Notifications\Concerns\RespectsPreferences;
use App\Notifications\Messages\WhatsAppMessage;
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
        $quote = $this->quote;

        return (new MailMessage)
            ->subject('We\'ve received your quote request — '.$quote->quote_number)
            ->view('mails.quotes.received', [
                'quote' => $quote,
                'customerName' => $quote->user?->name ?? $quote->contact_name ?? 'there',
                'quotationsUrl' => route('account.quotes.index'),
            ]);
    }

    public function toWhatsapp(object $notifiable): WhatsAppMessage
    {
        $quote = $this->quote;

        return WhatsAppMessage::template('quote_received')
            ->body(
                $quote->user?->name ?? $quote->contact_name ?? 'there',
                $quote->quote_number,
            );
    }
}
