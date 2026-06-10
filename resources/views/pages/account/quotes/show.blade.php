<?php

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Notifications\Quotes\QuoteDecisionReceived;
use App\Services\QuoteConversionService;
use App\Support\StaffRecipients;
use Artesaos\SEOTools\Facades\SEOMeta;
use Flux\Flux;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Computed;
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
        SEOMeta::setTitle('Quote '.$quote->quote_number);
        SEOMeta::setRobots('noindex,follow');
        $this->quote = $quote->load([
            'items.product.images',
            'statusHistories',
            'order',
        ]);
    }

    #[Computed]
    public function isAwaitingApproval(): bool
    {
        return $this->quote->status === QuoteStatus::AWAITING_APPROVAL;
    }

    #[Computed]
    public function isPaid(): bool
    {
        return $this->quote->order?->isPaid() ?? false;
    }

    public function approve(): void
    {
        abort_unless($this->quote->status === QuoteStatus::AWAITING_APPROVAL, 403);

        $this->quote->update(['status' => QuoteStatus::APPROVED]);

        $order = app(QuoteConversionService::class)->convert($this->quote);

        $this->quote->refresh();

        Notification::send(StaffRecipients::for('quotes.manage'), new QuoteDecisionReceived($this->quote));

        $this->redirectRoute('payment.page', $order, navigate: true);
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

<div class="page-fade space-y-5">

    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('home')" wire:navigate>Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('account.quotes.index')" wire:navigate>Quotes</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $quote->quote_number }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white">

        {{-- ── Header bar ── --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 bg-white px-5 py-4">
            <h1 class="font-serif text-lg font-black tracking-tight text-ink">
                QUOTATION <em class="not-italic text-brand-500">{{ $quote->quote_number }}</em>
            </h1>
            <flux:badge :color="$quote->status->badgeColor()" size="sm">{{ $quote->status->label() }}</flux:badge>
        </div>

        <div class="flex flex-col gap-6 p-5">

            {{-- ── Quick summary ── --}}
            <div class="space-y-1.5 border-b border-zinc-100 pb-6">
                <p class="text-[13px] text-ink-2">
                    <span class="font-bold text-ink">{{ $quote->items->count() }}</span>
                    {{ Str::plural('item', $quote->items->count()) }}
                </p>
                <p class="text-[13px] text-ink-2">
                    Submitted on <span class="font-bold text-ink">{{ $quote->created_at->format('M j, Y') }}</span>
                </p>
                @if ($quote->isPriced())
                    <p class="text-[13px] text-ink-2">
                        Total: <span class="font-bold text-ink">{!! money($quote->total_cents) !!}</span>
                    </p>
                    @if ($quote->expires_at)
                        <p class="text-[13px] {{ $quote->expires_at->isPast() ? 'text-red-500' : 'text-ink-3' }}">
                            {{ $quote->expires_at->isPast() ? 'Expired' : 'Valid until' }}
                            <span class="font-bold">{{ $quote->expires_at->format('M j, Y') }}</span>
                        </p>
                    @endif
                @else
                    <p class="text-[13px] font-medium text-amber-600">Pricing pending — our team is preparing your quotation.</p>
                @endif
            </div>

            {{-- ── Context callouts ── --}}
            @if ($this->isAwaitingApproval)
                <flux:callout icon="bell-alert" color="amber">
                    <flux:callout.heading>Your quotation is ready for review</flux:callout.heading>
                    <flux:callout.text>
                        Review the details below, then approve to proceed to payment
                        @if ($quote->expires_at) before <strong>{{ $quote->expires_at->format('M j, Y') }}</strong>@endif.
                    </flux:callout.text>
                </flux:callout>
            @elseif ($quote->status === QuoteStatus::APPROVED && $quote->order_id && ! $this->isPaid)
                <flux:callout icon="credit-card" color="amber">
                    <flux:callout.heading>Payment required to confirm your order</flux:callout.heading>
                    <flux:callout.text>Your order has been created. Complete payment to confirm it.</flux:callout.text>
                </flux:callout>
            @elseif ($quote->status === QuoteStatus::APPROVED && $this->isPaid)
                <flux:callout icon="check-circle" color="green">
                    <flux:callout.heading>Payment received — order placed</flux:callout.heading>
                    <flux:callout.text>
                        Your payment was successful and your order is being prepared.
                        @if ($quote->order)
                            <flux:callout.link :href="route('account.orders.show', $quote->order)" wire:navigate>
                                View order {{ $quote->order->order_number }}
                            </flux:callout.link>
                        @endif
                    </flux:callout.text>
                </flux:callout>
            @elseif ($quote->status === QuoteStatus::DECLINED)
                <flux:callout icon="x-circle" color="red">
                    <flux:callout.heading>You declined this quotation</flux:callout.heading>
                    <flux:callout.text>Contact us if you'd like to request a new quotation.</flux:callout.text>
                </flux:callout>
            @elseif ($quote->status === QuoteStatus::EXPIRED)
                <flux:callout icon="clock" color="zinc">
                    <flux:callout.heading>This quotation has expired</flux:callout.heading>
                    <flux:callout.text>The validity period ended. Please contact us to request a fresh quotation.</flux:callout.text>
                </flux:callout>
            @elseif ($quote->status === QuoteStatus::DRAFT)
                <flux:callout icon="clock" color="amber">
                    <flux:callout.heading>Quotation request under review</flux:callout.heading>
                    <flux:callout.text>Our team is preparing your pricing. You'll be notified once your quotation is ready.</flux:callout.text>
                </flux:callout>
            @endif

            {{-- ── Items ── --}}
            <div>
                <h2 class="mb-4 font-serif text-base font-black uppercase tracking-wider text-ink">
                    Items in your quotation
                </h2>

                <div class="flex flex-col divide-y divide-zinc-100 rounded border border-zinc-200">
                    @foreach ($quote->items as $item)
                        <div class="flex items-center gap-3.5 p-3.5" wire:key="item-{{ $item->id }}">

                            {{-- Thumbnail --}}
                            <div class="flex size-14 shrink-0 items-center justify-center overflow-hidden rounded border border-zinc-100 bg-zinc-50">
                                @if ($item->product?->cover_url)
                                    <img
                                        src="{{ $item->product->cover_url }}"
                                        alt="{{ $item->product_name }}"
                                        class="size-full object-contain p-1"
                                    />
                                @else
                                    <flux:icon.photo variant="outline" class="size-7 text-zinc-200" />
                                @endif
                            </div>

                            {{-- Details --}}
                            <div class="min-w-0 flex-1">
                                <p class="mb-0.5 text-[9px] font-bold uppercase tracking-widest text-ink-3">
                                    @if ($item->product_sku)SKU: {{ $item->product_sku }}@endif
                                    @if ($item->product_model_number) · {{ $item->product_model_number }}@endif
                                </p>
                                @if ($item->product)
                                    <a
                                        href="{{ route('product.show', $item->product) }}"
                                        wire:navigate
                                        class="truncate text-[13px] font-semibold text-ink transition-colors hover:text-brand-500"
                                    >{{ $item->product_name }}</a>
                                @else
                                    <p class="truncate text-[13px] font-semibold text-ink">{{ $item->product_name }}</p>
                                @endif
                                <p class="mt-0.5 text-[11px] text-ink-3">Qty: {{ $item->quantity }}</p>
                            </div>

                            {{-- Price --}}
                            <div class="shrink-0 text-right">
                                @if ($quote->isPriced())
                                    <p class="text-sm font-bold text-ink">{!! money($item->line_total_cents) !!}</p>
                                    @if ($item->quantity > 1)
                                        <p class="text-[11px] text-ink-4">{!! money($item->unit_price_cents) !!} each</p>
                                    @endif
                                @else
                                    <p class="text-[11px] font-medium text-amber-500">Pricing pending</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Quote Summary + Terms (only when priced) --}}
                @if ($quote->isPriced())
                    <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">

                        <div class="overflow-hidden rounded border border-zinc-200 bg-zinc-50">
                            <div class="border-b border-zinc-200 bg-white px-5 py-3">
                                <flux:heading size="sm" class="uppercase tracking-wide">Quote Summary</flux:heading>
                            </div>
                            <div class="space-y-2.5 p-5">
                                <div class="flex justify-between text-[13px]">
                                    <span class="font-medium text-ink-3">Subtotal</span>
                                    <span class="font-bold tabular-nums text-ink">{!! money($quote->subtotal_cents) !!}</span>
                                </div>
                                @if ($quote->discount_cents > 0)
                                    <div class="flex justify-between text-[13px]">
                                        <span class="font-medium text-emerald-600">Discount</span>
                                        <span class="font-bold tabular-nums text-emerald-600">−{!! money($quote->discount_cents) !!}</span>
                                    </div>
                                @endif
                                @if ($quote->shipping_cents > 0)
                                    <div class="flex justify-between text-[13px]">
                                        <span class="font-medium text-ink-3">Delivery</span>
                                        <span class="font-bold tabular-nums text-ink">{!! money($quote->shipping_cents) !!}</span>
                                    </div>
                                @endif
                                @if ($quote->vat_cents > 0)
                                    <div class="flex justify-between text-[13px]">
                                        <span class="font-medium text-ink-3">VAT ({{ rtrim(rtrim(number_format($quote->vat_rate, 2), '0'), '.') }}%)</span>
                                        <span class="font-bold tabular-nums text-ink">{!! money($quote->vat_cents) !!}</span>
                                    </div>
                                @endif
                                <div class="flex items-baseline justify-between border-t border-zinc-200 pt-3">
                                    <span class="text-sm font-bold uppercase tracking-widest text-ink">Total</span>
                                    <span class="font-serif text-2xl font-black leading-none text-brand-500 tabular-nums">
                                        {!! money($quote->total_cents) !!}
                                    </span>
                                </div>
                            </div>
                        </div>

                        @if ($quote->terms)
                            <div class="overflow-hidden rounded border border-zinc-200 bg-white">
                                <div class="border-b border-zinc-200 px-5 py-3">
                                    <flux:heading size="sm" class="uppercase tracking-wide">Terms & Conditions</flux:heading>
                                </div>
                                <div class="p-5">
                                    <p class="whitespace-pre-line text-[13px] leading-relaxed text-ink-3">{{ $quote->terms }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            {{-- ── Approve / Decline action bar ── --}}
            @if ($this->isAwaitingApproval)
                <div class="flex flex-wrap items-center justify-between gap-4 rounded border border-brand-200 bg-brand-50 px-5 py-4">
                    <div>
                        <p class="text-[14px] font-semibold text-brand-700">Ready to proceed?</p>
                        <p class="mt-0.5 text-[13px] text-brand-600">Approve to confirm your order and proceed to payment.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <flux:button variant="ghost" size="sm" wire:click="decline">Decline</flux:button>
                        <flux:button variant="customer-primary" size="customer" icon="check" wire:click="approve">
                            Approve quote
                        </flux:button>
                    </div>
                </div>
            @elseif ($quote->status === QuoteStatus::APPROVED && $quote->order_id && ! $this->isPaid)
                <div class="flex flex-wrap items-center justify-between gap-4 rounded border border-emerald-200 bg-emerald-50 px-5 py-4">
                    <div>
                        <p class="text-[14px] font-semibold text-emerald-800">Quote approved — payment pending</p>
                        <p class="mt-0.5 text-[13px] text-emerald-600">Your order has been created. Complete payment to confirm it.</p>
                    </div>
                    <flux:button
                        variant="customer-primary"
                        size="customer"
                        icon="credit-card"
                        :href="route('payment.page', $quote->order_id)"
                        wire:navigate
                    >
                        Complete payment
                    </flux:button>
                </div>
            @endif

            {{-- ── Order notes ── --}}
            @if ($quote->notes)
                <div class="rounded border border-zinc-200 bg-zinc-50 px-5 py-4">
                    <p class="mb-1 text-[11px] font-bold uppercase tracking-widest text-ink-3">Notes</p>
                    <p class="text-sm leading-relaxed text-ink-2">{{ $quote->notes }}</p>
                </div>
            @endif

            {{-- ── Quotation history timeline ── --}}
            <div>
                <h2 class="mb-5 font-serif text-base font-black uppercase tracking-wider text-ink">
                    Quotation History
                </h2>

                @php
                    $mainPath = [
                        [
                            'value' => 'draft',
                            'label' => 'Request Submitted',
                            'icon'  => 'document-text',
                            'desc'  => 'Your quotation request has been received.',
                        ],
                        [
                            'value' => 'awaiting_approval',
                            'label' => 'Quotation Ready',
                            'icon'  => 'clipboard-document-check',
                            'desc'  => 'Your quotation has been priced and is ready for your review.',
                        ],
                        [
                            'value' => 'approved',
                            'label' => 'Quote Accepted',
                            'icon'  => 'check-badge',
                            'desc'  => 'You accepted this quotation and your order was created.',
                        ],
                    ];

                    $isDeclined = $quote->status === QuoteStatus::DECLINED;
                    $isExpired  = $quote->status === QuoteStatus::EXPIRED;
                    $isTerminal = $isDeclined || $isExpired;

                    $histories = $quote->statusHistories->keyBy('to_status');

                    // Draft is always implicit — the quote exists so it was submitted
                    if (! $histories->has('draft')) {
                        $histories->put('draft', (object) ['created_at' => $quote->created_at, 'note' => null]);
                    }

                    // If admin moved to SENT before AWAITING_APPROVAL, alias it for the "Quotation Ready" step
                    if (! $histories->has('awaiting_approval') && $histories->has('sent')) {
                        $histories->put('awaiting_approval', $histories->get('sent'));
                    }

                    $maxReachedIndex = 0;
                    foreach ($mainPath as $i => $step) {
                        if ($histories->has($step['value'])) {
                            $maxReachedIndex = $i;
                        }
                    }
                @endphp

                <div class="relative px-1">
                    @foreach ($mainPath as $index => $step)
                        @php
                            $history        = $histories->get($step['value']);
                            $reached        = $index <= $maxReachedIndex;
                            $isCurrent      = ! $isTerminal && $index === $maxReachedIndex;
                            $isLast         = $index === count($mainPath) - 1;
                            $injectTerminal = $isTerminal && $index === $maxReachedIndex;
                        @endphp

                        <div class="relative flex gap-6 {{ $isLast && ! $injectTerminal ? 'pb-0' : 'pb-10' }}">

                            {{-- Connector line --}}
                            @if (! $isLast || $injectTerminal)
                                @php $nextReached = ($index + 1) <= $maxReachedIndex; @endphp
                                <div @class([
                                    'absolute left-[17px] top-9 bottom-0 w-0.5 z-0',
                                    'bg-brand-500' => $nextReached || $injectTerminal,
                                    'bg-zinc-100'  => ! $nextReached && ! $injectTerminal,
                                ])></div>
                            @endif

                            {{-- Dot --}}
                            <div @class([
                                'relative z-10 flex size-9 shrink-0 items-center justify-center rounded-full transition-all',
                                'bg-brand-500 text-white ring-4 ring-brand-500/20' => $isCurrent,
                                'bg-brand-500 text-white'                          => $reached && ! $isCurrent,
                                'bg-zinc-50 border border-zinc-100 text-zinc-300'  => ! $reached,
                            ])>
                                <flux:icon :name="$step['icon']" variant="mini" class="size-4.5" />
                            </div>

                            {{-- Content --}}
                            <div class="flex-1 pt-0.5">
                                <div class="flex flex-col justify-between gap-1 sm:flex-row sm:items-start sm:gap-4">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p @class([
                                                'text-[14px] font-bold',
                                                'text-ink'   => $reached,
                                                'text-ink-4' => ! $reached,
                                            ])>{{ $step['label'] }}</p>

                                            @if ($isCurrent)
                                                <span class="inline-flex items-center gap-1.5 rounded-sm border border-brand-200 bg-brand-50 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-widest text-brand-500">
                                                    <span class="relative flex size-1.5">
                                                        <span class="absolute inline-flex size-full animate-ping rounded-full bg-brand-500 opacity-75"></span>
                                                        <span class="relative inline-flex size-1.5 rounded-full bg-brand-500"></span>
                                                    </span>
                                                    Current
                                                </span>
                                            @endif
                                        </div>
                                        <p @class([
                                            'mt-1 text-[12px] leading-relaxed',
                                            'font-medium text-ink-2' => $reached,
                                            'text-zinc-300'          => ! $reached,
                                        ])>{{ $reached ? $step['desc'] : 'Pending…' }}</p>
                                    </div>

                                    @if ($history)
                                        <div class="shrink-0 sm:text-right">
                                            <p class="text-[12px] font-bold text-ink">{{ $history->created_at->format('M j, Y') }}</p>
                                            <p class="mt-0.5 text-[11px] font-medium text-ink-3">{{ $history->created_at->format('g:i A') }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Terminal step injected after last reached --}}
                        @if ($injectTerminal)
                            @php $terminalHistory = $histories->get($isDeclined ? 'declined' : 'expired'); @endphp
                            <div class="relative flex gap-6 pb-0">
                                @if ($isDeclined)
                                    <div class="relative z-10 flex size-9 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-500 ring-4 ring-red-50/50">
                                        <flux:icon.x-circle variant="mini" class="size-4.5" />
                                    </div>
                                    <div class="flex-1 pt-0.5">
                                        <div class="flex flex-col justify-between gap-1 sm:flex-row sm:items-start sm:gap-4">
                                            <div>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <p class="text-[14px] font-bold text-red-500">Quote Declined</p>
                                                    <span class="inline-flex items-center gap-1.5 rounded-sm border border-red-100 bg-red-50 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-widest text-red-500">
                                                        <span class="relative flex size-1.5">
                                                            <span class="absolute inline-flex size-full animate-ping rounded-full bg-red-500 opacity-75"></span>
                                                            <span class="relative inline-flex size-1.5 rounded-full bg-red-500"></span>
                                                        </span>
                                                        Current
                                                    </span>
                                                </div>
                                                <p class="mt-1 text-[12px] font-medium leading-relaxed text-ink-2">You declined this quotation.</p>
                                            </div>
                                            @if ($terminalHistory)
                                                <div class="shrink-0 sm:text-right">
                                                    <p class="text-[12px] font-bold text-ink">{{ $terminalHistory->created_at->format('M j, Y') }}</p>
                                                    <p class="mt-0.5 text-[11px] font-medium text-ink-3">{{ $terminalHistory->created_at->format('g:i A') }}</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <div class="relative z-10 flex size-9 shrink-0 items-center justify-center rounded-full border border-zinc-200 bg-zinc-50 text-zinc-400">
                                        <flux:icon.clock variant="mini" class="size-4.5" />
                                    </div>
                                    <div class="flex-1 pt-0.5">
                                        <p class="text-[14px] font-bold text-ink-3">Quote Expired</p>
                                        <p class="mt-1 text-[12px] font-medium leading-relaxed text-ink-3">The validity period ended without a response.</p>
                                    </div>
                                @endif
                            </div>
                            @break
                        @endif
                    @endforeach
                </div>
            </div>

            {{-- ── Actions ── --}}
            @if ($quote->document_path)
                <div class="flex flex-wrap gap-2">
                    <flux:button
                        size="customer"
                        variant="customer-outline"
                        icon="arrow-down-tray"
                        tag="a"
                        :href="route('account.quotes.download', $quote)"
                    >
                        Download PDF
                    </flux:button>
                </div>
            @endif

            {{-- ── Contact support ── --}}
            <div class="border-t border-zinc-100 pt-2 text-center">
                <p class="text-[13px] text-ink-3">
                    Need help with this quotation?
                    <a
                        class="font-bold text-brand-500 transition-colors hover:underline"
                        href="mailto:orders@sheffieldsteelsystems.com?subject=Quote%20{{ urlencode($quote->quote_number) }}%20enquiry"
                    >
                        Contact support
                    </a>
                </p>
            </div>

        </div>
    </div>

</div>
