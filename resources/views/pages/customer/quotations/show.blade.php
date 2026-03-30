<?php

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Services\QuotationService;
use Livewire\Attributes\{Computed, Layout, Title};
use Livewire\Component;

new #[Title('Quotation Details')] #[Layout('layouts.customer')] class extends Component {
    public Quote $quote;

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

        $this->quote = $quote->load(['items.product', 'statusHistories', 'order']);
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
        if (!$this->canRespond) {
            $this->dispatch('notify', variant: 'danger', message: 'This quotation is no longer available to accept.');
            return;
        }

        try {
            $salesOrder = app(QuotationService::class)->accept($this->quote);

            $this->dispatch('notify', variant: 'success', message: 'Quotation accepted! Redirecting to payment...');
            $this->redirectRoute('checkout.pay', $salesOrder->reference, navigate: true);
        } catch (\Throwable $e) {
            logger()->error('Customer failed to accept quotation.', [
                'quote_id' => $this->quote->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('notify', variant: 'danger', message: 'Something went wrong. Please try again or contact support.');
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

        if (!$this->canRespond) {
            $this->dispatch('notify', variant: 'danger', message: 'This quotation is no longer available.');
            return;
        }

        try {
            app(QuotationService::class)->reject($this->quote, $this->rejectNote ?: null);

            $this->quote->refresh();
            $this->rejectNote = '';
            $this->modal('reject-quote')->close();
            $this->dispatch('notify', variant: 'warning', message: 'Quotation rejected.');
        } catch (\Throwable $e) {
            $this->dispatch('notify', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }
};
?>

<div>
    <flux:card class="rounded-md p-0">

        {{-- Header --}}
        <div class="flex items-center gap-3 px-3 py-2 border-b border-zinc-200 dark:border-zinc-700">
            <flux:button size="xs" icon="arrow-long-left" variant="ghost" class="cursor-pointer"
                :href="route('customer.quotations.index')" wire:navigate />

            <flux:heading size="lg">Quotation Details</flux:heading>

            @if ($quote->quoted_at)
                <flux:button size="xs" variant="ghost" icon="arrow-down-tray" class="cursor-pointer"
                    :href="route('customer.quotations.pdf', $quote)" target="_blank">
                    PDF
                </flux:button>
            @endif

            <flux:badge :color="$quote->status->color()" variant="solid" size="sm" class="ml-auto">
                {{ $quote->status->label() }}
            </flux:badge>
        </div>

        <section class="p-5">

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
                    <flux:icon.exclamation-triangle class="size-5 shrink-0 mt-0.5 text-zinc-400" />
                    <div class="text-sm">
                        <p class="font-medium text-zinc-700">This quotation has expired</p>
                        <p class="text-zinc-500 mt-0.5">
                            The validity period ended without a response.
                            Please contact us if you'd still like to proceed —
                            we can prepare a fresh quotation for you.
                        </p>
                    </div>
                </div>
            @endif

            @if ($quote->isAccepted() && $quote->order)
                <div class="flex items-start gap-3 p-4 bg-teal-50 border border-teal-200 rounded-lg mb-5">
                    <flux:icon.check-circle class="size-5 shrink-0 mt-0.5 text-teal-500" />
                    <div class="text-sm flex items-center justify-between w-full">
                        <div>
                            <p class="font-medium text-teal-800">You accepted this quotation</p>
                            <p class="text-teal-700 mt-0.5">
                                A sales order has been created: {{ $quote->order->reference }}
                            </p>
                        </div>
                        <flux:button size="sm" variant="ghost"
                            :href="route('customer.orders.show', $quote->order)" wire:navigate>
                            View Order
                        </flux:button>
                    </div>
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
                    <flux:text class="{{ $quote->expires_at->diffInHours() <= 48 ? 'text-amber-600' : 'text-zinc-500' }}">
                        Valid until {{ $quote->expires_at->format('M j, Y') }}
                        ({{ $quote->expires_at->diffForHumans() }})
                    </flux:text>
                @endif
            </div>

            <flux:separator class="my-5" />

            {{-- ============================================================ --}}
            {{-- ITEMS                                                          --}}
            {{-- ============================================================ --}}
            <flux:heading class="text-lg mb-4">Items in Your Quotation</flux:heading>

            <div class="space-y-4">
                @foreach ($quote->items as $item)
                    <div class="border rounded-md p-4">
                        <div class="flex gap-4">
                            <div class="shrink-0">
                                @if ($item->productImageUrl())
                                    <img src="{{ asset($item->productImageUrl()) }}" alt="{{ $item->productName() }}"
                                        class="w-20 h-20 object-contain rounded" />
                                @else
                                    <div class="w-20 h-20 bg-zinc-100 rounded flex items-center justify-center">
                                        <flux:icon.photo class="w-8 h-8 text-zinc-300" />
                                    </div>
                                @endif
                            </div>

                            <div class="flex-1">
                                <flux:heading size="sm">{{ $item->productName() }}</flux:heading>
                                @if ($item->productSku())
                                    <flux:text size="sm" class="text-zinc-400">SKU: {{ $item->productSku() }}</flux:text>
                                @endif
                                <flux:text size="sm" class="text-zinc-500 mt-1">
                                    Qty: {{ $item->quantity }}
                                </flux:text>
                                @if ($this->showPrices)
                                    <flux:text size="sm" class="text-zinc-500">
                                        {{ $item->quantity }} × {{ format_currency($item->effective_price) }}
                                    </flux:text>
                                    <flux:text size="sm" class="font-semibold mt-1">
                                        {{ format_currency($item->effective_price * $item->quantity) }}
                                    </flux:text>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>


            {{-- ============================================================ --}}
            {{-- TOTALS (only shown when prices are available)                  --}}
            {{-- ============================================================ --}}
            @if ($this->showPrices)
                <div class="mt-6 space-y-1.5 max-w-xs ml-auto">
                    <div class="flex justify-between text-sm">
                        <flux:text>Subtotal</flux:text>
                        <span>{{ format_currency($quote->subtotal) }}</span>
                    </div>

                    @if ($quote->discount > 0)
                        <div class="flex justify-between text-sm text-green-600">
                            <span>Discount</span>
                            <span>− {{ format_currency($quote->discount) }}</span>
                        </div>
                    @endif

                    <div class="flex justify-between text-sm">
                        <flux:text>Delivery</flux:text>
                        <span>
                            @if ($quote->shipping_cents === 0 && !$quote->status->isTerminal())
                                <span class="text-amber-500">TBD</span>
                            @elseif ($quote->shipping_cents === 0)
                                <span class="text-green-600">Free</span>
                            @else
                                {{ format_currency($quote->shipping) }}
                            @endif
                        </span>
                    </div>

                    <div class="flex justify-between font-semibold border-t pt-2">
                        <span>Total</span>
                        <div class="text-right">
                            <span>{{ format_currency($quote->total) }}</span>
                            @if ($quote->shipping_cents === 0 && !$quote->status->isTerminal())
                                <p class="text-xs text-amber-500 font-normal">Excludes delivery</p>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                <div class="mt-6 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                    <flux:text class="text-sm text-amber-700">
                        <flux:icon name="clock" class="size-4 inline mr-1" />
                        Pricing is being prepared by our team. You'll be notified once your quotation is ready.
                    </flux:text>
                </div>
            @endif

            <flux:separator class="my-8" />

            {{-- ============================================================ --}}
            {{-- ACCEPT / REJECT ACTIONS                                        --}}
            {{-- ============================================================ --}}
            @if ($this->canRespond)
                <div class="flex flex-col sm:flex-row items-center gap-3 p-4 bg-zinc-50 border border-zinc-200 rounded-lg mb-8">
                    <div class="flex-1 text-sm">
                        <p class="font-medium text-zinc-800">Ready to decide?</p>
                        <p class="text-zinc-500 mt-0.5">
                            Accept to proceed to payment, or reject if you'd like to pass on this quote.
                        </p>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                        <flux:modal.trigger name="reject-quote">
                            <flux:button variant="ghost" icon="x-circle" class="cursor-pointer text-red-500!">
                                Reject
                            </flux:button>
                        </flux:modal.trigger>

                        <flux:modal.trigger name="accept-quote">
                            <flux:button variant="primary" icon="check-circle" class="cursor-pointer">
                                Accept Quote
                            </flux:button>
                        </flux:modal.trigger>
                    </div>
                </div>
            @endif

            {{-- ============================================================ --}}
            {{-- TIMELINE                                                       --}}
            {{-- ============================================================ --}}
            <flux:heading class="text-lg mb-4">Quotation History</flux:heading>

            @php
                $mainPath = [
                    QuoteStatus::PENDING,
                    QuoteStatus::SENT,
                    QuoteStatus::ACCEPTED,
                ];

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
                            'bg-zinc-100 dark:bg-zinc-800 text-zinc-300 dark:text-zinc-600' => $dimmed,
                            'bg-zinc-100 dark:bg-zinc-800 text-zinc-400' => !$reached && !$dimmed,
                        ])>
                            <flux:icon name="{{ $step->icon() }}" class="size-4" />
                        </div>

                        <div class="flex-1 pt-1">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <flux:text @class([
                                        'text-sm',
                                        'font-semibold text-green-600 dark:text-green-400' => $isActive,
                                        'font-semibold text-zinc-900 dark:text-white' => $reached && !$isActive,
                                        'text-zinc-300 dark:text-zinc-600' => $dimmed,
                                        'text-zinc-400' => !$reached && !$dimmed,
                                    ])>
                                        {{ $meta['label'] }}
                                    </flux:text>
                                    <flux:text class="text-xs mt-0.5
                                        {{ $reached ? 'text-zinc-500' : 'text-zinc-300 dark:text-zinc-600' }}">
                                        {{ $reached ? $meta['desc'] : 'Pending' }}
                                    </flux:text>
                                </div>

                                @if ($history)
                                    <div class="text-right shrink-0">
                                        <flux:text class="text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                            {{ $history->created_at->format('M j, Y') }}
                                        </flux:text>
                                        <flux:text class="text-xs text-zinc-400 mt-0.5">
                                            {{ $history->created_at->format('g:i A') }}
                                        </flux:text>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach

                @if ($isRejected)
                    <div class="relative flex gap-5 pt-6">
                        <div class="absolute left-4 top-0 h-6 w-0.5 bg-zinc-300 z-0"></div>
                        <div class="relative z-10 shrink-0 w-8 h-8 rounded-full flex items-center justify-center bg-red-100 text-red-500">
                            <flux:icon name="{{ QuoteStatus::REJECTED->icon() }}" class="size-4" />
                        </div>
                        <div class="flex-1 pt-1">
                            <flux:text class="text-sm font-semibold text-red-600">You rejected this quote</flux:text>
                            <flux:text class="text-xs text-zinc-500 mt-0.5">
                                Contact us if you'd like a revised quotation.
                            </flux:text>
                        </div>
                    </div>
                @endif

                @if ($isExpiredS)
                    <div class="relative flex gap-5 pt-6">
                        <div class="absolute left-4 top-0 h-6 w-0.5 bg-zinc-300 z-0"></div>
                        <div class="relative z-10 shrink-0 w-8 h-8 rounded-full flex items-center justify-center bg-zinc-100 text-zinc-400">
                            <flux:icon name="{{ QuoteStatus::EXPIRED->icon() }}" class="size-4" />
                        </div>
                        <div class="flex-1 pt-1">
                            <flux:text class="text-sm font-semibold text-zinc-500">Quote expired</flux:text>
                            <flux:text class="text-xs text-zinc-400 mt-0.5">
                                The validity period ended without a response.
                            </flux:text>
                        </div>
                    </div>
                @endif

                @if ($isCancelled)
                    <div class="relative flex gap-5 pt-6">
                        <div class="absolute left-4 top-0 h-6 w-0.5 bg-zinc-300 z-0"></div>
                        <div class="relative z-10 shrink-0 w-8 h-8 rounded-full flex items-center justify-center bg-rose-100 text-rose-500">
                            <flux:icon name="{{ QuoteStatus::CANCELLED->icon() }}" class="size-4" />
                        </div>
                        <div class="flex-1 pt-1">
                            <flux:text class="text-sm font-semibold text-rose-600">Quotation cancelled</flux:text>
                            <flux:text class="text-xs text-zinc-400 mt-0.5">
                                This quotation was cancelled. Please contact us for assistance.
                            </flux:text>
                        </div>
                    </div>
                @endif
            </div>

            <flux:separator class="my-8" />

            {{-- Footer --}}
            <div class="flex flex-col items-center gap-3">
                @if ($quote->quoted_at)
                    <flux:button variant="ghost" icon="arrow-down-tray" size="sm" class="cursor-pointer"
                        :href="route('customer.quotations.pdf', $quote)" target="_blank">
                        Download Quotation PDF
                    </flux:button>
                @endif

                <flux:text>
                    Need help?
                    <flux:link>Contact Support</flux:link>
                </flux:text>
            </div>
        </section>
    </flux:card>


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

            <div class="p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg text-sm space-y-1.5">
                <div class="flex justify-between">
                    <flux:text class="text-zinc-500">Reference</flux:text>
                    <flux:text class="font-medium">{{ $quote->reference }}</flux:text>
                </div>
                <div class="flex justify-between">
                    <flux:text class="text-zinc-500">Total</flux:text>
                    <flux:text class="font-medium">{{ format_currency($quote->total) }}</flux:text>
                </div>
                @if ($quote->expires_at)
                    <div class="flex justify-between">
                        <flux:text class="text-zinc-500">Valid until</flux:text>
                        <flux:text class="font-medium">{{ $quote->expires_at->format('M d, Y') }}</flux:text>
                    </div>
                @endif
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost" class="cursor-pointer">Cancel</flux:button>
                </flux:modal.close>
                <flux:button wire:click="acceptQuote" variant="primary" icon="check-circle" class="cursor-pointer">
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
                    <flux:button variant="ghost" class="cursor-pointer">Keep</flux:button>
                </flux:modal.close>
                <flux:button wire:click="rejectQuote" variant="danger" class="cursor-pointer">
                    Reject Quotation
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
