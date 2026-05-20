<?php

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Services\QuotationService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Quotation Details')] #[Layout('layouts.customer')] class extends Component
{
    public Quote $quote;

    #[Locked]
    public int $quoteId;

    // Rejection reason — optional, shown when customer clicks Reject
    public string $rejectNote = '';

    // =========================================================================
    //  MOUNT
    // =========================================================================

    public function mount(Quote $quote): void
    {
        // Ensure the quotation belongs to this customer
        if ($quote->user_id !== auth()->id()) {
            $this->redirectRoute('customer.quotations.index', navigate: true);

            return;
        }

        $this->quoteId = $quote->id;
        $this->quote = $quote->load([
            'items' => fn ($q) => $q
                ->select(['id', 'quote_id', 'product_id', 'product_snapshot', 'quantity', 'original_price_cents', 'quoted_price_cents', 'discount_cents', 'total_cents'])
                ->with(['product' => fn ($q) => $q->select(['id', 'image_path'])]),
            'statusHistories' => fn ($q) => $q->select(['id', 'quote_id', 'to_status', 'created_at']),
            'order' => fn ($q) => $q->select(['id', 'reference']),
        ]);
    }

    // =========================================================================
    //  REAL-TIME UPDATES
    // =========================================================================

    #[On('echo-private:quote.{quoteId},.quote.updated')]
    public function handleQuoteUpdate(array $data): void
    {
        // Refresh the quote from database
        $this->quote = $this->quote->fresh([
            'items' => fn ($q) => $q
                ->select(['id', 'quote_id', 'product_id', 'product_snapshot', 'quantity', 'original_price_cents', 'quoted_price_cents', 'discount_cents', 'total_cents'])
                ->with(['product' => fn ($q) => $q->select(['id', 'image_path'])]),
            'statusHistories' => fn ($q) => $q->select(['id', 'quote_id', 'to_status', 'created_at']),
            'order' => fn ($q) => $q->select(['id', 'reference']),
        ]);

        // Clear computed caches
        unset($this->canRespond, $this->isExpired, $this->showPrices);

        // Show toast notification
        $message = match ($data['update_type']) {
            'pricing' => 'Your quotation has been priced and is ready for review!',
            'status' => "Quotation status updated to {$data['status_label']}",
            default => 'Your quotation has been updated',
        };

        $this->dispatch('notify', title: 'Quotation Updated', variant: 'success', message: $message);
    }

    // =========================================================================
    //  COMPUTED — UI STATE HELPERS
    // =========================================================================

    #[Computed]
    public function canRespond(): bool
    {
        return $this->quote->canBeAccepted();
    }

    #[Computed]
    public function isExpired(): bool
    {
        return $this->quote->isExpired() || ($this->quote->isSent() && $this->quote->expires_at?->isPast());
    }

    /**
     * Check if prices should be shown.
     * For product quotes (PENDING status), we don't show prices since
     * the customer is asking for pricing. Prices are only shown after
     * the quote has been sent (priced by admin).
     */
    #[Computed]
    public function showPrices(): bool
    {
        return $this->quote->isSent() || $this->quote->isAccepted();
    }

    // =========================================================================
    //  ACCEPT QUOTE
    // =========================================================================

    public function acceptQuote(): void
    {
        if (! $this->canRespond) {
            $this->dispatch('notify', title: 'Quote Unavailable', variant: 'danger', message: 'This quotation is no longer available to accept.');

            return;
        }

        try {
            $salesOrder = app(QuotationService::class)->accept($this->quote);

            $this->dispatch('notify', title: 'Quote Accepted', variant: 'success', message: 'Quotation accepted! Redirecting to payment...');
            $this->redirectRoute('checkout.quote-pay', $salesOrder->reference, navigate: true);
        } catch (Throwable $e) {
            logger()->error('Customer failed to accept quotation.', [
                'quote_id' => $this->quote->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('notify', title: 'Accept Failed', variant: 'danger', message: 'Something went wrong. Please try again or contact support.');
        }
    }

    // =========================================================================
    //  REJECT QUOTE
    // =========================================================================

    public function rejectQuote(): void
    {
        $this->validate([
            'rejectNote' => ['nullable', 'string', 'max:500'],
        ]);

        if (! $this->canRespond) {
            $this->dispatch('notify', title: 'Quote Unavailable', variant: 'danger', message: 'This quotation is no longer available.');

            return;
        }

        try {
            app(QuotationService::class)->reject($this->quote, $this->rejectNote ?: null);

            $this->quote->refresh();
            $this->rejectNote = '';
            $this->modal('reject-quote')->close();
            $this->dispatch('notify', title: 'Quote Rejected', variant: 'warning', message: 'Quotation rejected.');
        } catch (Throwable $e) {
            $this->dispatch('notify', title: 'Reject Failed', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }
};
?>

<div>
    <div class="bg-white border border-zinc-200">
        <div class="p-4 bg-white border-b border-zinc-200 flex items-center justify-between flex-wrap gap-3">
            <a href="{{ route('customer.quotations.index') }}" wire:navigate
                class="flex items-center gap-1.5 text-[12px] font-bold tracking-widest uppercase text-on-surface-variant transition-colors hover:text-primary cursor-pointer">
                <flux:icon.chevron-left class="w-3.5 h-3.5" />
                Back to Quotations
            </a>
            <div class="font-serif text-[18px] font-black text-on-surface">
                QUOTATION <em class="text-primary not-italic">#{{ $quote->reference }}</em>
            </div>
            <flux:badge :color="$quote->status->color()">{{ $quote->status->label() }}
            </flux:badge>
        </div>

        <div class="p-5 flex flex-col gap-5">

            {{-- ============================================================ --}}
            {{-- CONTEXT BANNERS                                               --}}
            {{-- ============================================================ --}}

            @if ($this->canRespond)
                <div class="flex items-start gap-3 p-4 bg-amber-50 border border-amber-200 rounded-lg mb-5">
                    <flux:icon.clock class="size-5 shrink-0 mt-0.5 text-amber-500" />
                    <div class="text-sm flex-1">
                        <p class="font-medium text-amber-800">Your quotation is ready for review</p>
                        <p class="text-amber-700 mt-0.5">
                            Sheffield Africa has priced your request.
                            Please review the details below and accept or reject before
                            @if ($quote->expires_at)
                                <strong>{{ $quote->expires_at->format('M d, Y') }}</strong>.
                            @else
                                the validity period ends.
                            @endif
                        </p>
                    </div>
                </div>
            @endif

            @if ($this->isExpired)
                <div class="flex items-start gap-3 p-4 bg-zinc-50 border border-zinc-200 rounded-lg mb-5">
                    <flux:icon.exclamation-triangle class="size-5 shrink-0 mt-0.5 text-on-surface-variant" />
                    <div class="text-sm">
                        <p class="font-medium text-on-surface">This quotation has expired</p>
                        <p class="text-on-surface-variant mt-0.5">
                            The validity period ended without a response.
                            Please contact us if you'd still like to proceed —
                            we can prepare a fresh quotation for you.
                        </p>
                    </div>
                </div>
            @endif

            @if ($quote->isAccepted() && $quote->order)
                <div class="flex items-center gap-3 p-3 bg-teal-50 border border-teal-200 rounded-sm mb-5">
                    <flux:icon.tag class="size-4 shrink-0 text-teal-500" />
                    <flux:text class="text-sm text-teal-800 flex-1">
                        This quotation was converted to a sales order
                        <flux:link :href="route('customer.orders.show', $quote->order)" wire:navigate
                            class="font-medium">
                            {{ $quote->order->reference }}
                        </flux:link>
                    </flux:text>
                </div>
            @endif

            @if ($quote->isRejected())
                <div class="flex items-start gap-3 p-4 bg-red-50 border border-red-200 rounded-lg mb-5">
                    <flux:icon.x-circle class="size-5 shrink-0 mt-0.5 text-red-400" />
                    <div class="text-sm">
                        <p class="font-medium text-red-700">You rejected this quotation</p>
                        <p class="text-red-600 mt-0.5">
                            If you changed your mind, please contact our team to request a new quote.
                        </p>
                    </div>
                </div>
            @endif

            {{-- ============================================================ --}}
            {{-- QUOTE META                                                     --}}
            {{-- ============================================================ --}}
            <div class="space-y-1 mb-5">
                <flux:heading>{{ $quote->reference }}</flux:heading>
                <flux:text>
                    {{ $quote->items->count() }}
                    {{ Str::plural('item', $quote->items->count()) }}
                </flux:text>
                <flux:text>Submitted on {{ $quote->created_at->format('M j, Y') }}</flux:text>
                @if ($quote->quoted_at)
                    <flux:text>Quoted on {{ $quote->quoted_at->format('M j, Y') }}</flux:text>
                @endif
                @if ($quote->expires_at && $this->canRespond)
                    <flux:text
                        class="{{ $quote->expires_at->diffInHours() <= 48 ? 'text-amber-600' : 'text-on-surface-variant' }}">
                        Valid until {{ $quote->expires_at->format('M j, Y') }}
                        ({{ $quote->expires_at->diffForHumans() }})
                    </flux:text>
                @endif
            </div>

            <flux:separator class="my-5" />

            {{-- Items List --}}
            <div>
                <h3 class="text-base font-bold uppercase tracking-wider text-on-surface mb-4 font-serif">Items in Your
                    Quotation
                </h3>

                <div class="flex flex-col border border-zinc-200">
                    @foreach ($quote->items as $item)
                        <div class="flex items-center gap-3.5 p-3.5 border-b border-zinc-200 last:border-b-0">
                            <div class="w-14 h-14 bg-zinc-50 flex items-center justify-center shrink-0 relative">
                                @if ($item->productImageUrl())
                                    <img src="{{ asset($item->productImageUrl()) }}" alt="{{ $item->productName() }}"
                                        class="w-[90%] h-[90%] object-contain">
                                @else
                                    <flux:icon.photo class="w-8 h-8 text-zinc-200" />
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                @if ($item->productSku())
                                    <div class="text-[9px] font-bold tracking-widest uppercase text-on-surface-variant mb-0.5">
                                        SKU: {{ $item->productSku() }}
                                    </div>
                                @endif
                                <div class="text-[13px] font-semibold text-on-surface mb-0.5 truncate">
                                    {{ $item->productName() }}
                                </div>
                                <div class="text-[11px] text-on-surface-variant">Qty: {{ $item->quantity }}</div>
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                @if ($this->showPrices)
                                    <div class="text-[13px] font-bold text-on-surface shrink-0">
                                        {{ format_currency($item->effective_price * $item->quantity) }}
                                    </div>
                                    <div class="text-[10px] text-on-surface-variant">
                                        {{ $item->quantity }} × {{ format_currency($item->effective_price) }}
                                    </div>
                                @else
                                    <div class="text-[11px] text-amber-500 font-medium">Pricing pending</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>


                {{-- ============================================================ --}}
                {{-- TOTALS (only shown when prices are available)                  --}}
                {{-- ============================================================ --}}
                @if ($this->showPrices)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-5">
                        {{-- Quote Totals --}}
                        <div class="bg-zinc-50 border border-zinc-200 rounded-sm overflow-hidden">
                            <div class="px-5 py-4 border-b border-zinc-200 bg-white">
                                <h3 class="text-[13px] font-bold uppercase tracking-widest text-on-surface font-serif">
                                    Quote Summary</h3>
                            </div>
                            <div class="p-5 space-y-3">
                                <div class="flex justify-between text-[13px]">
                                    <span class="text-on-surface-variant font-medium">Subtotal</span>
                                    <span
                                        class="text-on-surface font-bold">{{ format_currency($quote->subtotal) }}</span>
                                </div>
                                @if ($quote->discount > 0)
                                    <div class="flex justify-between text-[13px]">
                                        <span class="text-green-600 font-medium">Discount</span>
                                        <span class="text-green-600 font-bold">−
                                            {{ format_currency($quote->discount) }}</span>
                                    </div>
                                @endif
                                <div class="flex justify-between text-[13px]">
                                    <span class="text-on-surface-variant font-medium">Delivery</span>
                                    <span class="text-on-surface font-bold">
                                        @if ($quote->shipping_cents === 0 && !$quote->status->isTerminal())
                                            <span class="text-amber-500">TBD</span>
                                        @elseif ($quote->shipping_cents === 0)
                                            FREE
                                        @else
                                            {{ format_currency($quote->shipping) }}
                                        @endif
                                    </span>
                                </div>
                                @php $taxService = app(\App\Services\TaxService::class); @endphp
                                @if ($taxService->isEnabled() && $quote->tax_cents > 0)
                                    <div class="flex justify-between text-[13px]">
                                        <span class="text-on-surface-variant font-medium">
                                            {{ $taxService->name() }}
                                            @if ($taxService->isInclusive())
                                                <span class="text-[11px] font-normal">(incl.)</span>
                                            @endif
                                        </span>
                                        <span class="text-on-surface font-bold">
                                            {{ format_currency($quote->tax) }}
                                        </span>
                                    </div>
                                @endif
                                <div class="pt-3 border-t border-zinc-200 flex justify-between items-baseline">
                                    <span
                                        class="text-[14px] font-bold uppercase tracking-widest text-on-surface">Total</span>
                                    <div class="text-right">
                                        <span
                                            class="text-[24px] font-black text-primary font-barlow-condensed leading-none">
                                            {{ format_currency($quote->total) }}
                                        </span>
                                        @if ($quote->shipping_cents === 0 && !$quote->status->isTerminal())
                                            <div class="text-[9px] text-amber-500 font-normal mt-1">Excludes delivery
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="mt-6 p-4 bg-amber-50 border border-amber-200 rounded-sm">
                        <div class="flex items-start gap-2">
                            <flux:icon.clock class="size-4 shrink-0 mt-0.5 text-amber-500" />
                            <div class="text-sm text-amber-700">
                                Pricing is being prepared by our team. You'll be notified once your quotation is ready.
                            </div>
                        </div>
                    </div>
                @endif

                <flux:separator class="my-8" />

                {{-- ============================================================ --}}
                {{-- ACCEPT / REJECT ACTIONS                                        --}}
                {{-- ============================================================ --}}
                @if ($this->canRespond)
                    <div
                        class="flex flex-col sm:flex-row items-center gap-3 p-4 bg-zinc-50 border border-zinc-200 rounded-sm mt-6">
                        <div class="flex-1 text-sm">
                            <p class="font-medium text-on-surface">Ready to decide?</p>
                            <p class="text-on-surface-variant mt-0.5">
                                Accept to proceed to payment, or reject if you'd like to pass on this quote.
                            </p>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <flux:modal.trigger name="reject-quote">
                                <flux:button variant="customer-danger" size="customer" class="cursor-pointer px-5!">
                                    <flux:icon.x-circle class="w-3.5 h-3.5" />
                                    Reject
                                </flux:button>
                            </flux:modal.trigger>

                            <flux:modal.trigger name="accept-quote">
                                <flux:button variant="customer-primary" size="customer" class="cursor-pointer px-5!">
                                    <flux:icon.check-circle class="w-3.5 h-3.5" />
                                    Accept Quote
                                </flux:button>
                            </flux:modal.trigger>
                        </div>
                    </div>
                @endif

                {{-- ============================================================ --}}
                {{-- TIMELINE                                                       --}}
                {{-- ============================================================ --}}
                <div class="mt-8">
                    <h3 class="text-base font-bold uppercase tracking-wider text-on-surface mb-4 font-serif">Quotation
                        History</h3>

                    @php
                        $mainPath = [QuoteStatus::PENDING, QuoteStatus::SENT, QuoteStatus::ACCEPTED];

                        $isCancelled = $quote->isCancelled();
                        $isRejected = $quote->isRejected();
                        $isExpiredS = $quote->isExpired();
                        $isTerminal = $isCancelled || $isRejected || $isExpiredS;
                        $histories = $quote->statusHistories->keyBy('to_status');

                        // Find the current active step (last reached step)
                        $currentStepIndex = -1;
                        foreach ($mainPath as $idx => $step) {
                            if ($histories->has($step->value)) {
                                $currentStepIndex = $idx;
                            }
                        }

                        $stepLabels = [
                            'pending' => ['label' => 'Quote Requested', 'desc' => 'Your quote request was submitted.'],
                            'sent' => ['label' => 'Quote Ready', 'desc' => 'Sheffield Africa has priced your request.'],
                            'accepted' => ['label' => 'Quote Accepted', 'desc' => 'You accepted this quotation.'],
                        ];
                    @endphp

                    <div class="relative">
                        @foreach ($mainPath as $index => $step)
                            @php
                                $history = $histories->get($step->value);
                                $reached = (bool) $history;
                                $isActive = $index === $currentStepIndex && !$isTerminal;
                                $isLast = $index === count($mainPath) - 1;
                                $next = $mainPath[$index + 1] ?? null;
                                $nextReached = $next && $histories->has($next->value);
                                $dimmed = $isTerminal && !$reached;
                                $meta = $stepLabels[$step->value];
                            @endphp

                            <div class="relative flex gap-5 {{ $isLast ? 'pb-0' : 'pb-8' }}">
                                @if (!$isLast)
                                    <div @class([
                                        'absolute left-4 top-8 bottom-0 w-0.5 z-0',
                                        'bg-green-500' => $nextReached,
                                        'bg-zinc-200 dark:bg-zinc-700' => !$nextReached,
                                    ])></div>
                                @endif

                                <div @class([
                                    'relative z-10 shrink-0 w-8 h-8 rounded-full flex items-center justify-center transition-colors',
                                    'bg-green-500 text-white ring-4 ring-green-100 dark:ring-green-900' => $isActive,
                                    'bg-green-500 text-white' => $reached && !$isActive,
                                    'bg-zinc-100 dark:bg-zinc-800 text-zinc-300 dark:text-on-surface-variant' => $dimmed,
                                    'bg-zinc-100 dark:bg-zinc-800 text-on-surface-variant' => !$reached && !$dimmed,
                                ])>
                                    <flux:icon name="{{ $step->icon() }}" class="size-4" />
                                </div>

                                <div class="flex-1 pt-1">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <div @class([
                                                'text-sm',
                                                'font-semibold text-green-600 dark:text-green-400' => $isActive,
                                                'font-semibold text-on-surface dark:text-white' => $reached && !$isActive,
                                                'text-zinc-300 dark:text-on-surface-variant' => $dimmed,
                                                'text-on-surface-variant' => !$reached && !$dimmed,
                                            ])>
                                                {{ $meta['label'] }}
                                            </div>
                                            <div
                                                class="text-xs mt-0.5
                                        {{ $reached ? 'text-on-surface-variant' : 'text-zinc-300 dark:text-on-surface-variant' }}">
                                                {{ $reached ? $meta['desc'] : 'Pending' }}
                                            </div>
                                        </div>

                                        @if ($history)
                                            <div class="text-right shrink-0">
                                                <div class="text-xs font-medium text-on-surface">
                                                    {{ $history->created_at->format('M j, Y') }}
                                                </div>
                                                <div class="text-xs text-on-surface-variant mt-0.5">
                                                    {{ $history->created_at->format('g:i A') }}
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        @if ($isRejected)
                            <div class="relative flex gap-5 pt-6">
                                <div class="absolute left-4 top-0 h-6 w-0.5 bg-zinc-300 z-0"></div>
                                <div
                                    class="relative z-10 shrink-0 w-8 h-8 rounded-full flex items-center justify-center bg-red-100 text-red-500">
                                    <flux:icon name="{{ QuoteStatus::REJECTED->icon() }}" class="size-4" />
                                </div>
                                <div class="flex-1 pt-1">
                                    <div class="text-sm font-semibold text-red-600">You rejected this quote
                                    </div>
                                    <div class="text-xs text-on-surface-variant mt-0.5">
                                        Contact us if you'd like a revised quotation.
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($isExpiredS)
                            <div class="relative flex gap-5 pt-6">
                                <div class="absolute left-4 top-0 h-6 w-0.5 bg-zinc-300 z-0"></div>
                                <div
                                    class="relative z-10 shrink-0 w-8 h-8 rounded-full flex items-center justify-center bg-zinc-100 text-on-surface-variant">
                                    <flux:icon name="{{ QuoteStatus::EXPIRED->icon() }}" class="size-4" />
                                </div>
                                <div class="flex-1 pt-1">
                                    <div class="text-sm font-semibold text-on-surface-variant">Quote expired</div>
                                    <div class="text-xs text-on-surface-variant mt-0.5">
                                        The validity period ended without a response.
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($isCancelled)
                            <div class="relative flex gap-5 pt-6">
                                <div class="absolute left-4 top-0 h-6 w-0.5 bg-zinc-300 z-0"></div>
                                <div
                                    class="relative z-10 shrink-0 w-8 h-8 rounded-full flex items-center justify-center bg-rose-100 text-rose-500">
                                    <flux:icon name="{{ QuoteStatus::CANCELLED->icon() }}" class="size-4" />
                                </div>
                                <div class="flex-1 pt-1">
                                    <div class="text-sm font-semibold text-rose-600">Quotation cancelled</div>
                                    <div class="text-xs text-on-surface-variant mt-0.5">
                                        This quotation was cancelled. Please contact us for assistance.
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex flex-col items-center gap-3 mt-8">
                    @if ($quote->quoted_at)
                        <flux:button variant="customer-outline" size="customer" class="cursor-pointer px-5!"
                            :href="route('customer.quotations.pdf', $quote)" target="_blank">
                            <flux:icon.arrow-down-tray class="w-3.5 h-3.5" />
                            Download Quotation PDF
                        </flux:button>
                    @endif

                    <div class="text-[13px] text-on-surface-variant">
                        Need help? <a href="#" class="text-primary font-bold hover:underline">Contact
                            Support</a>
                    </div>
                </div>
            </div>
        </div>
    </div>


    {{-- ================================================================== --}}
    {{-- MODAL: Accept Quote confirmation                                    --}}
    {{-- ================================================================== --}}
    <flux:modal name="accept-quote" class="max-w-md">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Accept this quotation?</flux:heading>
                <flux:subheading>
                    You'll be taken to the payment page to complete your order.
                </flux:subheading>
            </div>

            <div class="p-3 bg-zinc-50 dark:bg-zinc-800 rounded-sm text-sm space-y-1.5">
                <div class="flex justify-between">
                    <span class="text-on-surface-variant">Reference</span>
                    <span class="font-medium">{{ $quote->reference }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-on-surface-variant">Total</span>
                    <span class="font-medium">{{ format_currency($quote->total) }}</span>
                </div>
                @if ($quote->expires_at)
                    <div class="flex justify-between">
                        <span class="text-on-surface-variant">Valid until</span>
                        <span class="font-medium">{{ $quote->expires_at->format('M d, Y') }}
                        </span>
                    </div>
                @endif
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <flux:modal.close>
                    <flux:button variant="customer-outline" size="customer" class="cursor-pointer px-5!">Cancel
                    </flux:button>
                </flux:modal.close>
                <flux:button wire:click="acceptQuote" variant="customer-primary" size="customer"
                    class="cursor-pointer px-5!">
                    <flux:icon.check-circle class="w-3.5 h-3.5" />
                    Yes, Accept & Pay
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- ================================================================== --}}
    {{-- MODAL: Reject Quote                                                 --}}
    {{-- ================================================================== --}}
    <flux:modal name="reject-quote" class="max-w-md">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Reject this quotation?</flux:heading>
                <flux:subheading>
                    This cannot be undone. Let us know why so we can serve you better.
                </flux:subheading>
            </div>

            <flux:textarea wire:model="rejectNote" label="Reason (optional)"
                placeholder="e.g. Price too high, found a better option..." rows="3" />

            <div class="flex justify-end gap-3 pt-2">
                <flux:modal.close>
                    <flux:button variant="customer-outline" size="customer" class="cursor-pointer px-5!">Keep
                    </flux:button>
                </flux:modal.close>
                <flux:button wire:click="rejectQuote" variant="customer-danger" size="customer"
                    class="cursor-pointer px-5! text-white!">
                    <flux:icon.x-circle class="w-3.5 h-3.5" />
                    Reject Quotation
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
