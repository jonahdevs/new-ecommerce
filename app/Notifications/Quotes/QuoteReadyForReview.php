<?php

namespace App\Notifications\Quotes;

use App\Models\Quote;
use App\Notifications\Concerns\RespectsPreferences;
use App\Services\QuotePdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

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
        return ['quotes', 'updates'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Your quotation is ready — '.$this->quote->quote_number)
            ->greeting('Your quotation is ready')
            ->line('We\'ve prepared quotation '.$this->quote->quote_number.'.')
            ->line('Total: '.money($this->quote->total_cents));

        $pdfBytes = app(QuotePdfService::class)->bytes($this->quote);

        if ($pdfBytes) {
            $mail->attachData($pdfBytes, $this->quote->quote_number.'.pdf', ['mime' => 'application/pdf']);
        }

        $validUntil = $this->quote->expires_at?->format('d M Y') ?? 'further notice';

        if ($this->quote->user_id) {
            return $mail
                ->action('Review and approve', route('account.quotes.show', $this->quote))
                ->line('The quote is valid until '.$validUntil.'.');
        }

        $expiry = $this->quote->expires_at ?? now()->addDays(60);
        $reviewUrl = URL::temporarySignedRoute('quotes.guest-review', $expiry, ['quote' => $this->quote]);

        return $mail
            ->action('Review and approve online', $reviewUrl)
            ->line('The quote is valid until '.$validUntil.'.')
            ->line('Approving will ask you to create a free account so you can pay and track your order online.');
    }
}
