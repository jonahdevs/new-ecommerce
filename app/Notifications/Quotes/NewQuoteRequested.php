<?php

namespace App\Notifications\Quotes;

use App\Models\Quote;
use App\Notifications\Concerns\RespectsStaffPreferences;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewQuoteRequested extends Notification implements ShouldQueue
{
    use Queueable;
    use RespectsStaffPreferences;

    public function __construct(public Quote $quote) {}

    protected function staffGlobalKey(): ?string
    {
        return 'staff_new_quote';
    }

    protected function staffPreferenceKey(): ?string
    {
        return 'new_quote';
    }

    protected function supportsInApp(): bool
    {
        return true;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $quote = $this->quote->load('items');
        $who = $quote->contact_name ?: ($quote->user?->name ?? 'A customer');

        return (new MailMessage)
            ->subject('New quote request — ' . $quote->quote_number)
            ->greeting('New quote request')
            ->line($who . ' submitted quote request ' . $quote->quote_number . '.')
            ->line($quote->contact_email . ($quote->contact_phone ? ' · ' . $quote->contact_phone : ''))
            ->when($quote->contact_company, fn($m) => $m->line('Company: ' . $quote->contact_company))
            ->line($quote->items->count() . ' item(s) to price.')
            ->when($quote->delivery_required, fn($m) => $m->line('Delivery required to: ' . $quote->delivery_address))
            ->when($quote->notes, fn($m) => $m->line('Notes: ' . $quote->notes))
            ->action('Prepare quote', route('admin.quotes.show', $quote));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'quote_request',
            'quote_id' => $this->quote->id,
            'quote_number' => $this->quote->quote_number,
            'contact_name' => $this->quote->contact_name,
            'contact_company' => $this->quote->contact_company,
            'url' => route('admin.quotes.show', $this->quote),
        ];
    }
}
