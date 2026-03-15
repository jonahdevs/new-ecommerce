<?php

use App\Enums\OrdersStatus;
use App\Models\Order;
use App\Services\QuotationService;
use Livewire\Attributes\{Computed, Layout, Title};
use Livewire\Component;

new #[Title('Quotation Details')] #[Layout('layouts.customer')] class extends Component {
    public Order $order;

    // Rejection reason — optional, shown when customer clicks Reject
    public string $rejectNote = '';

    // =========================================================================
    //  MOUNT
    //
    //  Guards:
    //    - Quotation must belong to the authenticated customer
    //    - Must be a quotation document (not a sales order)
    // =========================================================================

    public function mount(Order $order): void
    {
        // Ensure the quotation belongs to this customer
        if ($order->user_id !== auth()->id() || !$order->isQuotation()) {
            $this->redirectRoute('customer.quotations.index', navigate: true);
            return;
        }

        $this->order = $order->load([
            'items.product',
            'statusHistories',
            'convertedOrder', // the sales order created after acceptance
        ]);
    }

    // =========================================================================
    //  COMPUTED — UI STATE HELPERS
    // =========================================================================

    // Customer can only respond when the quote has been sent by admin
    #[Computed]
    public function canRespond(): bool
    {
        return $this->order->status === OrdersStatus::QUOTE_SENT && !$this->order->expires_at?->isPast();
    }

    // Whether the quote has expired without a response
    #[Computed]
    public function isExpired(): bool
    {
        return $this->order->status === OrdersStatus::QUOTE_EXPIRED || ($this->order->status === OrdersStatus::QUOTE_SENT && $this->order->expires_at?->isPast());
    }

    // =========================================================================
    //  ACCEPT QUOTE
    //
    //  Delegates to QuotationService::accept() which:
    //    1. Transitions quotation → QUOTE_ACCEPTED
    //    2. Creates a new sales order (convertToSalesOrder)
    //    3. Notifies admin
    //
    //  After acceptance the customer is routed to the payment page
    //  for the new sales order — same /checkout/pay/{order} flow.
    // =========================================================================

    public function acceptQuote(): void
    {
        if (!$this->canRespond) {
            $this->dispatch('notify', variant: 'danger', message: 'This quotation is no longer available to accept.');
            return;
        }

        try {
            $salesOrder = app(QuotationService::class)->accept($this->order);

            $this->dispatch('notify', variant: 'success', message: 'Quotation accepted! Redirecting to payment...');

            // Route directly to the payment page for the new sales order
            $this->redirectRoute('checkout.pay', $salesOrder->reference, navigate: true);
        } catch (\Throwable $e) {
            logger()->error('Customer failed to accept quotation.', [
                'quotation_id' => $this->order->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('notify', variant: 'danger', message: 'Something went wrong. Please try again or contact support.');
        }
    }

    // =========================================================================
    //  REJECT QUOTE
    //
    //  Delegates to QuotationService::reject() which:
    //    1. Transitions quotation → QUOTE_REJECTED
    //    2. Notifies admin
    //
    //  The rejection note is optional — the customer may explain why.
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
            app(QuotationService::class)->reject($this->order, $this->rejectNote ?: null);

            $this->order->refresh();
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
        <div class="flex items-center gap-3 px-3 py-2 border-b">
            <flux:button size="xs" icon="arrow-long-left" variant="ghost" class="cursor-pointer"
                :href="route('customer.quotations.index')" wire:navigate />


            <flux:heading size="lg">Quotation Details</flux:heading>

            {{-- Download button — only shown once admin has sent the quote --}}
            @if ($order->quoted_at)
                <flux:button size="xs" variant="ghost" icon="arrow-down-tray" class="cursor-pointer"
                    :href="route('customer.quotations.pdf', $order)" target="_blank">
                    PDF
                </flux:button>
            @endif

            <flux:badge :color="$order->status->color()" variant="solid" size="sm" class="ml-auto">
                {{ $order->status->label() }}
            </flux:badge>
        </div>

        <section class="p-5">

            {{-- ============================================================ --}}
            {{-- CONTEXT BANNERS                                               --}}
            {{-- ============================================================ --}}

            {{-- Quote ready — needs customer response --}}
            @if ($this->canRespond)
                <div class="flex items-start gap-3 p-4 bg-amber-50 border border-amber-200 rounded-lg mb-5">
                    <flux:icon.clock class="size-5 shrink-0 mt-0.5 text-amber-500" />
                    <div class="text-sm flex-1">
                        <p class="font-medium text-amber-800">Your quotation is ready for review</p>
                        <p class="text-amber-700 mt-0.5">
                            Sheffield Africa has priced your request.
                            Please review the details below and accept or reject before
                            @if ($order->expires_at)
                                <strong>{{ $order->expires_at->format('M d, Y') }}</strong>.
                            @else
                                the validity period ends.
                            @endif
                        </p>
                    </div>
                </div>
            @endif

            {{-- Expired --}}
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

            {{-- Converted — show link to the resulting sales order --}}
            @if ($order->status === \App\Enums\OrdersStatus::QUOTE_ACCEPTED && $order->convertedOrder)
                <div class="flex items-start gap-3 p-4 bg-teal-50 border border-teal-200 rounded-lg mb-5">
                    <flux:icon.check-circle class="size-5 shrink-0 mt-0.5 text-teal-500" />
                    <div class="text-sm flex items-center justify-between w-full">
                        <div>
                            <p class="font-medium text-teal-800">You accepted this quotation</p>
                            <p class="text-teal-700 mt-0.5">
                                A sales order has been created: {{ $order->convertedOrder->reference }}
                            </p>
                        </div>
                        <flux:button size="sm" variant="ghost"
                            :href="route('customer.orders.show', $order->convertedOrder)" wire:navigate>
                            View Order
                        </flux:button>
                    </div>
                </div>
            @endif

            {{-- Rejected --}}
            @if ($order->status === \App\Enums\OrdersStatus::QUOTE_REJECTED)
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
            {{-- ORDER META                                                     --}}
            {{-- ============================================================ --}}
            <div class="space-y-1 mb-5">
                <flux:heading>{{ $order->reference }}</flux:heading>
                <flux:text>
                    {{ $order->items_count ?? $order->items->count() }}
                    {{ Str::plural('item', $order->items->count()) }}
                </flux:text>
                <flux:text>Submitted on {{ $order->created_at->format('M j, Y') }}</flux:text>
                @if ($order->quoted_at)
                    <flux:text>Quoted on {{ $order->quoted_at->format('M j, Y') }}</flux:text>
                @endif
                @if ($order->expires_at && $this->canRespond)
                    <flux:text
                        class="{{ $order->expires_at->diffInHours() <= 48 ? 'text-amber-600' : 'text-zinc-500' }}">
                        Valid until {{ $order->expires_at->format('M j, Y') }}
                        ({{ $order->expires_at->diffForHumans() }})
                    </flux:text>
                @endif
            </div>

            <flux:separator class="my-5" />

            {{-- ============================================================ --}}
            {{-- ITEMS                                                          --}}
            {{-- ============================================================ --}}
            <flux:heading class="text-lg mb-4">Items in Your Quotation</flux:heading>

            <div class="space-y-4">
                @foreach ($order->items as $item)
                    @php
                        $name = $item->product_snapshot['name'] ?? ($item->product?->name ?? '—');
                        $sku = $item->product_snapshot['sku'] ?? null;
                        $imagePath = $item->product_image_url ?? $item->product?->image_url;
                    @endphp

                    <div class="border rounded-md p-4">
                        <div class="flex gap-4">

                            {{-- Image --}}
                            <div class="shrink-0">
                                @if ($imagePath)
                                    <img src="{{ asset($imagePath) }}" alt="{{ $name }}"
                                        class="w-20 h-20 object-contain rounded" />
                                @else
                                    <div class="w-20 h-20 bg-zinc-100 rounded flex items-center justify-center">
                                        <flux:icon.photo class="w-8 h-8 text-zinc-300" />
                                    </div>
                                @endif
                            </div>

                            {{-- Details --}}
                            <div class="flex-1">
                                <flux:heading size="sm">{{ $name }}</flux:heading>
                                @if ($sku)
                                    <flux:text size="sm" class="text-zinc-400">SKU: {{ $sku }}
                                    </flux:text>
                                @endif
                                <flux:text size="sm" class="text-zinc-500 mt-1">
                                    {{ $item->quantity }} × {{ format_currency($item->unit_price_cents / 100) }}
                                </flux:text>
                                <flux:text size="sm" class="font-semibold mt-1">
                                    {{ format_currency($item->total_cents / 100) }}
                                </flux:text>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- ============================================================ --}}
            {{-- TOTALS                                                         --}}
            {{-- ============================================================ --}}
            <div class="mt-6 space-y-1.5 max-w-xs ml-auto">
                <div class="flex justify-between text-sm">
                    <flux:text>Subtotal</flux:text>
                    <span>{{ format_currency($order->subtotal) }}</span>
                </div>

                @if ($order->discount > 0)
                    <div class="flex justify-between text-sm text-green-600">
                        <span>Discount</span>
                        <span>− {{ format_currency($order->discount) }}</span>
                    </div>
                @endif

                <div class="flex justify-between text-sm">
                    <flux:text>Delivery</flux:text>
                    <span>
                        @if ($order->shipping_cents === 0 && !$order->status->isTerminal())
                            <span class="text-amber-500">TBD</span>
                        @elseif ($order->shipping_cents === 0)
                            <span class="text-green-600">Free</span>
                        @else
                            {{ format_currency($order->shipping) }}
                        @endif
                    </span>
                </div>

                <div class="flex justify-between font-semibold border-t pt-2">
                    <span>Total</span>
                    <div class="text-right">
                        <span>{{ format_currency($order->total) }}</span>
                        @if ($order->shipping_cents === 0 && !$order->status->isTerminal())
                            <p class="text-xs text-amber-500 font-normal">Excludes delivery</p>
                        @endif
                    </div>
                </div>
            </div>

            <flux:separator class="my-8" />

            {{-- ============================================================ --}}
            {{-- ACCEPT / REJECT ACTIONS                                        --}}
            {{-- Only shown when the quote is in QUOTE_SENT and not expired    --}}
            {{-- ============================================================ --}}
            @if ($this->canRespond)
                <div
                    class="flex flex-col sm:flex-row items-center gap-3 p-4 bg-zinc-50 border border-zinc-200 rounded-lg mb-8">
                    <div class="flex-1 text-sm">
                        <p class="font-medium text-zinc-800">Ready to decide?</p>
                        <p class="text-zinc-500 mt-0.5">
                            Accept to proceed to payment, or reject if you'd like to pass on this quote.
                        </p>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                        {{-- Reject --}}
                        <flux:modal.trigger name="reject-quote">
                            <flux:button variant="ghost" icon="x-circle" class="cursor-pointer text-red-500!">
                                Reject
                            </flux:button>
                        </flux:modal.trigger>

                        {{-- Accept --}}
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
                    \App\Enums\OrdersStatus::PENDING_QUOTE,
                    \App\Enums\OrdersStatus::QUOTE_SENT,
                    \App\Enums\OrdersStatus::QUOTE_ACCEPTED,
                ];

                $isCancelled = $order->status === \App\Enums\OrdersStatus::CANCELLED;
                $isRejected = $order->status === \App\Enums\OrdersStatus::QUOTE_REJECTED;
                $isExpiredS = $order->status === \App\Enums\OrdersStatus::QUOTE_EXPIRED;
                $isTerminal = $isCancelled || $isRejected || $isExpiredS;
                $histories = $order->statusHistories->keyBy('to_status');

                // Customer-friendly labels
                $stepLabels = [
                    'pending_quote' => ['label' => 'Quote Requested', 'desc' => 'Your quote request was submitted.'],
                    'quote_sent' => ['label' => 'Quote Ready', 'desc' => 'Sheffield Africa has priced your request.'],
                    'quote_accepted' => ['label' => 'Quote Accepted', 'desc' => 'You accepted this quotation.'],
                ];
            @endphp

            <div class="relative">
                @foreach ($mainPath as $index => $step)
                    @php
                        $history = $histories->get($step->value);
                        $reached = (bool) $history;
                        $isLast = $index === count($mainPath) - 1;
                        $next = $mainPath[$index + 1] ?? null;
                        $nextReached = $next && $histories->has($next->value);
                        $dimmed = $isTerminal && !$reached;
                        $meta = $stepLabels[$step->value];
                    @endphp

                    <div class="relative flex gap-5 {{ $isLast ? 'pb-0' : 'pb-8' }}">

                        @if (!$isLast)
                            <div
                                class="absolute left-4 top-8 bottom-0 w-0.5 z-0
                                {{ $nextReached ? 'bg-zinc-900 dark:bg-white' : 'bg-zinc-200 dark:bg-zinc-700' }}">
                            </div>
                        @endif

                        <div
                            class="relative z-10 shrink-0 w-8 h-8 rounded-full flex items-center justify-center transition-colors
                            {{ $reached
                                ? 'bg-zinc-900 dark:bg-white text-white dark:text-zinc-900'
                                : ($dimmed
                                    ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-300'
                                    : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-400') }}">
                            <flux:icon name="{{ $step->icon() }}" class="size-4" />
                        </div>

                        <div class="flex-1 pt-1">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <flux:text
                                        class="text-sm font-semibold
                                        {{ $reached ? 'text-zinc-900 dark:text-white' : ($dimmed ? 'text-zinc-300' : 'text-zinc-400') }}">
                                        {{ $meta['label'] }}
                                    </flux:text>
                                    <flux:text
                                        class="text-xs mt-0.5
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

                {{-- Branch: Rejected --}}
                @if ($isRejected)
                    <div class="relative flex gap-5 pt-6">
                        <div class="absolute left-4 top-0 h-6 w-0.5 bg-zinc-300 z-0"></div>
                        <div
                            class="relative z-10 shrink-0 w-8 h-8 rounded-full flex items-center justify-center bg-red-100 text-red-500">
                            <flux:icon name="{{ \App\Enums\OrdersStatus::QUOTE_REJECTED->icon() }}" class="size-4" />
                        </div>
                        <div class="flex-1 pt-1">
                            <flux:text class="text-sm font-semibold text-red-600">You rejected this quote</flux:text>
                            <flux:text class="text-xs text-zinc-500 mt-0.5">
                                Contact us if you'd like a revised quotation.
                            </flux:text>
                        </div>
                    </div>
                @endif

                {{-- Branch: Expired --}}
                @if ($isExpiredS)
                    <div class="relative flex gap-5 pt-6">
                        <div class="absolute left-4 top-0 h-6 w-0.5 bg-zinc-300 z-0"></div>
                        <div
                            class="relative z-10 shrink-0 w-8 h-8 rounded-full flex items-center justify-center bg-zinc-100 text-zinc-400">
                            <flux:icon name="{{ \App\Enums\OrdersStatus::QUOTE_EXPIRED->icon() }}" class="size-4" />
                        </div>
                        <div class="flex-1 pt-1">
                            <flux:text class="text-sm font-semibold text-zinc-500">Quote expired</flux:text>
                            <flux:text class="text-xs text-zinc-400 mt-0.5">
                                The validity period ended without a response.
                            </flux:text>
                        </div>
                    </div>
                @endif

                {{-- Branch: Cancelled --}}
                @if ($isCancelled)
                    <div class="relative flex gap-5 pt-6">
                        <div class="absolute left-4 top-0 h-6 w-0.5 bg-zinc-300 z-0"></div>
                        <div
                            class="relative z-10 shrink-0 w-8 h-8 rounded-full flex items-center justify-center bg-rose-100 text-rose-500">
                            <flux:icon name="{{ \App\Enums\OrdersStatus::CANCELLED->icon() }}" class="size-4" />
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
                {{-- Download quotation PDF — available once quote has been sent --}}
                @if ($order->quoted_at)
                    <flux:button variant="ghost" icon="arrow-down-tray" size="sm" class="cursor-pointer"
                        :href="route('customer.quotations.pdf', $order)" target="_blank">
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

            {{-- Summary --}}
            <div class="p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg text-sm space-y-1.5">
                <div class="flex justify-between">
                    <flux:text class="text-zinc-500">Reference</flux:text>
                    <flux:text class="font-medium">{{ $order->reference }}</flux:text>
                </div>
                <div class="flex justify-between">
                    <flux:text class="text-zinc-500">Total</flux:text>
                    <flux:text class="font-medium">{{ format_currency($order->total) }}</flux:text>
                </div>
                @if ($order->expires_at)
                    <div class="flex justify-between">
                        <flux:text class="text-zinc-500">Valid until</flux:text>
                        <flux:text class="font-medium">{{ $order->expires_at->format('M d, Y') }}</flux:text>
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
