<?php

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Notifications\Quotes\QuoteDecisionReceived;
use App\Services\QuoteConversionService;
use App\Support\StaffRecipients;
use Artesaos\SEOTools\Facades\SEOMeta;
use Flux\Flux;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::storefront')] #[Title('Review Your Quote')] class extends Component
{
    #[Locked]
    public Quote $quote;

    public function mount(Quote $quote): void
    {
        abort_if($quote->user_id !== null, 403);
        SEOMeta::setRobots('noindex,nofollow');
        $this->quote = $quote->load('items');
    }

    public function approve(): void
    {
        abort_unless($this->quote->status === QuoteStatus::AWAITING_APPROVAL, 403);

        // Already authenticated — link the quote and go straight to payment.
        if (auth()->check()) {
            $this->quote->update([
                'user_id' => auth()->id(),
                'status' => QuoteStatus::APPROVED,
            ]);
            $this->quote->refresh();

            $order = app(QuoteConversionService::class)->convert($this->quote);

            Notification::send(StaffRecipients::for('quotes.manage'), new QuoteDecisionReceived($this->quote));

            $this->redirectRoute('payment.page', $order, navigate: true);

            return;
        }

        // Guest — store pending quote and prompt to create an account.
        session(['quote_approval_pending' => $this->quote->id]);

        $this->redirect('/register', navigate: true);
    }

    public function decline(): void
    {
        abort_unless($this->quote->status === QuoteStatus::AWAITING_APPROVAL, 403);

        $this->quote->update(['status' => QuoteStatus::DECLINED]);
        $this->quote->refresh();

        Notification::send(StaffRecipients::for('quotes.manage'), new QuoteDecisionReceived($this->quote));

        Flux::toast(heading: 'Quote declined', text: 'Contact us if you\'d like a new quote.', variant: 'warning');
    }
}; ?>

