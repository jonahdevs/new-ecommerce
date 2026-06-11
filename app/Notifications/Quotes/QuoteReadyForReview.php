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
        $quote = $this->quote;

        if ($quote->user_id) {
            $portalUrl = route('account.quotes.show', $quote);
        } else {
            $expiry = $quote->expires_at ?? now()->addDays(60);
            $portalUrl = URL::temporarySignedRoute('quotes.guest-review', $expiry, ['quote' => $quote]);
        }

        $mail = (new MailMessage)
            ->subject('Your quotation is ready — '.$quote->quote_number)
            ->view('mails.quotes.sent', [
                'quote' => $quote,
                'customerName' => $quote->user?->name ?? $quote->contact_name ?? 'there',
                'portalUrl' => $portalUrl,
            ]);

        $pdfBytes = app(QuotePdfService::class)->bytes($quote);

        if ($pdfBytes) {
            $mail->attachData($pdfBytes, $quote->quote_number.'.pdf', ['mime' => 'application/pdf']);
        }

        return $mail;
    }
}
