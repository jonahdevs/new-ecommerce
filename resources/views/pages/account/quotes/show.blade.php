<?php

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Notifications\Quotes\QuoteDecisionReceived;
use App\Support\StaffRecipients;
use Artesaos\SEOTools\Facades\SEOMeta;
use Flux\Flux;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::account')] #[Title('Quote')] class extends Component
{
    #[Locked]
    public Quote $quote;

    public function mount(Quote $quote): void
    {
        abort_unless($quote->user_id === auth()->id(), 403);
        SEOMeta::setRobots('noindex,follow');
        $this->quote = $quote->load('items');
    }

    public function approve(): void
    {
        abort_unless($this->quote->status === QuoteStatus::AWAITING_APPROVAL, 403);

        $this->quote->update(['status' => QuoteStatus::APPROVED]);
        $this->quote->refresh();

        Notification::send(StaffRecipients::for('quotes.manage'), new QuoteDecisionReceived($this->quote));

        Flux::toast(heading: 'Quote accepted', text: 'Our team will be in touch to confirm your order.', variant: 'success');
    }

    public function decline(): void
    {
        abort_unless($this->quote->status === QuoteStatus::AWAITING_APPROVAL, 403);

        $this->quote->update(['status' => QuoteStatus::DECLINED]);
        $this->quote->refresh();

        Notification::send(StaffRecipients::for('quotes.manage'), new QuoteDecisionReceived($this->quote));

        Flux::toast(heading: 'Quote declined', text: 'You can request a new quote any time.', variant: 'warning');
    }
}; ?>

<style>
    @media print {
        body * { visibility: hidden; }
        #quote-print-area, #quote-print-area * { visibility: visible; }
        #quote-print-area { position: absolute; left: 0; top: 0; width: 100%; }
    }
</style>

<div class="page-fade">

    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('home')" wire:navigate>Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('account.quotes.index')" wire:navigate>Quotes</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $quote->quote_number }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    {{-- Page header --}}
    <div class="print:hidden mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-semibold tracking-tight text-ink font-mono">{{ $quote->quote_number }}</h1>
                <flux:badge :color="$quote->status->badgeColor()" size="sm">{{ $quote->status->label() }}</flux:badge>
            </div>
            <p class="mt-1 text-sm text-ink-3">
                Submitted {{ $quote->created_at->format('d F Y') }}
                @if ($quote->expires_at)
                    &middot;
                    <span @class(['text-red-500' => $quote->expires_at->isPast()])>
                        {{ $quote->expires_at->isPast() ? 'Expired' : 'Valid until' }}
                        {{ $quote->expires_at->format('d F Y') }}
                    </span>
                @endif
            </p>
        </div>

        @if ($quote->isPriced())
            <div class="flex items-center gap-2">
                @if ($quote->document_path)
                    <flux:button variant="customer-outline" size="customer" icon="arrow-down-tray"
                        :href="route('account.quotes.download', $quote)">
                        Download PDF
                    </flux:button>
                @endif
                <flux:button variant="customer-outline" size="customer" icon="printer"
                    onclick="window.print()">
                    Print
                </flux:button>
            </div>
        @endif
    </div>

    {{-- Status timeline --}}
    <div class="print:hidden mb-6">
        @php
            $steps = [
                ['label' => 'Request submitted', 'done' => true],
                ['label' => 'Under review',       'done' => $quote->status !== QuoteStatus::DRAFT],
                ['label' => 'Quotation ready',    'done' => in_array($quote->status, [QuoteStatus::AWAITING_APPROVAL, QuoteStatus::SENT, QuoteStatus::APPROVED, QuoteStatus::DECLINED])],
                ['label' => $quote->status === QuoteStatus::DECLINED ? 'Declined' : 'Accepted', 'done' => in_array($quote->status, [QuoteStatus::APPROVED, QuoteStatus::DECLINED])],
            ];
        @endphp
        <div class="flex items-center gap-0">
            @foreach ($steps as $i => $step)
                <div class="flex items-center {{ $loop->last ? '' : 'flex-1' }}">
                    <div class="flex flex-col items-center">
                        <div @class([
                            'flex size-7 items-center justify-center rounded-full text-[11px] font-bold',
                            'bg-brand-500 text-white' => $step['done'],
                            'bg-zinc-100 text-zinc-400 border border-zinc-200' => ! $step['done'],
                        ])>
                            @if ($step['done'])
                                <flux:icon.check variant="micro" class="size-3.5" />
                            @else
                                {{ $i + 1 }}
                            @endif
                        </div>
                        <span @class([
                            'mt-1.5 text-[11px] whitespace-nowrap',
                            'font-semibold text-brand-500' => $step['done'],
                            'text-zinc-400' => ! $step['done'],
                        ])>{{ $step['label'] }}</span>
                    </div>
                    @if (! $loop->last)
                        <div @class([
                            'mb-5 h-px flex-1 mx-2',
                            'bg-brand-500' => $step['done'],
                            'bg-zinc-200' => ! $step['done'],
                        ])></div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Approve / Decline action bar (only when awaiting customer decision) --}}
    @if ($quote->status === QuoteStatus::AWAITING_APPROVAL)
        <div class="print:hidden mb-6 rounded-md border border-brand-200 bg-brand-50 px-5 py-4 flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-[14px] font-semibold text-brand-800">Your quotation is ready for review</p>
                <p class="text-[13px] text-brand-600 mt-0.5">Review the document below and approve or decline.</p>
            </div>
            <div class="flex items-center gap-3">
                <flux:button variant="ghost" size="sm" wire:click="decline">Decline</flux:button>
                <flux:button variant="customer-primary" size="customer" icon="check" wire:click="approve">
                    Approve quote
                </flux:button>
            </div>
        </div>
    @endif

    {{-- Quote document (priced) or holding state (draft) --}}
    @if ($quote->isPriced())
        <div id="quote-print-area">
            <x-quote-document :quote="$quote" :show-actions="false" />
        </div>
    @else
        {{-- Not yet priced — show items submitted and a holding notice --}}
        <flux:card class="overflow-hidden p-0">
            <div class="border-b border-zinc-100 px-6 py-4">
                <div class="flex items-center justify-between">
                    <flux:heading size="sm">Items requested</flux:heading>
                    <flux:badge color="yellow">Awaiting quote</flux:badge>
                </div>
                <flux:text size="sm" class="mt-1 text-zinc-400">Our team is preparing your formal quotation. Prices will appear once ready.</flux:text>
            </div>
            <div class="divide-y divide-zinc-100">
                @foreach ($quote->items as $item)
                    <div class="flex items-center justify-between px-6 py-3">
                        <div>
                            <p class="text-[13.5px] font-medium text-ink">{{ $item->product_name }}</p>
                            @if ($item->product_sku)
                                <p class="text-[11.5px] font-mono text-ink-4">{{ $item->product_sku }}</p>
                            @endif
                        </div>
                        <span class="text-[13px] text-ink-3">× {{ $item->quantity }}</span>
                    </div>
                @endforeach
            </div>
            <div class="border-t border-zinc-100 bg-zinc-50 px-6 py-4 text-center text-[12.5px] text-ink-3">
                You'll receive an email once your quotation is ready. Typical response within 1 business day.
            </div>
        </flux:card>
    @endif

</div>
