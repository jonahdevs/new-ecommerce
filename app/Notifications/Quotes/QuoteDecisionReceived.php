<?php

namespace App\Notifications\Quotes;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Notifications\Concerns\RespectsStaffPreferences;
use App\Notifications\Messages\WhatsAppMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuoteDecisionReceived extends Notification implements ShouldQueue
{
    use Queueable;
    use RespectsStaffPreferences;

    public function __construct(public Quote $quote) {}

    protected function staffGlobalKey(): ?string
    {
        return 'staff_quote_decision';
    }

    protected function staffPreferenceKey(): ?string
    {
        return 'quote_decision';
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

    public function toWhatsapp(object $notifiable): WhatsAppMessage
    {
        $approved = $this->quote->status === QuoteStatus::APPROVED;
        $who = $this->quote->contact_name ?: ($this->quote->user?->name ?? 'The customer');

        return WhatsAppMessage::template('staff_quote_decision')
            ->body(
                $who,
                $this->quote->quote_number,
                $approved ? 'approved' : 'declined',
            );
    }
}
