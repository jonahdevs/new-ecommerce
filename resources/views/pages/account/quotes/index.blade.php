<?php

use App\Enums\QuoteStatus;
use Artesaos\SEOTools\Facades\SEOMeta;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::account')] #[Title('Quotes')] class extends Component {
    use WithPagination;

    #[Url]
    public string $status = 'active';

    public function mount(): void
    {
        SEOMeta::setRobots('noindex,follow');
    }

    #[Computed]
    public function quotes()
    {
        return auth()->user()->quotes()
            ->when($this->status === 'active', fn ($q) => $q->whereIn('status', ['draft', 'sent', 'awaiting_approval', 'approved']))
            ->when($this->status === 'rejected', fn ($q) => $q->whereIn('status', ['declined', 'expired']))
            ->latest()
            ->paginate(10);
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function approve(int $id): void
    {
        $quote = auth()->user()->quotes()->findOrFail($id);
        $quote->update(['status' => QuoteStatus::APPROVED]);

        \Illuminate\Support\Facades\Notification::send(\App\Support\StaffRecipients::for('quotes.manage'), new \App\Notifications\Quotes\QuoteDecisionReceived($quote));

        unset($this->quotes);
        Flux::toast(heading: 'Quote approved', text: 'Your quote has been approved and our team will be in touch.', variant: 'success');
    }
}; ?>

<div class="page-fade space-y-6">

    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('home')" wire:navigate>Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Quotes</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

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
            <flux:button variant="customer-primary" size="customer" :href="route('quote.request')" wire:navigate
                class="mt-5">
                Request a quote
            </flux:button>
        </flux:card>
    @else
        <flux:card class="p-0 overflow-hidden">
            <div class="flex flex-wrap gap-2 border-b border-zinc-200 px-6 py-3">
                @foreach (['active' => 'Active', 'rejected' => 'Rejected / Expired'] as $value => $label)
                    <button type="button" wire:click="$set('status', '{{ $value }}')"
                            class="cursor-pointer rounded-md border px-3.5 py-1 text-[12px] font-semibold transition
                                {{ $status === $value
                                    ? 'border-brand-blue-500 bg-brand-blue-500 text-white'
                                    : 'border-zinc-200 bg-white text-ink-2 hover:border-zinc-300' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
            <flux:table
                container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
                <flux:table.columns class="bg-zinc-50 dark:bg-zinc-800/60">
                    <flux:table.column>Quote</flux:table.column>
                    <flux:table.column class="hidden sm:table-cell">Date</flux:table.column>
                    <flux:table.column class="hidden md:table-cell">Expires</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column class="hidden md:table-cell" align="end">Total</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->quotes as $quote)
                        <flux:table.row wire:key="quote-{{ $quote->id }}"
                            :href="route('account.quotes.show', $quote)" wire:navigate
                            class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                            <flux:table.cell>
                                <flux:text class="font-semibold text-ink">{{ $quote->quote_number }}</flux:text>
                                <flux:text size="sm" class="mt-0.5 text-ink-4 line-clamp-1">{{ $quote->title }}
                                </flux:text>
                            </flux:table.cell>
                            <flux:table.cell class="hidden sm:table-cell">
                                <flux:text size="sm">{{ $quote->created_at->format('d M Y') }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell class="hidden md:table-cell">
                                @if ($quote->expires_at)
                                    <flux:text size="sm"
                                        class="{{ $quote->expires_at->isPast() ? 'text-red-500' : 'text-ink-3' }}">
                                        {{ $quote->expires_at->isPast() ? 'Expired' : $quote->expires_at->diffForHumans() }}
                                    </flux:text>
                                @else
                                    <flux:text size="sm" class="text-ink-4">—</flux:text>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :color="$quote->status->badgeColor()" size="sm" inset="top bottom">
                                    {{ $quote->status->label() }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="hidden md:table-cell" align="end">
                                @if ($quote->isPriced())
                                    <flux:text size="sm" class="font-semibold tabular-nums">
                                        {!! money($quote->total_cents) !!}
                                    </flux:text>
                                @else
                                    <flux:text size="sm" class="text-zinc-400 italic">Awaiting quote</flux:text>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                @if ($quote->status === QuoteStatus::AWAITING_APPROVAL)
                                    <flux:button size="sm" variant="customer-primary"
                                        :href="route('account.quotes.show', $quote)" wire:navigate
                                        onclick="event.stopPropagation()">
                                        Review &amp; approve
                                    </flux:button>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>

            @if ($this->quotes->hasPages())
                <div class="border-t border-zinc-200 px-6 pb-3">
                    <flux:pagination :paginator="$this->quotes" />
                </div>
            @endif
        </flux:card>
    @endif

</div>