<div class="page-fade">
    <div class="shell pt-4 pb-20">

    {{-- Page header --}}
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="font-mono text-2xl font-semibold tracking-tight text-ink">{{ $quote->quote_number }}</h1>
                <flux:badge :color="$quote->status->badgeColor()" size="sm">{{ $quote->status->label() }}</flux:badge>
            </div>
            <p class="mt-1 text-sm text-ink-3">
                {{ $quote->title }}
                @if ($quote->expires_at)
                    &middot;
                    <span @class(['text-red-500' => $quote->expires_at->isPast()])>
                        {{ $quote->expires_at->isPast() ? 'Expired' : 'Valid until' }}
                        {{ $quote->expires_at->format('d F Y') }}
                    </span>
                @endif
            </p>
        </div>

        @if ($quote->isPriced() && $quote->document_path)
            <flux:button variant="customer-outline" size="customer" icon="arrow-down-tray"
                :href="route('account.quotes.download', $quote)">
                Download PDF
            </flux:button>
        @endif
    </div>

    {{-- Approve / Decline action bar --}}
    @if ($quote->status === QuoteStatus::AWAITING_APPROVAL)
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-md border border-brand-200 bg-brand-50 px-5 py-4">
            <div>
                <p class="text-[14px] font-semibold text-brand-800">Your quotation is ready for review</p>
                <p class="mt-0.5 text-[13px] text-brand-600">Review the details below. Approving will ask you to create an account so you can pay online and track your order.</p>
            </div>
            <div class="flex items-center gap-3">
                <flux:button variant="ghost" size="sm" wire:click="decline">Decline</flux:button>
                <flux:button variant="customer-primary" size="customer" icon="check" wire:click="approve">
                    Approve quote
                </flux:button>
            </div>
        </div>
    @endif

    {{-- Post-decision banners --}}
    @if ($quote->status === QuoteStatus::APPROVED)
        <div class="mb-6 rounded-md border border-emerald-200 bg-emerald-50 px-5 py-4">
            <p class="text-[14px] font-semibold text-emerald-800">Quote approved — thank you</p>
            <p class="mt-0.5 text-[13px] text-emerald-600">Our team will be in touch shortly to arrange payment and next steps.</p>
        </div>
    @elseif ($quote->status === QuoteStatus::DECLINED)
        <div class="mb-6 rounded-md border border-zinc-200 bg-zinc-50 px-5 py-4">
            <p class="text-[14px] font-semibold text-ink">Quote declined</p>
            <p class="mt-0.5 text-[13px] text-ink-3">Contact us if you'd like to discuss a revised quote.</p>
        </div>
    @elseif ($quote->status === QuoteStatus::EXPIRED || ($quote->expires_at && $quote->expires_at->isPast()))
        <div class="mb-6 rounded-md border border-red-200 bg-red-50 px-5 py-4">
            <p class="text-[14px] font-semibold text-red-800">This quote has expired</p>
            <p class="mt-0.5 text-[13px] text-red-600">Please contact us to request an updated quotation.</p>
        </div>
    @endif

    {{-- Quote content --}}
    @if ($quote->isPriced())
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start">

            {{-- Left column --}}
            <div class="min-w-0 flex-1 space-y-6">

                {{-- Line items --}}
                <flux:card class="overflow-hidden p-0">
                    <div class="border-b border-zinc-100 px-6 py-4">
                        <flux:heading size="sm" class="uppercase tracking-widest text-ink-3">Items</flux:heading>
                    </div>
                    <flux:table container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
                        <flux:table.columns class="bg-zinc-50">
                            <flux:table.column>Product</flux:table.column>
                            <flux:table.column class="w-32">SKU</flux:table.column>
                            <flux:table.column class="w-36" align="end">Unit price</flux:table.column>
                            <flux:table.column class="w-16" align="end">Qty</flux:table.column>
                            <flux:table.column class="w-36" align="end">Total</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($quote->items as $item)
                                <flux:table.row wire:key="item-{{ $item->id }}">
                                    <flux:table.cell>
                                        <span class="text-[13.5px] font-semibold leading-snug text-ink">{{ $item->product_name }}</span>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <span class="font-mono text-xs text-ink-4">{{ $item->product_sku ?: '—' }}</span>
                                    </flux:table.cell>
                                    <flux:table.cell align="end">
                                        <span class="tabular-nums text-sm text-ink-2">{!! money($item->unit_price_cents) !!}</span>
                                    </flux:table.cell>
                                    <flux:table.cell align="end">
                                        <span class="tabular-nums text-sm text-ink-3">{{ $item->quantity }}</span>
                                    </flux:table.cell>
                                    <flux:table.cell align="end">
                                        <span class="font-semibold tabular-nums text-ink">{!! money($item->line_total_cents) !!}</span>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </flux:card>

                @if ($quote->terms)
                    <flux:card>
                        <flux:heading size="sm" class="uppercase tracking-widest text-ink-3">Terms & conditions</flux:heading>
                        <p class="mt-3 whitespace-pre-line text-[13px] leading-relaxed text-ink-3">{{ $quote->terms }}</p>
                    </flux:card>
                @endif

            </div>

            {{-- Right sidebar --}}
            <aside class="w-full shrink-0 lg:sticky lg:top-44 lg:w-80">
                <flux:card class="p-0">
                    <div class="border-b border-zinc-100 px-5 py-4">
                        <flux:heading size="sm" class="uppercase tracking-widest text-ink-3">Summary</flux:heading>
                    </div>
                    <div class="space-y-3 px-5 py-4">
                        <div class="flex justify-between">
                            <flux:text size="sm">Subtotal</flux:text>
                            <flux:text size="sm" class="font-medium tabular-nums">{!! money($quote->subtotal_cents) !!}</flux:text>
                        </div>
                        @if ($quote->discount_cents > 0)
                            <div class="flex justify-between">
                                <flux:text size="sm">Discount</flux:text>
                                <flux:text size="sm" class="font-medium tabular-nums text-red-500">−{!! money($quote->discount_cents) !!}</flux:text>
                            </div>
                        @endif
                        @if ($quote->shipping_cents > 0)
                            <div class="flex justify-between">
                                <flux:text size="sm">Shipping</flux:text>
                                <flux:text size="sm" class="font-medium tabular-nums">{!! money($quote->shipping_cents) !!}</flux:text>
                            </div>
                        @endif
                        @if ($quote->vat_cents > 0)
                            <div class="flex justify-between">
                                <flux:text size="sm">VAT ({{ rtrim(rtrim(number_format($quote->vat_rate, 2), '0'), '.') }}%)</flux:text>
                                <flux:text size="sm" class="font-medium tabular-nums">{!! money($quote->vat_cents) !!}</flux:text>
                            </div>
                        @endif
                    </div>
                    <flux:separator />
                    <div class="flex items-baseline justify-between px-5 py-4">
                        <flux:text class="text-[12px] font-bold uppercase tracking-wide">Total</flux:text>
                        <span class="font-serif text-2xl text-brand-500 tabular-nums">{!! money($quote->total_cents) !!}</span>
                    </div>
                </flux:card>
            </aside>

        </div>

    @else

        {{-- Not yet priced --}}
        <flux:card class="overflow-hidden p-0">
            <div class="border-b border-zinc-100 px-6 py-4">
                <div class="flex items-center justify-between">
                    <flux:heading size="sm">Items requested</flux:heading>
                    <flux:badge color="yellow">Awaiting quote</flux:badge>
                </div>
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
        </flux:card>

    @endif

    </div>
</div>
