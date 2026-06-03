<?php

namespace App\Notifications\Quotes;

use App\Models\Quote;
use App\Notifications\Concerns\RespectsPreferences;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the customer (or guest) their formal quotation is ready and awaiting
 * their approval. Registered users get a link to review and approve online.
 */
class QuoteReadyForReview extends Notification implements ShouldQueue
{
    use Queueable;
    use RespectsPreferences;

    public function __construct(public Quote $quote) {}

    protected function preferenceKey(): ?array
    {
        return ['quotes', 'ready'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Your quotation is ready — '.$this->quote->quote_number)
            ->greeting('Your quotation is ready')
            ->line('We\'ve prepared quotation '.$this->quote->quote_number.' ('.$this->quote->title.').')
            ->line('Total: '.money($this->quote->total_cents));

        if ($this->quote->user_id) {
            return $mail
                ->action('Review and approve', route('account.quotes.show', $this->quote))
                ->line('The quote is valid until '.($this->quote->expires_at?->format('d M Y') ?? 'further notice').'.');
        }

        return $mail->line('Reply to this email or contact us to approve and proceed with your order.');
    }
}
