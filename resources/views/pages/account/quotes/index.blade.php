<?php

use App\Enums\QuoteStatus;
use Artesaos\SEOTools\Facades\SEOMeta;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::account')] #[Title('Quotes')] class extends Component
{
    use WithPagination;

    public function mount(): void
    {
        SEOMeta::setRobots('noindex,follow');
    }

    #[Computed]
    public function quotes()
    {
        return auth()->user()->quotes()
            ->latest()
            ->paginate(10);
    }

    public function approve(int $id): void
    {
        $quote = auth()->user()->quotes()->findOrFail($id);
        $quote->update(['status' => QuoteStatus::APPROVED]);

        \Illuminate\Support\Facades\Notification::send(
            \App\Support\StaffRecipients::for('quotes.manage'),
            new \App\Notifications\Quotes\QuoteDecisionReceived($quote),
        );

        unset($this->quotes);
        Flux::toast(heading: 'Quote approved', text: 'Your quote has been approved and our team will be in touch.', variant: 'success');
    }
}; ?>

<div class="page-fade space-y-6">

    {{-- Header --}}
    <div>
        <flux:heading size="xl">Quotes</flux:heading>
        <flux:text class="mt-1">Pending and historical quotations. Approve a quote to convert it to an order.</flux:text>
    </div>

    @if ($this->quotes->isEmpty())
        <flux:card class="py-14 text-center">
            <flux:icon.document-text variant="outline" class="mx-auto size-9 text-ink-4" />
            <flux:heading size="sm" class="mt-4">No quotes yet</flux:heading>
            <flux:text class="mt-1">Request a formal quote for your next project.</flux:text>
            <flux:button variant="customer-primary" size="customer" :href="route('quote.request')" wire:navigate class="mt-5">
                Request a quote
            </flux:button>
        </flux:card>
    @else
        <div class="space-y-3">
            @foreach ($this->quotes as $quote)
                <flux:card wire:key="quote-{{ $quote->id }}">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0">
                            <flux:text size="sm" class="font-bold uppercase tracking-[0.08em] text-ink-3">
                                {{ $quote->quote_number }}
                            </flux:text>
                            <flux:heading size="sm" class="mt-1 leading-snug">{{ $quote->title }}</flux:heading>
                            <div class="mt-2 flex flex-wrap items-center gap-3">
                                <flux:text size="sm" class="text-ink-3">{{ $quote->created_at->format('d M Y') }}</flux:text>
                                <flux:badge :color="$quote->status->badgeColor()" size="sm" inset="top bottom">
                                    {{ $quote->status->label() }}
                                </flux:badge>
                                @if ($quote->expires_at)
                                    <flux:text size="sm"
                                               class="{{ $quote->expires_at->isPast() ? 'text-red-500' : 'text-ink-3' }}">
                                        {{ $quote->expires_at->isPast() ? 'Expired' : 'Expires ' . $quote->expires_at->diffForHumans() }}
                                    </flux:text>
                                @endif
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-4">
                            {{-- Only a staff-prepared quotation has a valid price; a fresh request shows none. --}}
                            @if ($quote->isPriced())
                                <span class="font-serif text-xl tabular-nums text-ink">{!! money($quote->total_cents) !!}</span>
                            @else
                                <span class="text-[13px] font-medium text-ink-3">Awaiting quote</span>
                            @endif
                            <div class="flex gap-2">
                                @if ($quote->status === QuoteStatus::AWAITING_APPROVAL)
                                    <flux:button variant="customer-primary" size="customer"
                                                 wire:click="approve({{ $quote->id }})"
                                                 wire:confirm="Approve this quote?">
                                        Approve
                                    </flux:button>
                                @endif
                                <flux:button variant="customer-outline" size="customer"
                                             :href="route('account.quotes.show', $quote)" wire:navigate>View</flux:button>
                            </div>
                        </div>
                    </div>
                </flux:card>
            @endforeach
        </div>

        <div>{{ $this->quotes->links() }}</div>
    @endif

</div>
