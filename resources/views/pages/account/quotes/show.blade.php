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
        $this->quote = $quote->load('items.product');
    }

    public function approve(): void
    {
        if ($this->quote->status !== QuoteStatus::AWAITING_APPROVAL) {
            return;
        }

        $this->quote->update(['status' => QuoteStatus::APPROVED]);

        Notification::send(StaffRecipients::for('quotes.manage'), new QuoteDecisionReceived($this->quote));

        Flux::toast(heading: 'Quote approved', text: 'Our team will be in touch to arrange next steps.', variant: 'success');
    }

    public function decline(): void
    {
        if ($this->quote->status !== QuoteStatus::AWAITING_APPROVAL) {
            return;
        }

        $this->quote->update(['status' => QuoteStatus::DECLINED]);

        Notification::send(StaffRecipients::for('quotes.manage'), new QuoteDecisionReceived($this->quote));

        Flux::toast(heading: 'Quote declined', text: 'You can request a new quote any time.', variant: 'warning');
    }
}; ?>

<div class="page-fade space-y-6">

    {{-- Back --}}
    <flux:button variant="ghost" size="sm" icon="arrow-left" :href="route('account.quotes.index')" wire:navigate inset="left">
        Back to quotes
    </flux:button>

    {{-- Header --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ $quote->quote_number }}</flux:heading>
            <flux:text class="mt-1">{{ $quote->title }}</flux:text>
            <flux:text size="sm" class="mt-1 text-ink-3">Requested {{ $quote->created_at->format('d F Y') }}</flux:text>
        </div>
        <flux:badge :color="$quote->status->badgeColor()">{{ $quote->status->label() }}</flux:badge>
    </div>

    <div class="flex flex-col gap-6 lg:flex-row lg:items-start">

        {{-- Items + notes --}}
        <div class="min-w-0 flex-1 space-y-4">

            <flux:card class="p-0">
                <div class="border-b border-zinc-200 px-5 py-4">
                    <flux:heading size="sm" class="uppercase tracking-widest text-ink-3">Items</flux:heading>
                </div>
                <flux:table container:class="px-5">
                    <flux:table.columns>
                        <flux:table.column>Product</flux:table.column>
                        <flux:table.column align="end">Qty</flux:table.column>
                        @if ($quote->isPriced())
                            <flux:table.column align="end">Unit</flux:table.column>
                            <flux:table.column align="end">Line total</flux:table.column>
                        @endif
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse ($quote->items as $item)
                            <flux:table.row wire:key="item-{{ $item->id }}">
                                <flux:table.cell variant="strong">
                                    {{ $item->product_name }}
                                    @if ($item->product_sku)
                                        <span class="block font-mono text-xs font-normal text-ink-4">{{ $item->product_sku }}</span>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell align="end" class="tabular-nums text-ink-3">{{ $item->quantity }}</flux:table.cell>
                                @if ($quote->isPriced())
                                    <flux:table.cell align="end" class="tabular-nums text-ink-3">{!! money($item->unit_price_cents) !!}</flux:table.cell>
                                    <flux:table.cell align="end" class="font-medium tabular-nums">{!! money($item->line_total_cents) !!}</flux:table.cell>
                                @endif
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="{{ $quote->isPriced() ? 4 : 2 }}" class="py-8 text-center text-ink-4">
                                    No items on this quote.
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </flux:card>

            @if ($quote->notes)
                <flux:card>
                    <flux:heading size="sm" class="uppercase tracking-widest text-ink-3">Notes</flux:heading>
                    <flux:text class="mt-3 whitespace-pre-line leading-relaxed">{{ $quote->notes }}</flux:text>
                </flux:card>
            @endif
        </div>

        {{-- Summary sidebar --}}
        <aside class="w-full shrink-0 lg:sticky lg:top-44 lg:w-80">
            <flux:card class="p-0">
                <div class="border-b border-zinc-200 px-5 py-4">
                    <flux:heading size="sm" class="uppercase tracking-widest text-ink-3">Summary</flux:heading>
                </div>

                <div class="space-y-3 px-5 py-4">
                    @if ($quote->expires_at)
                        <div class="flex justify-between text-sm">
                            <flux:text size="sm">Valid until</flux:text>
                            <flux:text size="sm" class="{{ $quote->expires_at->isPast() ? 'font-medium text-red-500' : 'text-ink-2' }}">
                                {{ $quote->expires_at->format('d M Y') }}
                            </flux:text>
                        </div>
                    @endif

                    <div class="flex items-baseline justify-between">
                        <flux:text class="text-[12px] font-bold uppercase tracking-wide">Total</flux:text>
                        @if ($quote->isPriced())
                            <span class="font-serif text-2xl text-brand-500 tabular-nums">{!! money($quote->total_cents) !!}</span>
                        @else
                            <flux:text size="sm" class="font-medium text-ink-3">Awaiting quote</flux:text>
                        @endif
                    </div>
                </div>

                @if (! $quote->isPriced())
                    <div class="border-t border-zinc-200 px-5 py-4">
                        <flux:text size="sm" class="text-ink-3">
                            Our team is preparing your formal quotation. We'll confirm pricing, VAT, delivery and lead times shortly.
                        </flux:text>
                    </div>
                @endif

                @if ($quote->status === QuoteStatus::AWAITING_APPROVAL)
                    <div class="space-y-2 border-t border-zinc-200 px-5 py-4">
                        <flux:button variant="customer-primary" size="customer" class="w-full!"
                                     wire:click="approve" wire:confirm="Approve this quote?">
                            Approve quote
                        </flux:button>
                        <flux:button variant="ghost" size="customer" class="w-full!"
                                     wire:click="decline" wire:confirm="Decline this quote?">
                            Decline
                        </flux:button>
                    </div>
                @endif
            </flux:card>
        </aside>

    </div>

</div>
