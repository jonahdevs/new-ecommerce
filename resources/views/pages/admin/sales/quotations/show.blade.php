<?php

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Services\{QuotationService, DocumentService};
use Livewire\Attributes\{Computed, Title};
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Quotation Details')] class extends Component {
    use WithFileUploads;

    public Quote $quote;

    // Price & Send form fields
    public string $quotedShipping = '';
    public int $validityDays = 7;
    public string $note = '';

    // Admin can override unit prices per item (keyed by quote_item.id)
    public array $itemPrices = [];

    // Cancel form
    public string $cancelNote = '';

    // SAP-prepared PDF upload
    public $sapPdfUpload = null;

    // =========================================================================
    //  MOUNT
    // =========================================================================

    public function mount(Quote $quote): void
    {
        $this->quote = $quote->load(['user', 'items.product', 'statusHistories.changedBy', 'order']);

        // Pre-populate item prices so the pricing form shows current prices
        foreach ($quote->items as $item) {
            $price = $item->quoted_price_cents ?? $item->original_price_cents;
            $this->itemPrices[$item->id] = number_format($price / 100, 2, '.', '');
        }
    }

    // =========================================================================
    //  COMPUTED — UI state helpers
    // =========================================================================

    #[Computed]
    public function canPrice(): bool
    {
        return $this->quote->isPending();
    }

    #[Computed]
    public function canCancel(): bool
    {
        return $this->quote->status->canTransitionTo(QuoteStatus::CANCELLED);
    }

    // Live total preview as admin types in the pricing form
    #[Computed]
    public function quotedTotal(): float
    {
        $shipping = (float) str_replace(',', '', $this->quotedShipping ?? '0');
        $itemsTotal = 0;

        foreach ($this->itemPrices as $itemId => $price) {
            $item = $this->quote->items->firstWhere('id', (int) $itemId);
            $itemsTotal += (float) str_replace(',', '', $price ?? '0') * ($item?->quantity ?? 1);
        }

        return max(0, $itemsTotal - $this->quote->discount_cents / 100 + $shipping);
    }

    // =========================================================================
    //  SAVE QUOTE (prepare without sending)
    // =========================================================================

    public function saveQuote(): void
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
            app(QuotationService::class)->prepare($this->quote, [
                'shipping' => $this->quotedShipping,
                'validity_days' => $this->validityDays,
                'note' => $this->note ?: null,
                'item_prices' => $this->itemPrices,
            ]);

            $this->quote->refresh();
            $this->modal('price-quote')->close();
            $this->dispatch('notify', variant: 'success', message: 'Quotation saved. You can send it when ready.');
        } catch (\Throwable $e) {
            logger()->error('Failed to save quotation.', [
                'quote_id' => $this->quote->id,
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('notify', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }

    // =========================================================================
    //  PRICE & SEND QUOTE
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
            app(QuotationService::class)->send($this->quote, [
                'shipping' => $this->quotedShipping,
                'validity_days' => $this->validityDays,
                'note' => $this->note ?: null,
                'item_prices' => $this->itemPrices,
            ]);

            $this->quote->refresh();
            $this->note = '';
            $this->modal('price-quote')->close();
            $this->dispatch('notify', variant: 'success', message: 'Quotation sent to customer.');
        } catch (\Throwable $e) {
            logger()->error('Failed to send quotation.', [
                'quote_id' => $this->quote->id,
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('notify', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }

    // =========================================================================
    //  UPLOAD SAP-PREPARED PDF
    // =========================================================================

    public function uploadSapPdf(): void
    {
        $this->validate([
            'sapPdfUpload' => ['required', 'file', 'mimes:pdf', 'max:10240'], // 10MB max
        ]);

        try {
            $path = $this->sapPdfUpload->store('quotations', 'local');
            $this->quote->update(['document_path' => $path]);
            $this->quote->refresh();

            $this->sapPdfUpload = null;
            $this->modal('upload-sap-pdf')->close();
            $this->dispatch('notify', variant: 'success', message: 'SAP quotation PDF uploaded successfully.');
        } catch (\Throwable $e) {
            logger()->error('Failed to upload SAP PDF.', [
                'quote_id' => $this->quote->id,
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('notify', variant: 'danger', message: 'Failed to upload PDF. Please try again.');
        }
    }

    // =========================================================================
    //  CANCEL QUOTATION
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
            app(QuotationService::class)->cancel($this->quote, $this->cancelNote ?: null);

            $this->quote->refresh();
            $this->cancelNote = '';
            $this->modal('cancel-quote')->close();
            $this->dispatch('notify', variant: 'warning', message: 'Quotation cancelled.');
        } catch (\Throwable $e) {
            $this->dispatch('notify', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }

    // =========================================================================
    //  PREVIEW QUOTATION PDF (stream inline)
    // =========================================================================

    public function previewPdf(): mixed
    {
        $quote = $this->quote;

        if (!$quote->document_path) {
            $path = app(DocumentService::class)->generateQuotation($quote);

            if (!$path) {
                $this->dispatch('notify', variant: 'danger', message: 'Unable to generate PDF. Please try again.');
                return null;
            }

            $quote->refresh();
        }

        $response = app(DocumentService::class)->stream($quote->document_path, 'Quotation');

        if (!$response) {
            $path = app(DocumentService::class)->generateQuotation($quote);

            if (!$path) {
                $this->dispatch('notify', variant: 'danger', message: 'PDF not found. Please try again.');
                return null;
            }

            $quote->refresh();
            return app(DocumentService::class)->stream($quote->document_path, 'Quotation');
        }

        return $response;
    }

    // =========================================================================
    //  DOWNLOAD QUOTATION PDF
    // =========================================================================

    public function downloadPdf(): mixed
    {
        $quote = $this->quote;

        if (!$quote->document_path) {
            $path = app(DocumentService::class)->generateQuotation($quote);

            if (!$path) {
                $this->dispatch('notify', variant: 'danger', message: 'Unable to generate PDF. Please try again.');
                return null;
            }

            $quote->refresh();
        }

        $response = app(DocumentService::class)->serve($quote->document_path, 'Quotation');

        if (!$response) {
            $path = app(DocumentService::class)->generateQuotation($quote);

            if (!$path) {
                $this->dispatch('notify', variant: 'danger', message: 'PDF not found. Please try again.');
                return null;
            }

            $quote->refresh();
            return app(DocumentService::class)->serve($quote->document_path, 'Quotation');
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
                <flux:breadcrumbs.item :href="route('admin.quotations.index')" wire:navigate>Quotations
                </flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $quote->reference }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            <div class="flex items-center gap-3 flex-wrap">
                <flux:heading size="xl" class="font-bold tracking-tight">
                    {{ $quote->reference }}
                </flux:heading>
                <flux:badge :color="$quote->status->color()" variant="solid" size="sm"
                    class="uppercase text-[10px] tracking-widest font-bold">
                    {{ $quote->status->label() }}
                </flux:badge>
            </div>

            <flux:text class="mt-1 flex items-center gap-2">
                <flux:icon name="calendar" class="size-4 text-zinc-400" />
                Submitted on {{ $quote->created_at->format('M d, Y') }} at {{ $quote->created_at->format('g:i A') }}
            </flux:text>
        </div>

        {{-- Primary actions --}}
        <div class="flex items-center gap-3 flex-wrap">
            @if ($quote->document_path)
                <flux:button variant="outline" icon="eye" size="sm" wire:click="previewPdf"
                    class="cursor-pointer">
                    <span wire:loading.remove wire:target="previewPdf">Preview PDF</span>
                    <span wire:loading wire:target="previewPdf">Loading...</span>
                </flux:button>
                <flux:button variant="outline" icon="arrow-down-tray" size="sm" wire:click="downloadPdf"
                    class="cursor-pointer">
                    <span wire:loading.remove wire:target="downloadPdf">Download</span>
                    <span wire:loading wire:target="downloadPdf">Downloading...</span>
                </flux:button>
            @elseif ($quote->isSent())
                <flux:button variant="outline" icon="document-text" size="sm" wire:click="previewPdf"
                    class="cursor-pointer">
                    <span wire:loading.remove wire:target="previewPdf">Generate & Preview</span>
                    <span wire:loading wire:target="previewPdf">Generating...</span>
                </flux:button>
            @endif

            @if ($this->canPrice)
                <flux:modal.trigger name="upload-sap-pdf">
                    <flux:button size="sm" variant="outline" icon="arrow-up-tray" class="cursor-pointer">
                        Upload Quote
                    </flux:button>
                </flux:modal.trigger>

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
    @if ($quote->isPending())
        <div class="flex items-start gap-3 p-4 bg-amber-50 border border-amber-200 rounded-lg mb-5">
            <flux:icon.clock class="size-5 shrink-0 mt-0.5 text-amber-500" />
            <div class="text-sm">
                <p class="font-medium text-amber-800">This quotation is awaiting your pricing</p>
                <p class="text-amber-700 mt-0.5">
                    Review the items and delivery preferences below, then click
                    <strong>Price & Send Quote</strong> to notify the customer.
                </p>
            </div>
        </div>
    @endif

    {{-- Expiring soon --}}
    @if ($quote->isSent() && $quote->expires_at?->diffInHours(now()) <= 48 && !$quote->expires_at?->isPast())
        <div class="flex items-start gap-3 p-4 bg-rose-50 border border-rose-200 rounded-lg mb-5">
            <flux:icon.exclamation-triangle class="size-5 shrink-0 mt-0.5 text-rose-500" />
            <div class="text-sm">
                <p class="font-medium text-rose-800">
                    This quotation expires {{ $quote->expires_at->diffForHumans() }}
                </p>
                <p class="text-rose-700 mt-0.5">Follow up with the customer to ensure they have seen the quote.</p>
            </div>
        </div>
    @endif

    {{-- Converted to sales order --}}
    @if ($quote->order)
        <div class="flex items-start gap-3 p-4 bg-teal-50 border border-teal-200 rounded-lg mb-5">
            <flux:icon.check-circle class="size-5 shrink-0 mt-0.5 text-teal-500" />
            <div class="text-sm flex items-center justify-between w-full">
                <div>
                    <p class="font-medium text-teal-800">Converted to a sales order</p>
                    <p class="text-teal-700 mt-0.5">Reference: {{ $quote->order->reference }}</p>
                </div>
                <flux:button size="sm" variant="ghost" icon="arrow-top-right-on-square"
                    :href="route('admin.orders.show', $quote->order)" wire:navigate>
                    View Order
                </flux:button>
            </div>
        </div>
    @endif


    {{-- ================================================================== --}}
    {{-- MAIN LAYOUT                                                         --}}
    {{-- ================================================================== --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-5 mt-6">

        {{-- ── Left: Main content (3 cols) ── --}}
        <div class="lg:col-span-3 space-y-5">

            {{-- Items table --}}
            <flux:card class="p-0">
                <div class="px-6 py-2 border-b border-zinc-200 dark:border-zinc-600 flex justify-between items-center">
                    <flux:heading level="3" class="font-semibold">Items</flux:heading>
                    <flux:badge variant="outline">{{ $quote->items->sum('quantity') }} items</flux:badge>
                </div>

                <flux:table>
                    <flux:table.columns>
                        <flux:table.column class="ps-6!">Product</flux:table.column>
                        <flux:table.column>SKU</flux:table.column>
                        <flux:table.column>Qty</flux:table.column>
                        <flux:table.column>Original Price</flux:table.column>
                        <flux:table.column>Quoted Price</flux:table.column>
                        <flux:table.column>Total</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($quote->items as $item)
                            <flux:table.row :key="$item->id">
                                <flux:table.cell class="ps-6!">
                                    <div class="flex items-center gap-3">
                                        <div class="shrink-0 w-12 h-12 rounded border overflow-hidden bg-zinc-50">
                                            @if ($item->productImageUrl())
                                                <img src="{{ asset($item->productImageUrl()) }}"
                                                    alt="{{ $item->productName() }}"
                                                    class="w-full h-full object-cover" />
                                            @else
                                                <flux:icon name="photo" class="w-full h-full p-2 text-zinc-300" />
                                            @endif
                                        </div>
                                        <div>
                                            <flux:text class="text-sm font-medium">{{ $item->productName() }}
                                            </flux:text>
                                            @if ($item->product_snapshot['variant'] ?? null)
                                                <flux:text class="text-xs text-zinc-400">
                                                    {{ collect($item->product_snapshot['variant'])->map(fn($v, $k) => "$k: $v")->join(', ') }}
                                                </flux:text>
                                            @endif
                                        </div>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:text class="text-xs text-zinc-400">{{ $item->productSku() }}</flux:text>
                                </flux:table.cell>
                                <flux:table.cell>{{ $item->quantity }}</flux:table.cell>
                                <flux:table.cell>{{ format_currency($item->original_price) }}</flux:table.cell>
                                <flux:table.cell>
                                    @if ($item->quoted_price_cents)
                                        <span
                                            class="{{ $item->hasCustomPrice() ? 'text-blue-600 dark:text-blue-400 font-medium' : '' }}">
                                            {{ format_currency($item->quoted_price) }}
                                        </span>
                                    @else
                                        <span class="text-zinc-400">—</span>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell class="font-medium">
                                    {{ format_currency($item->effective_price * $item->quantity) }}
                                </flux:table.cell>
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
                                <flux:text class="font-medium">{{ format_currency($quote->subtotal) }}</flux:text>
                            </div>
                            @if ($quote->discount > 0)
                                <div class="flex justify-between text-sm">
                                    <flux:text>Discount</flux:text>
                                    <flux:text class="font-medium text-green-600">
                                        − {{ format_currency($quote->discount) }}
                                    </flux:text>
                                </div>
                            @endif
                            <div class="flex justify-between text-sm">
                                <flux:text>Shipping</flux:text>
                                <flux:text class="font-medium">
                                    @if ($quote->shipping_cents === 0 && !$quote->status->isTerminal())
                                        <span class="text-amber-500">TBD</span>
                                    @elseif ($quote->shipping_cents === 0)
                                        <span class="text-green-600">Free</span>
                                    @else
                                        {{ format_currency($quote->shipping) }}
                                    @endif
                                </flux:text>
                            </div>
                            <div class="flex justify-between pt-2 border-t border-zinc-200 dark:border-zinc-600">
                                <flux:heading size="lg">Total</flux:heading>
                                <div class="text-right">
                                    <flux:heading size="lg" class="font-bold">
                                        {{ format_currency($quote->total) }}
                                    </flux:heading>
                                    @if ($quote->shipping_cents === 0 && !$quote->status->isTerminal())
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
                <div class="px-5 py-3 border-b border-zinc-200 dark:border-zinc-600 flex items-center justify-between">
                    <flux:heading>Quotation Timeline</flux:heading>
                    <flux:badge :color="$quote->status->color()" variant="solid" size="sm">
                        {{ $quote->status->label() }}
                    </flux:badge>
                </div>

                <div class="p-5">
                    @php
                        $mainPath = [QuoteStatus::PENDING, QuoteStatus::SENT, QuoteStatus::ACCEPTED];
                        $isCancelled = $quote->isCancelled();
                        $isRejected = $quote->isRejected();
                        $isExpired = $quote->isExpired();
                        $isTerminal = $isCancelled || $isRejected || $isExpired;
                        $histories = $quote->statusHistories->keyBy('to_status');

                        // Find the current active step (last reached step)
                        $currentStepIndex = -1;
                        foreach ($mainPath as $idx => $step) {
                            if ($histories->has($step->value)) {
                                $currentStepIndex = $idx;
                            }
                        }
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
                            @endphp

                            <div class="relative flex gap-4 {{ $isLast ? 'pb-0' : 'pb-6' }}">
                                @if (!$isLast)
                                    <div @class([
                                        'absolute left-4 top-8 bottom-0 w-px z-0',
                                        'bg-green-500' => $nextReached,
                                        'bg-zinc-200 dark:bg-zinc-600' => !$nextReached,
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

                                <div class="flex-1 flex items-start justify-between gap-4 pt-1 min-w-0">
                                    <div class="min-w-0">
                                        <flux:text @class([
                                            'text-sm',
                                            'font-semibold text-green-600 dark:text-green-400' => $isActive,
                                            'font-medium text-zinc-900 dark:text-white' => $reached && !$isActive,
                                            'text-zinc-300 dark:text-zinc-600' => $dimmed,
                                            'text-zinc-400' => !$reached && !$dimmed,
                                        ])>
                                            {{ $step->label() }}
                                        </flux:text>

                                        @if ($step === QuoteStatus::SENT && $quote->expires_at && $reached)
                                            <flux:text
                                                class="text-xs mt-0.5
                                                {{ $quote->expires_at->isPast() ? 'text-rose-500' : 'text-zinc-400' }}">
                                                {{ $quote->expires_at->isPast() ? 'Expired' : 'Expires' }}
                                                {{ $quote->expires_at->diffForHumans() }}
                                                ({{ $quote->expires_at->format('M d, Y') }})
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
                        @if ($quote->order)
                            <div class="relative flex gap-4 pt-6">
                                <div class="absolute left-4 top-0 h-6 w-px bg-green-500 z-0"></div>
                                <div
                                    class="relative z-10 shrink-0 w-8 h-8 rounded-full flex items-center justify-center bg-teal-600 text-white">
                                    <flux:icon name="arrow-right-circle" class="size-4" />
                                </div>
                                <div class="flex-1 pt-1">
                                    <flux:text class="text-sm font-medium text-teal-600">Converted to sales order
                                    </flux:text>
                                    <flux:link :href="route('admin.orders.show', $quote->order)" wire:navigate
                                        class="text-xs text-teal-500">
                                        {{ $quote->order->reference }} →
                                    </flux:link>
                                </div>
                            </div>
                        @endif

                        {{-- Branch: Rejected --}}
                        @if ($isRejected)
                            @php $h = $histories->get(QuoteStatus::REJECTED->value); @endphp
                            <div class="relative flex gap-4 pt-6">
                                <div class="absolute left-4 top-0 h-6 w-px bg-zinc-300 z-0"></div>
                                <div
                                    class="relative z-10 shrink-0 w-8 h-8 rounded-full flex items-center justify-center bg-red-500 text-white">
                                    <flux:icon name="x-circle" class="size-4" />
                                </div>
                                <div class="flex-1 flex items-start justify-between gap-4 pt-1">
                                    <div>
                                        <flux:text class="text-sm font-medium text-red-600">Rejected by customer
                                        </flux:text>
                                        @if ($quote->rejection_reason)
                                            <flux:text class="text-xs text-zinc-400 mt-0.5">
                                                {{ $quote->rejection_reason }}</flux:text>
                                        @endif
                                    </div>
                                    @if ($h)
                                        <div class="text-right shrink-0">
                                            <flux:text class="text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                                {{ $h->created_at->format('M d, Y') }}
                                            </flux:text>
                                            <flux:text class="text-xs text-zinc-400 mt-0.5">
                                                {{ $h->created_at->format('g:i A') }}
                                            </flux:text>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- Branch: Expired --}}
                        @if ($isExpired)
                            @php $h = $histories->get(QuoteStatus::EXPIRED->value); @endphp
                            <div class="relative flex gap-4 pt-6">
                                <div class="absolute left-4 top-0 h-6 w-px bg-zinc-300 z-0"></div>
                                <div
                                    class="relative z-10 shrink-0 w-8 h-8 rounded-full flex items-center justify-center bg-zinc-400 text-white">
                                    <flux:icon name="exclamation-circle" class="size-4" />
                                </div>
                                <div class="flex-1 flex items-start justify-between gap-4 pt-1">
                                    <flux:text class="text-sm font-medium text-zinc-500">Quote expired</flux:text>
                                    @if ($h)
                                        <div class="text-right shrink-0">
                                            <flux:text class="text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                                {{ $h->created_at->format('M d, Y') }}
                                            </flux:text>
                                            <flux:text class="text-xs text-zinc-400 mt-0.5">
                                                {{ $h->created_at->format('g:i A') }}
                                            </flux:text>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- Branch: Cancelled --}}
                        @if ($isCancelled)
                            @php $h = $histories->get(QuoteStatus::CANCELLED->value); @endphp
                            <div class="relative flex gap-4 pt-6">
                                <div class="absolute left-4 top-0 h-6 w-px bg-zinc-300 z-0"></div>
                                <div
                                    class="relative z-10 shrink-0 w-8 h-8 rounded-full flex items-center justify-center bg-zinc-400 text-white">
                                    <flux:icon name="x-mark" class="size-4" />
                                </div>
                                <div class="flex-1 flex items-start justify-between gap-4 pt-1">
                                    <div>
                                        <flux:text class="text-sm font-medium text-zinc-500">Cancelled by admin
                                        </flux:text>
                                        @if ($h?->notes)
                                            <flux:text class="text-xs text-zinc-400 mt-0.5">{{ $h->notes }}
                                            </flux:text>
                                        @endif
                                    </div>
                                    @if ($h)
                                        <div class="text-right shrink-0">
                                            <flux:text class="text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                                {{ $h->created_at->format('M d, Y') }}
                                            </flux:text>
                                            <flux:text class="text-xs text-zinc-400 mt-0.5">
                                                {{ $h->created_at->format('g:i A') }}
                                            </flux:text>
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
        <div class="space-y-5">

            {{-- Customer info --}}
            <flux:card class="p-0">
                <div class="px-5 py-3 border-b border-zinc-200 dark:border-zinc-600">
                    <flux:heading>Customer</flux:heading>
                </div>
                <div class="p-5 space-y-4">
                    <div class="flex items-center gap-3">
                        <div
                            class="shrink-0 w-10 h-10 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
                            <flux:icon name="user" class="size-5 text-zinc-400" />
                        </div>
                        <div>
                            <flux:text class="font-medium">{{ $quote->customerName() }}</flux:text>
                            @if ($quote->isGuest())
                                <flux:badge size="sm" color="zinc" variant="flat">Guest</flux:badge>
                            @endif
                        </div>
                    </div>

                    <div class="space-y-2 text-sm">
                        @if ($quote->customerEmail())
                            <div class="flex items-center gap-2">
                                <flux:icon name="envelope" class="size-4 text-zinc-400" />
                                <flux:link href="mailto:{{ $quote->customerEmail() }}">{{ $quote->customerEmail() }}
                                </flux:link>
                            </div>
                        @endif
                        @if ($quote->customerPhone())
                            <div class="flex items-center gap-2">
                                <flux:icon name="phone" class="size-4 text-zinc-400" />
                                <flux:text>{{ $quote->customerPhone() }}</flux:text>
                            </div>
                        @endif
                    </div>

                    @if ($quote->user)
                        <flux:button size="sm" variant="ghost" icon="arrow-top-right-on-square"
                            :href="route('admin.customers.show', $quote->user)" wire:navigate class="w-full">
                            View Customer
                        </flux:button>
                    @endif
                </div>
            </flux:card>

            {{-- Delivery preferences --}}
            <flux:card class="p-0">
                <div class="px-5 py-3 border-b border-zinc-200 dark:border-zinc-600">
                    <flux:heading>Delivery Preferences</flux:heading>
                </div>
                <div class="p-5 space-y-3 text-sm">
                    @if ($quote->preferred_county || $quote->preferred_area)
                        <div class="flex items-start gap-2">
                            <flux:icon name="map-pin" class="size-4 text-zinc-400 mt-0.5" />
                            <div>
                                @if ($quote->preferred_area)
                                    <flux:text>{{ $quote->preferred_area }}</flux:text>
                                @endif
                                @if ($quote->preferred_county)
                                    <flux:text class="text-zinc-400">{{ $quote->preferred_county }}</flux:text>
                                @endif
                            </div>
                        </div>
                    @else
                        <flux:text class="text-zinc-400">No delivery preferences specified.</flux:text>
                    @endif
                </div>
            </flux:card>

            {{-- Customer notes --}}
            @if ($quote->customer_notes)
                <flux:card class="p-0">
                    <div class="px-5 py-3 border-b border-zinc-200 dark:border-zinc-600">
                        <flux:heading>Customer Notes</flux:heading>
                    </div>
                    <div class="p-5">
                        <flux:text class="text-sm whitespace-pre-wrap">{{ $quote->customer_notes }}</flux:text>
                    </div>
                </flux:card>
            @endif

            {{-- Admin notes --}}
            @if ($quote->admin_notes)
                <flux:card class="p-0">
                    <div class="px-5 py-3 border-b border-zinc-200 dark:border-zinc-600">
                        <flux:heading>Admin Notes</flux:heading>
                    </div>
                    <div class="p-5">
                        <flux:text class="text-sm whitespace-pre-wrap">{{ $quote->admin_notes }}</flux:text>
                    </div>
                </flux:card>
            @endif
        </div>
    </div>


    {{-- ================================================================== --}}
    {{-- MODALS                                                              --}}
    {{-- ================================================================== --}}

    {{-- Price & Send Quote Modal --}}
    <flux:modal name="price-quote" class="max-w-2xl">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Price & Send Quote</flux:heading>
                <flux:text class="mt-1">Set item prices and shipping, then send the quotation to the customer.
                </flux:text>
            </div>

            <form wire:submit="sendQuote" class="space-y-5">
                {{-- Item prices --}}
                <div class="space-y-3">
                    <flux:label>Item Prices</flux:label>
                    @foreach ($quote->items as $item)
                        <div class="flex items-center gap-4 p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                            <div class="flex-1 min-w-0">
                                <flux:text class="text-sm font-medium truncate">{{ $item->productName() }}</flux:text>
                                <flux:text class="text-xs text-zinc-400">Qty: {{ $item->quantity }} × Original:
                                    {{ format_currency($item->original_price) }}</flux:text>
                            </div>
                            <div class="w-32">
                                <flux:input type="number" step="0.01" min="0"
                                    wire:model.live.debounce.300ms="itemPrices.{{ $item->id }}"
                                    placeholder="Unit price" />
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Shipping --}}
                <flux:input type="number" step="0.01" min="0"
                    wire:model.live.debounce.300ms="quotedShipping"
                    label="Shipping Cost ({{ get_currency_symbol() }})" placeholder="0.00" />

                {{-- Validity --}}
                <flux:input type="number" min="1" max="90" wire:model="validityDays"
                    label="Validity Period (days)" description="How long the customer has to accept this quote." />

                {{-- Note --}}
                <flux:textarea wire:model="note" label="Note to Customer (optional)"
                    placeholder="Any additional information for the customer..." rows="3" />

                {{-- Live total preview --}}
                <div class="p-4 bg-zinc-100 dark:bg-zinc-800 rounded-lg">
                    <div class="flex justify-between items-center">
                        <flux:text class="font-medium">Quoted Total</flux:text>
                        <flux:heading size="lg" class="font-bold">{{ format_currency($this->quotedTotal) }}
                        </flux:heading>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button wire:click="saveQuote" variant="outline" icon="bookmark">
                        <span wire:loading.remove wire:target="saveQuote">Save Only</span>
                        <span wire:loading wire:target="saveQuote">Saving...</span>
                    </flux:button>
                    <flux:button type="submit" variant="primary" icon="paper-airplane">
                        <span wire:loading.remove wire:target="sendQuote">Send Quote</span>
                        <span wire:loading wire:target="sendQuote">Sending...</span>
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Upload SAP PDF Modal --}}
    <flux:modal name="upload-sap-pdf" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Upload Quotation</flux:heading>
                <flux:text class="mt-1">Upload a quotation PDF. This will replace any existing document.</flux:text>
            </div>

            <form wire:submit="uploadSapPdf" class="space-y-5">
                <div>
                    <flux:label>PDF File</flux:label>
                    <input type="file" wire:model="sapPdfUpload" accept=".pdf"
                        class="mt-1 block w-full text-sm text-zinc-500
                            file:mr-4 file:py-2 file:px-4
                            file:rounded-md file:border-0
                            file:text-sm file:font-medium
                            file:bg-zinc-100 file:text-zinc-700
                            hover:file:bg-zinc-200
                            dark:file:bg-zinc-800 dark:file:text-zinc-300
                            dark:hover:file:bg-zinc-700" />
                    @error('sapPdfUpload')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                    <flux:description class="mt-1">Maximum file size: 10MB</flux:description>
                </div>

                @if ($sapPdfUpload)
                    <div
                        class="p-3 bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800 rounded-lg">
                        <flux:text class="text-sm text-green-700 dark:text-green-300">
                            <flux:icon name="document" class="size-4 inline mr-1" />
                            {{ $sapPdfUpload->getClientOriginalName() }}
                            ({{ number_format($sapPdfUpload->getSize() / 1024, 1) }} KB)
                        </flux:text>
                    </div>
                @endif

                <div class="flex justify-end gap-3 pt-4">
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" icon="arrow-up-tray" :disabled="!$sapPdfUpload">
                        <span wire:loading.remove wire:target="uploadSapPdf">Upload PDF</span>
                        <span wire:loading wire:target="uploadSapPdf">Uploading...</span>
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Cancel Quote Modal --}}
    <flux:modal name="cancel-quote" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Cancel Quotation</flux:heading>
                <flux:text class="mt-1">Are you sure you want to cancel this quotation? This action cannot be undone.
                </flux:text>
            </div>

            <form wire:submit="cancelQuotation" class="space-y-5">
                <flux:textarea wire:model="cancelNote" label="Reason (optional)"
                    placeholder="Why is this quotation being cancelled?" rows="3" />

                <div class="flex justify-end gap-3 pt-4">
                    <flux:modal.close>
                        <flux:button variant="ghost">Keep Quote</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="danger" icon="x-circle">
                        <span wire:loading.remove wire:target="cancelQuotation">Cancel Quotation</span>
                        <span wire:loading wire:target="cancelQuotation">Cancelling...</span>
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
