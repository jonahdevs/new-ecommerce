<?php

use App\Enums\OrdersStatus;
use App\Models\Order;
use App\Services\{QuotationService, DocumentService};
use Livewire\Attributes\{Computed, Title};
use Livewire\Component;

new #[Title('Quotation Details')] class extends Component {
    public Order $order;

    //  Price & Send form fields
    public string $quotedShipping = '';
    public int $validityDays = 7;
    public string $note = '';

    // For product quotations — admin can override unit prices per item.
    // Keyed by order_item.id → new unit price in KES (string for input binding).
    public array $itemPrices = [];

    //  Cancel form
    public string $cancelNote = '';

    // =========================================================================
    //  MOUNT
    // =========================================================================

    public function mount(Order $order): void
    {
        // Guard: this page is only for quotation documents
        if (!$order->isQuotation()) {
            $this->redirectRoute('admin.orders.show', $order, navigate: true);
            return;
        }

        $this->order = $order->load(['user', 'items.product', 'statusHistories.changedBy', 'convertedOrder']);

        // Pre-populate item prices for product quotations so the pricing
        // form shows the current catalogue price rather than a blank field.
        if ($order->isProductQuotation()) {
            foreach ($order->items as $item) {
                $this->itemPrices[$item->id] = number_format($item->unit_price_cents / 100, 2, '.', '');
            }
        }
    }

    // =========================================================================
    //  COMPUTED — UI state helpers
    // =========================================================================

    // Show the Price & Send form only when awaiting admin pricing
    #[Computed]
    public function canPrice(): bool
    {
        return $this->order->status === OrdersStatus::PENDING_QUOTE;
    }

    // Show the Convert button only after customer acceptance (and only once)
    #[Computed]
    public function canConvert(): bool
    {
        return $this->order->status === OrdersStatus::QUOTE_ACCEPTED && !$this->order->hasBeenConverted();
    }

    // Show Cancel button for any non-terminal quotation
    #[Computed]
    public function canCancel(): bool
    {
        return $this->order->status->canTransitionTo(OrdersStatus::CANCELLED);
    }

    // Live total preview — recalculates as admin types in the pricing form.
    // For delivery quotes: existing subtotal + quoted shipping.
    // For product quotes: sum of overridden item prices + quoted shipping.
    #[Computed]
    public function quotedTotal(): float
    {
        $shipping = (float) str_replace(',', '', $this->quotedShipping ?? '0');

        if ($this->order->isProductQuotation() && !empty($this->itemPrices)) {
            $itemsTotal = 0;
            foreach ($this->itemPrices as $itemId => $price) {
                $item = $this->order->items->firstWhere('id', (int) $itemId);
                $itemsTotal += (float) str_replace(',', '', $price ?? '0') * ($item?->quantity ?? 1);
            }
            return max(0, $itemsTotal - $this->order->discount_cents / 100 + $shipping);
        }

        return max(0, $this->order->subtotal + $shipping - $this->order->discount);
    }

    // =========================================================================
    //  PRICE & SEND QUOTE
    //
    //  Validates the form, builds the pricing payload, and delegates
    //  all business logic to QuotationService::send().
    //  The service handles DB transactions, financial updates, status
    //  transitions, and customer notification.
    // =========================================================================

    public function sendQuote(): void
    {
        $this->validate([
            'quotedShipping' => ['required', 'numeric', 'min:0'],
            'validityDays' => ['required', 'integer', 'min:1', 'max:90'],
            'note' => ['nullable', 'string', 'max:1000'],
            'itemPrices.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        if (!$this->canPrice) {
            $this->dispatch('notify', variant: 'danger', message: 'This quotation can no longer be priced.');
            return;
        }

        try {
            app(QuotationService::class)->send($this->order, [
                'shipping' => $this->quotedShipping,
                'validity_days' => $this->validityDays,
                'note' => $this->note ?: null,
                'item_prices' => $this->itemPrices,
            ]);

            $this->order->refresh();
            $this->note = '';
            $this->modal('price-quote')->close();
            $this->dispatch('notify', variant: 'success', message: 'Quotation sent to customer.');
        } catch (\Throwable $e) {
            logger()->error('Failed to send quotation.', [
                'order_id' => $this->order->id,
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('notify', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }

    // =========================================================================
    //  CONVERT TO SALES ORDER
    //
    //  Delegates to QuotationService::accept() which handles the transition
    //  and conversion in a single transaction, then notifies admin.
    //  Redirects to the new sales order show page on success.
    // =========================================================================

    public function convertToSalesOrder(): void
    {
        if (!$this->canConvert) {
            $this->dispatch('notify', variant: 'danger', message: 'This quotation cannot be converted.');
            return;
        }

        try {
            $salesOrder = app(QuotationService::class)->accept($this->order);

            $this->dispatch('notify', variant: 'success', message: "Sales order {$salesOrder->reference} created.");
            $this->redirectRoute('admin.orders.show', $salesOrder, navigate: true);
        } catch (\Throwable $e) {
            logger()->error('Failed to convert quotation.', [
                'quotation_id' => $this->order->id,
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('notify', variant: 'danger', message: $e->getMessage());
        }
    }

    // =========================================================================
    //  CANCEL QUOTATION
    //
    //  Delegates to QuotationService::cancel().
    //  No customer notification — admin handles communication directly.
    // =========================================================================

    public function cancelQuotation(): void
    {
        $this->validate([
            'cancelNote' => ['nullable', 'string', 'max:1000'],
        ]);

        if (!$this->canCancel) {
            $this->dispatch('notify', variant: 'danger', message: 'This quotation cannot be cancelled.');
            return;
        }

        try {
            app(QuotationService::class)->cancel($this->order, $this->cancelNote ?: null);

            $this->order->refresh();
            $this->cancelNote = '';
            $this->modal('cancel-quote')->close();
            $this->dispatch('notify', variant: 'warning', message: 'Quotation cancelled.');
        } catch (\Throwable $e) {
            $this->dispatch('notify', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }

    // =========================================================================
    //  DOWNLOAD QUOTATION PDF
    //
    //  Admin-side download — bypasses the customer ownership check
    //  on QuotationPdfController (which requires auth()->id() === order->user_id).
    //
    //  If the PDF hasn't been generated yet (e.g. DocumentService failed silently
    //  when the quote was sent), it generates it on the fly before serving.
    // =========================================================================

    public function downloadPdf(): mixed
    {
        $order = $this->order;

        // Generate if not yet stored
        if (!$order->quotation_pdf_path) {
            $path = app(DocumentService::class)->generateQuotation($order);

            if (!$path) {
                $this->dispatch('notify', variant: 'danger', message: 'Unable to generate PDF. Please try again.');
                return null;
            }

            $order->refresh();
        }

        $response = app(DocumentService::class)->serve($order->quotation_pdf_path, 'Quotation');

        if (!$response) {
            // File missing on disk — regenerate
            $path = app(DocumentService::class)->generateQuotation($order);

            if (!$path) {
                $this->dispatch('notify', variant: 'danger', message: 'PDF not found. Please try again.');
                return null;
            }

            $order->refresh();

            return app(DocumentService::class)->serve($order->quotation_pdf_path, 'Quotation');
        }

        return $response;
    }
};
?>

<div>

    {{-- ================================================================== --}}
    {{-- PAGE HEADER                                                         --}}
    {{-- ================================================================== --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <flux:breadcrumbs class="mb-2">
                <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline"
                    wire:navigate />
                <flux:breadcrumbs.item :href="route('admin.orders.index')" wire:navigate>Orders</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $order->reference }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            <div class="flex items-center gap-3 flex-wrap">
                <flux:heading size="xl" class="font-bold tracking-tight">
                    {{ $order->reference }}
                </flux:heading>
                <flux:badge :color="$order->status->color()" variant="solid" size="sm"
                    class="uppercase text-[10px] tracking-widest font-bold">
                    {{ $order->status->label() }}
                </flux:badge>
                @if ($order->quotation_type === 'delivery')
                    <flux:badge color="indigo" variant="flat" size="sm" icon="truck">Delivery Quote</flux:badge>
                @elseif ($order->quotation_type === 'product')
                    <flux:badge color="purple" variant="flat" size="sm" icon="tag">Product Quote</flux:badge>
                @endif
            </div>

            <flux:text class="mt-1 flex items-center gap-2">
                <flux:icon name="calendar" class="size-4 text-zinc-400" />
                Submitted on {{ $order->created_at->format('M d, Y') }} at {{ $order->created_at->format('g:i A') }}
            </flux:text>
        </div>

        {{-- Primary actions — only show what's relevant to the current status --}}
        <div class="flex items-center gap-3 flex-wrap">

            <flux:button variant="outline" icon="document-text" size="sm" wire:click="downloadPdf"
                class="cursor-pointer">
                <span wire:loading.remove wire:target="downloadPdf">Quotation PDF</span>
                <span wire:loading wire:target="downloadPdf">Generating...</span>
            </flux:button>

            @if ($this->canConvert)
                <flux:modal.trigger name="convert-quote">
                    <flux:button size="sm" variant="primary" icon="arrow-right-circle" class="cursor-pointer">
                        Convert to Sales Order
                    </flux:button>
                </flux:modal.trigger>
            @endif

            @if ($this->canPrice)
                <flux:modal.trigger name="price-quote">
                    <flux:button size="sm" variant="primary" icon="pencil-square" class="cursor-pointer">
                        Price & Send Quote
                    </flux:button>
                </flux:modal.trigger>
            @endif

            @if ($this->canCancel)
                <flux:modal.trigger name="cancel-quote">
                    <flux:button size="sm" variant="ghost" icon="x-circle" class="text-red-500! cursor-pointer">
                        Cancel
                    </flux:button>
                </flux:modal.trigger>
            @endif

        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- CONTEXT ALERTS                                                      --}}
    {{-- ================================================================== --}}

    {{-- Awaiting admin pricing --}}
    @if ($order->status === \App\Enums\OrdersStatus::PENDING_QUOTE)
        <div class="flex items-start gap-3 p-4 bg-amber-50 border border-amber-200 rounded-lg mb-5">
            <flux:icon.clock class="size-5 shrink-0 mt-0.5 text-amber-500" />
            <div class="text-sm">
                <p class="font-medium text-amber-800">This quotation is awaiting your pricing</p>
                <p class="text-amber-700 mt-0.5">
                    Review the items and delivery address below, then click
                    <strong>Price & Send Quote</strong> to notify the customer.
                </p>
            </div>
        </div>
    @endif

    {{-- Expiring soon --}}
    @if (
        $order->status === \App\Enums\OrdersStatus::QUOTE_SENT &&
            $order->expires_at?->diffInHours(now()) <= 48 &&
            !$order->expires_at?->isPast())
        <div class="flex items-start gap-3 p-4 bg-rose-50 border border-rose-200 rounded-lg mb-5">
            <flux:icon.exclamation-triangle class="size-5 shrink-0 mt-0.5 text-rose-500" />
            <div class="text-sm">
                <p class="font-medium text-rose-800">
                    This quotation expires {{ $order->expires_at->diffForHumans() }}
                </p>
                <p class="text-rose-700 mt-0.5">Follow up with the customer to ensure they have seen the quote.</p>
            </div>
        </div>
    @endif

    {{-- Converted to sales order --}}
    @if ($order->hasBeenConverted())
        <div class="flex items-start gap-3 p-4 bg-teal-50 border border-teal-200 rounded-lg mb-5">
            <flux:icon.check-circle class="size-5 shrink-0 mt-0.5 text-teal-500" />
            <div class="text-sm flex items-center justify-between w-full">
                <div>
                    <p class="font-medium text-teal-800">Converted to a sales order</p>
                    <p class="text-teal-700 mt-0.5">Reference: {{ $order->convertedOrder->reference }}</p>
                </div>
                <flux:button size="sm" variant="ghost" icon="arrow-top-right-on-square"
                    :href="route('admin.orders.show', $order->convertedOrder)" wire:navigate>
                    View Order
                </flux:button>
            </div>
        </div>
    @endif

    {{-- ================================================================== --}}
    {{-- MAIN LAYOUT                                                         --}}
    {{-- ================================================================== --}}
    <div class="grid grid-cols-4 gap-5 mt-6">

        {{-- ── Left: Main content (3 cols) ── --}}
        <div class="col-span-3 space-y-5">

            {{-- Items table --}}
            <flux:card class="p-0">
                <div class="px-6 py-2 border-b flex justify-between items-center">
                    <flux:heading level="3" class="font-semibold">Items</flux:heading>
                    <flux:badge variant="outline">{{ $order->items->sum('quantity') }} items</flux:badge>
                </div>

                <flux:table>
                    <flux:table.columns>
                        <flux:table.column class="ps-6!">Product</flux:table.column>
                        <flux:table.column>SKU</flux:table.column>
                        <flux:table.column>Qty</flux:table.column>
                        <flux:table.column>Unit Price</flux:table.column>
                        <flux:table.column>Discount</flux:table.column>
                        <flux:table.column>Total</flux:table.column>
                        @if ($order->isProductQuotation())
                            <flux:table.column></flux:table.column>
                        @endif
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($order->items as $item)
                            @php
                                $name = $item->product_snapshot['name'] ?? ($item->product?->name ?? '—');
                                $sku = $item->product_snapshot['sku'] ?? '—';
                                $imagePath = $item->product?->image_url;
                                $requiresQuote = $item->product_snapshot['requires_quotation'] ?? false;
                            @endphp
                            <flux:table.row :key="$item->id">
                                <flux:table.cell class="ps-6!">
                                    <div class="flex items-center gap-3">
                                        <div class="shrink-0 w-12 h-12 rounded border overflow-hidden bg-zinc-50">
                                            @if ($imagePath)
                                                <img src="{{ asset($imagePath) }}" alt="{{ $name }}"
                                                    class="w-full h-full object-cover" />
                                            @else
                                                <flux:icon name="photo" class="w-full h-full p-2 text-zinc-300" />
                                            @endif
                                        </div>
                                        <flux:text class="text-sm font-medium">{{ $name }}</flux:text>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:text class="text-xs text-zinc-400">{{ $sku }}</flux:text>
                                </flux:table.cell>
                                <flux:table.cell>{{ $item->quantity }}</flux:table.cell>
                                <flux:table.cell>{{ format_currency($item->unit_price_cents / 100) }}</flux:table.cell>
                                <flux:table.cell>{{ format_currency($item->discount_cents / 100) }}</flux:table.cell>
                                <flux:table.cell class="font-medium">{{ format_currency($item->total_cents / 100) }}
                                </flux:table.cell>
                                @if ($order->isProductQuotation())
                                    <flux:table.cell>
                                        @if ($requiresQuote)
                                            <flux:badge size="sm" color="amber" variant="flat">Quote required
                                            </flux:badge>
                                        @endif
                                    </flux:table.cell>
                                @endif
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="6" class="text-center py-8">
                                    <flux:text class="text-zinc-400">No items found.</flux:text>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>

                {{-- Totals panel --}}
                <div class="bg-zinc-50/50 dark:bg-white/2 border-t border-zinc-100 dark:border-zinc-800 p-6">
                    <div class="flex flex-col items-end">
                        <div class="w-full max-w-xs space-y-2">
                            <div class="flex justify-between text-sm">
                                <flux:text>Subtotal</flux:text>
                                <flux:text class="font-medium">{{ format_currency($order->subtotal) }}</flux:text>
                            </div>
                            @if ($order->discount > 0)
                                <div class="flex justify-between text-sm">
                                    <flux:text>Discount</flux:text>
                                    <flux:text class="font-medium text-green-600">
                                        − {{ format_currency($order->discount) }}
                                    </flux:text>
                                </div>
                            @endif
                            <div class="flex justify-between text-sm">
                                <flux:text>Shipping</flux:text>
                                <flux:text class="font-medium">
                                    @if ($order->shipping_cents === 0 && !$order->status->isTerminal())
                                        <span class="text-amber-500">TBD</span>
                                    @elseif ($order->shipping_cents === 0)
                                        <span class="text-green-600">Free</span>
                                    @else
                                        {{ format_currency($order->shipping) }}
                                    @endif
                                </flux:text>
                            </div>
                            <div class="flex justify-between pt-2 border-t border-zinc-200 dark:border-zinc-700">
                                <flux:heading size="lg">Total</flux:heading>
                                <div class="text-right">
                                    <flux:heading size="lg" class="font-bold">
                                        {{ format_currency($order->total) }}
                                    </flux:heading>
                                    @if ($order->shipping_cents === 0 && !$order->status->isTerminal())
                                        <p class="text-xs text-amber-500 font-normal">Excludes shipping</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </flux:card>

            {{-- Timeline --}}
            <flux:card class="p-0">
                <div class="px-5 py-3 border-b flex items-center justify-between">
                    <flux:heading>Quotation Timeline</flux:heading>
                    <flux:badge :color="$order->status->color()" variant="solid" size="sm">
                        {{ $order->status->label() }}
                    </flux:badge>
                </div>

                <div class="p-5">
                    @php
                        $mainPath = [
                            \App\Enums\OrdersStatus::PENDING_QUOTE,
                            \App\Enums\OrdersStatus::QUOTE_SENT,
                            \App\Enums\OrdersStatus::QUOTE_ACCEPTED,
                        ];
                        $isCancelled = $order->status === \App\Enums\OrdersStatus::CANCELLED;
                        $isRejected = $order->status === \App\Enums\OrdersStatus::QUOTE_REJECTED;
                        $isExpired = $order->status === \App\Enums\OrdersStatus::QUOTE_EXPIRED;
                        $isTerminal = $isCancelled || $isRejected || $isExpired;
                        $histories = $order->statusHistories->keyBy('to_status');
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
                            @endphp

                            <div class="relative flex gap-4 {{ $isLast ? 'pb-0' : 'pb-6' }}">
                                @if (!$isLast)
                                    <div
                                        class="absolute left-4 top-8 bottom-0 w-px
                                        {{ $nextReached ? 'bg-zinc-900 dark:bg-white' : 'bg-zinc-200 dark:bg-zinc-700' }} z-0">
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

                                <div class="flex-1 flex items-start justify-between gap-4 pt-1 min-w-0">
                                    <div class="min-w-0">
                                        <flux:text
                                            class="text-sm
                                            {{ $reached ? 'font-medium text-zinc-900 dark:text-white' : ($dimmed ? 'text-zinc-300' : 'text-zinc-400') }}">
                                            {{ $step->label() }}
                                        </flux:text>

                                        @if ($step === \App\Enums\OrdersStatus::QUOTE_SENT && $order->expires_at && $reached)
                                            <flux:text
                                                class="text-xs mt-0.5
                                                {{ $order->expires_at->isPast() ? 'text-rose-500' : 'text-zinc-400' }}">
                                                {{ $order->expires_at->isPast() ? 'Expired' : 'Expires' }}
                                                {{ $order->expires_at->diffForHumans() }}
                                                ({{ $order->expires_at->format('M d, Y') }})
                                            </flux:text>
                                        @endif

                                        @if ($history?->notes)
                                            <flux:text class="text-xs text-zinc-400 mt-0.5 leading-relaxed">
                                                {{ $history->notes }}
                                            </flux:text>
                                        @endif
                                    </div>

                                    @if ($history)
                                        <div class="text-right shrink-0">
                                            <flux:text class="text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                                {{ $history->created_at->format('M d, Y') }}
                                            </flux:text>
                                            <flux:text class="text-xs text-zinc-400 mt-0.5">
                                                {{ $history->created_at->format('g:i A') }}
                                            </flux:text>
                                            <flux:text class="text-xs text-zinc-400 italic mt-0.5">
                                                {{ $history->changedBy?->name ?? 'System' }}
                                            </flux:text>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach

                        {{-- Branch: Converted --}}
                        @if ($order->hasBeenConverted())
                            <div class="relative flex gap-4 pt-6">
                                <div class="absolute left-4 top-0 h-6 w-px bg-zinc-900 dark:bg-white z-0"></div>
                                <div
                                    class="relative z-10 shrink-0 w-8 h-8 rounded-full flex items-center justify-center bg-teal-600 text-white">
                                    <flux:icon name="arrow-right-circle" class="size-4" />
                                </div>
                                <div class="flex-1 pt-1">
                                    <flux:text class="text-sm font-medium text-teal-600">Converted to sales order
                                    </flux:text>
                                    <flux:link :href="route('admin.orders.show', $order->convertedOrder)" wire:navigate
                                        class="text-xs text-teal-500">
                                        {{ $order->convertedOrder->reference }} →
                                    </flux:link>
                                </div>
                            </div>
                        @endif

                        {{-- Branch: Rejected --}}
                        @if ($isRejected)
                            @php $h = $histories->get('quote_rejected'); @endphp
                            <div class="relative flex gap-4 pt-6">
                                <div class="absolute left-4 top-0 h-6 w-px bg-zinc-300 z-0"></div>
                                <div
                                    class="relative z-10 shrink-0 w-8 h-8 rounded-full flex items-center justify-center bg-red-100 dark:bg-red-950 text-red-600">
                                    <flux:icon name="{{ \App\Enums\OrdersStatus::QUOTE_REJECTED->icon() }}"
                                        class="size-4" />
                                </div>
                                <div class="flex-1 flex items-start justify-between gap-4 pt-1">
                                    <div>
                                        <flux:text class="text-sm font-medium text-red-600">Quote rejected by customer
                                        </flux:text>
                                        @if ($h?->notes)
                                            <flux:text class="text-xs text-zinc-400 mt-0.5">{{ $h->notes }}
                                            </flux:text>
                                        @endif
                                    </div>
                                    @if ($h)
                                        <div class="text-right shrink-0">
                                            <flux:text class="text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                                {{ $h->created_at->format('M d, Y') }}</flux:text>
                                            <flux:text class="text-xs text-zinc-400 mt-0.5">
                                                {{ $h->created_at->format('g:i A') }}</flux:text>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- Branch: Expired --}}
                        @if ($isExpired)
                            @php $h = $histories->get('quote_expired'); @endphp
                            <div class="relative flex gap-4 pt-6">
                                <div class="absolute left-4 top-0 h-6 w-px bg-zinc-300 z-0"></div>
                                <div
                                    class="relative z-10 shrink-0 w-8 h-8 rounded-full flex items-center justify-center bg-zinc-100 dark:bg-zinc-800 text-zinc-400">
                                    <flux:icon name="{{ \App\Enums\OrdersStatus::QUOTE_EXPIRED->icon() }}"
                                        class="size-4" />
                                </div>
                                <div class="flex-1 flex items-start justify-between gap-4 pt-1">
                                    <div>
                                        <flux:text class="text-sm font-medium text-zinc-500">Quote expired without
                                            response</flux:text>
                                        @if ($h?->notes)
                                            <flux:text class="text-xs text-zinc-400 mt-0.5">{{ $h->notes }}
                                            </flux:text>
                                        @endif
                                    </div>
                                    @if ($h)
                                        <div class="text-right shrink-0">
                                            <flux:text class="text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                                {{ $h->created_at->format('M d, Y') }}</flux:text>
                                            <flux:text class="text-xs text-zinc-400 mt-0.5">
                                                {{ $h->created_at->format('g:i A') }}</flux:text>
                                            <flux:text class="text-xs text-zinc-400 italic mt-0.5">System</flux:text>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- Branch: Cancelled --}}
                        @if ($isCancelled)
                            @php $h = $histories->get('cancelled'); @endphp
                            <div class="relative flex gap-4 pt-6">
                                <div class="absolute left-4 top-0 h-6 w-px bg-zinc-300 z-0"></div>
                                <div
                                    class="relative z-10 shrink-0 w-8 h-8 rounded-full flex items-center justify-center bg-rose-100 dark:bg-rose-950 text-rose-600">
                                    <flux:icon name="{{ \App\Enums\OrdersStatus::CANCELLED->icon() }}"
                                        class="size-4" />
                                </div>
                                <div class="flex-1 flex items-start justify-between gap-4 pt-1">
                                    <div>
                                        <flux:text class="text-sm font-medium text-rose-600">Quotation Cancelled
                                        </flux:text>
                                        @if ($h?->notes)
                                            <flux:text class="text-xs text-zinc-400 mt-0.5">{{ $h->notes }}
                                            </flux:text>
                                        @endif
                                    </div>
                                    @if ($h)
                                        <div class="text-right shrink-0">
                                            <flux:text class="text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                                {{ $h->created_at->format('M d, Y') }}</flux:text>
                                            <flux:text class="text-xs text-zinc-400 mt-0.5">
                                                {{ $h->created_at->format('g:i A') }}</flux:text>
                                            <flux:text class="text-xs text-zinc-400 italic mt-0.5">
                                                {{ $h->changedBy?->name ?? 'System' }}</flux:text>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                    </div>
                </div>
            </flux:card>

        </div>

        {{-- ── Right: Sidebar (1 col) ── --}}
        <div class="col-span-1 space-y-5">

            {{-- Customer --}}
            <flux:card class="p-0">
                <div class="px-5 py-3 border-b">
                    <flux:heading>Customer</flux:heading>
                </div>
                <div class="p-5 text-sm space-y-4">
                    <flux:card class="flex items-center gap-3 p-3 bg-zinc-50 dark:bg-zinc-800/50">
                        <div class="shrink-0">
                            @if ($order->user?->avatar)
                                <flux:avatar circle class="size-10" src="{{ $order->user->avatar }}" />
                            @else
                                <flux:avatar circle class="size-10" name="{{ $order->user?->name ?? 'U' }}" />
                            @endif
                        </div>
                        <div>
                            <flux:text class="font-medium">{{ $order->user?->name }}</flux:text>
                            <flux:link href="mailto:{{ $order->user?->email }}" class="text-xs">
                                {{ $order->user?->email }}
                            </flux:link>
                        </div>
                    </flux:card>

                    <div class="flex items-start gap-3">
                        <flux:icon name="phone" class="size-4 mt-0.5 text-zinc-400" />
                        <flux:text size="sm">{{ format_phone($order->user?->phone_number) ?? 'No phone' }}
                        </flux:text>
                    </div>

                    <div class="flex items-start gap-3">
                        <flux:icon name="map-pin" class="size-4 mt-0.5 text-zinc-400" />
                        <div>
                            <flux:text size="sm" class="font-medium block">Delivery Address</flux:text>
                            <flux:text size="xs" class="leading-relaxed">
                                {{ $order->shipping_address['address'] ?? 'N/A' }}<br>
                                {{ $order->shipping_address['area'] ?? '' }},
                                {{ $order->shipping_address['county'] ?? '' }}
                            </flux:text>
                            <flux:text size="xs" class="text-zinc-400 mt-1">
                                Zone: {{ $order->shipping_address['zone'] ?? '—' }}
                            </flux:text>
                        </div>
                    </div>
                </div>
            </flux:card>

            {{-- Quote Details --}}
            <flux:card class="p-0">
                <div class="px-5 py-3 border-b">
                    <flux:heading>Quote Details</flux:heading>
                </div>
                <div class="p-5 text-sm space-y-3">

                    <div class="flex justify-between items-center">
                        <flux:text class="text-zinc-500">Type</flux:text>
                        @if ($order->quotation_type === 'delivery')
                            <flux:badge size="sm" color="indigo" variant="flat">Delivery</flux:badge>
                        @else
                            <flux:badge size="sm" color="purple" variant="flat">Product</flux:badge>
                        @endif
                    </div>

                    <div class="flex justify-between">
                        <flux:text class="text-zinc-500">Submitted</flux:text>
                        <flux:text class="font-medium">{{ $order->created_at->format('M d, Y') }}</flux:text>
                    </div>

                    <div class="flex justify-between">
                        <flux:text class="text-zinc-500">Quoted</flux:text>
                        <flux:text class="font-medium">
                            {{ $order->quoted_at ? $order->quoted_at->format('M d, Y') : '—' }}
                        </flux:text>
                    </div>

                    <div class="flex justify-between">
                        <flux:text class="text-zinc-500">Expires</flux:text>
                        @if ($order->expires_at)
                            <flux:text @class([
                                'font-medium',
                                'text-rose-600' => $order->expires_at->isPast(),
                                'text-amber-600' =>
                                    !$order->expires_at->isPast() &&
                                    $order->expires_at->diffInHours() <= 48,
                            ])>
                                {{ $order->expires_at->format('M d, Y') }}
                            </flux:text>
                        @else
                            <flux:text class="text-zinc-400">Not set</flux:text>
                        @endif
                    </div>

                    @if ($order->quotation_type === 'delivery')
                        <div class="flex justify-between">
                            <flux:text class="text-zinc-500">Package weight</flux:text>
                            <flux:text class="font-medium">
                                {{ $order->shipping_snapshot['weight_kg'] ?? '—' }} kg
                            </flux:text>
                        </div>
                    @endif

                    @if ($order->hasBeenConverted())
                        <div class="pt-2 border-t border-zinc-100 dark:border-zinc-800">
                            <flux:text class="text-zinc-500 text-xs mb-1">Converted to</flux:text>
                            <flux:link :href="route('admin.orders.show', $order->convertedOrder)" wire:navigate
                                class="text-sm font-medium">
                                {{ $order->convertedOrder->reference }}
                                <flux:icon.arrow-top-right-on-square class="size-3 inline-block ms-1" />
                            </flux:link>
                        </div>
                    @endif

                </div>
            </flux:card>

        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- MODAL: Price & Send Quote                                           --}}
    {{-- ================================================================== --}}
    <flux:modal name="price-quote" class="w-full max-w-lg">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">Price & Send Quote</flux:heading>
                <flux:subheading>
                    @if ($order->isDeliveryQuotation())
                        Set the delivery cost. Product prices are fixed from the catalogue.
                    @else
                        Adjust item prices if needed, then set the delivery cost.
                    @endif
                </flux:subheading>
            </div>

            <form wire:submit="sendQuote" class="space-y-5">

                {{-- Product quotation: editable item prices --}}
                @if ($order->isProductQuotation())
                    <div>
                        <flux:text class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-3">
                            Item Prices (KES)
                        </flux:text>
                        <div class="space-y-2">
                            @foreach ($order->items as $item)
                                @php $name = $item->product_snapshot['name'] ?? ($item->product?->name ?? '—'); @endphp
                                <div class="flex items-center gap-3">
                                    <flux:text class="flex-1 text-sm truncate" title="{{ $name }}">
                                        {{ $name }}
                                        <span class="text-zinc-400">× {{ $item->quantity }}</span>
                                    </flux:text>
                                    <flux:input wire:model.live="itemPrices.{{ $item->id }}" type="number"
                                        step="0.01" min="0" class="w-32" placeholder="0.00" />
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <flux:input wire:model.live="quotedShipping" type="number" step="0.01" min="0"
                    label="Delivery cost (KES)" placeholder="e.g. 1500" description="Enter 0 if delivery is free" />

                <flux:input wire:model="validityDays" type="number" min="1" max="90"
                    label="Valid for (days)" description="Customer must accept before this expires" />

                {{-- Live total preview --}}
                <div class="flex justify-between items-center p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg text-sm">
                    <flux:text class="font-medium">Quoted total</flux:text>
                    <flux:text class="font-bold text-base">{{ format_currency($this->quotedTotal) }}</flux:text>
                </div>

                <flux:textarea wire:model="note" label="Note (optional)"
                    placeholder="Any notes for this quotation..." rows="3" />

                <div class="flex justify-end gap-3 pt-2">
                    <flux:modal.close>
                        <flux:button variant="ghost" class="cursor-pointer">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" class="cursor-pointer">
                        Send Quote to Customer
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- ================================================================== --}}
    {{-- MODAL: Convert to Sales Order                                       --}}
    {{-- ================================================================== --}}
    <flux:modal name="convert-quote" class="w-full max-w-md">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Convert to Sales Order</flux:heading>
                <flux:subheading>
                    Creates a new sales order and routes the customer to payment.
                    This cannot be undone.
                </flux:subheading>
            </div>
            <div class="p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg text-sm space-y-1.5">
                <div class="flex justify-between">
                    <flux:text class="text-zinc-500">Quotation</flux:text>
                    <flux:text class="font-medium">{{ $order->reference }}</flux:text>
                </div>
                <div class="flex justify-between">
                    <flux:text class="text-zinc-500">Customer</flux:text>
                    <flux:text class="font-medium">{{ $order->user?->name }}</flux:text>
                </div>
                <div class="flex justify-between">
                    <flux:text class="text-zinc-500">Total</flux:text>
                    <flux:text class="font-medium">{{ format_currency($order->total) }}</flux:text>
                </div>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost" class="cursor-pointer">Cancel</flux:button>
                </flux:modal.close>
                <flux:button wire:click="convertToSalesOrder" variant="primary" class="cursor-pointer">
                    Create Sales Order
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- ================================================================== --}}
    {{-- MODAL: Cancel Quotation                                             --}}
    {{-- ================================================================== --}}
    <flux:modal name="cancel-quote" class="w-full max-w-md">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Cancel Quotation</flux:heading>
                <flux:subheading>This quotation will be permanently cancelled.</flux:subheading>
            </div>
            <flux:textarea wire:model="cancelNote" label="Reason (optional)"
                placeholder="Why is this quotation being cancelled?" rows="3" />
            <div class="flex justify-end gap-3 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost" class="cursor-pointer">Keep</flux:button>
                </flux:modal.close>
                <flux:button wire:click="cancelQuotation" variant="danger" class="cursor-pointer">
                    Cancel Quotation
                </flux:button>
            </div>
        </div>
    </flux:modal>

</div>
