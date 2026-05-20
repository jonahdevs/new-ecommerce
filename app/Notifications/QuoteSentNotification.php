<?php

namespace App\Notifications;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class QuoteSentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    // =========================================================================
    //  Fires when admin prices and sends a quotation (SENT transition).
    //  Sent to the customer — this is their prompt to log in and respond.
    //
    //  This email IS the customer's entry point to the portal accept/reject
    //  page, so the action URL must link directly to their quotation.
    // =========================================================================

    public function __construct(public readonly Quote $quote) {}

    public function via(): array
    {
        return ['mail', 'database'];
    }

    public function toMail(): MailMessage
    {
        $quote = $this->quote->loadMissing(['items', 'user']);

        $mail = (new MailMessage)
            ->subject("Your Quotation is Ready — {$quote->reference}")
            ->view('mails.quotes.sent', [
                'quote' => $quote,
                'customerName' => $quote->customerName(),
                'portalUrl' => route('customer.quotations.show', $quote),
            ]);

        if ($quote->document_path && Storage::disk('local')->exists($quote->document_path)) {
            $mail->attach(
                Storage::disk('local')->path($quote->document_path),
                ['as' => "{$quote->reference}.pdf", 'mime' => 'application/pdf'],
            );
        }

        return $mail;
    }

    public function toArray(): array
    {
        return [
            'quote_id' => $this->quote->id,
            'reference' => $this->quote->reference,
            'title' => 'Quotation Ready',
            'message' => "Your quotation {$this->quote->reference} is ready for review. Total: ".format_currency($this->quote->total),
            'url' => route('customer.quotations.show', $this->quote),
        ];
    }
}
