<?php

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Services\DocumentService;
use App\Services\QuotationService;
use App\Settings\QuotationSettings;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Quotation Details')] class extends Component
{
    use WithFileUploads;

    public Quote $quote;

    public string $quotedShipping = '';

    public int $validityDays = 7;

    public string $adminNote = '';

    public array $itemPrices = [];

    public string $cancelNote = '';

    public $sapPdfUpload = null;

    public function mount(Quote $quote): void
    {
        $this->quote = $quote->load(['user', 'items.product', 'statusHistories.changedBy', 'order']);

        foreach ($quote->items as $item) {
            $price = $item->quoted_price_cents ?? $item->original_price_cents;
            $this->itemPrices[$item->id] = number_format($price / 100, 2, '.', '');
        }

        // Rehydrate shipping from the persisted value so the input keeps what was saved.
        if ($quote->shipping_cents > 0) {
            $this->quotedShipping = number_format($quote->shipping_cents / 100, 2, '.', '');
        }

        $settings = app(QuotationSettings::class);
        $this->validityDays = $settings->default_validity_days;
    }

    // =========================================================================
    //  COMPUTED
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

    #[Computed]
    public function quotedTotal(): float
    {
        $tax = app(\App\Services\TaxService::class);
        $shipping = (float) str_replace(',', '', $this->quotedShipping ?: '0');
        $base = max(0, $this->quotedSubtotal - $this->quote->discount_cents / 100 + $shipping);

        return $tax->isEnabled() && ! $tax->isInclusive()
            ? $base + $this->quotedTax
            : $base;
    }

    #[Computed]
    public function quotedSubtotal(): float
    {
        $total = 0;

        foreach ($this->itemPrices as $itemId => $price) {
            $item = $this->quote->items->firstWhere('id', (int) $itemId);
            $total += (float) str_replace(',', '', $price ?: '0') * ($item?->quantity ?? 1);
        }

        return max(0, $total);
    }

    #[Computed]
    public function quotedTax(): float
    {
        $tax = app(\App\Services\TaxService::class);

        if (! $tax->isEnabled()) {
            return 0.0;
        }

        $subtotalCents = (int) round($this->quotedSubtotal * 100);
        $shippingCents = (int) round((float) str_replace(',', '', $this->quotedShipping ?: '0') * 100);
        $discountCents = $this->quote->discount_cents;

        $taxableSubtotal = max(0, $subtotalCents - $discountCents);
        $breakdown = $tax->calculateOrderTax($taxableSubtotal, $shippingCents);

        return $breakdown['total_tax'] / 100;
    }

    // =========================================================================
    //  SAVE DRAFT
    // =========================================================================

    public function saveDraft(): void
    {
        $this->validate([
            'quotedShipping' => ['nullable', 'numeric', 'min:0'],
            'validityDays' => ['required', 'integer', 'min:'.app(QuotationSettings::class)->min_validity_days, 'max:'.app(QuotationSettings::class)->max_validity_days],
            'adminNote' => ['nullable', 'string', 'max:1000'],
            'itemPrices.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        if (! $this->canPrice) {
            $this->dispatch('notify', variant: 'danger', message: 'This quotation can no longer be priced');

            return;
        }

        try {
            app(QuotationService::class)->prepare($this->quote, [
                'shipping' => $this->quotedShipping ?: 0,
                'validity_days' => $this->validityDays,
                'note' => $this->adminNote ?: null,
                'item_prices' => $this->itemPrices,
            ]);

            $this->quote->refresh();
            $this->dispatch('notify', variant: 'success', message: 'Draft saved — prices updated');
        } catch (Throwable $e) {
            $this->dispatch('notify', variant: 'danger', message: 'Something went wrong. Please try again');
        }
    }

    // =========================================================================
    //  SEND QUOTE
    // =========================================================================

    public function sendQuote(): void
    {
        $this->validate([
            'quotedShipping' => ['nullable', 'numeric', 'min:0'],
            'validityDays' => ['required', 'integer', 'min:'.app(QuotationSettings::class)->min_validity_days, 'max:'.app(QuotationSettings::class)->max_validity_days],
            'adminNote' => ['nullable', 'string', 'max:1000'],
            'itemPrices.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        if (! $this->canPrice) {
            $this->dispatch('notify', variant: 'danger', message: 'This quotation can no longer be priced');

            return;
        }

        try {
            app(QuotationService::class)->send($this->quote, [
                'shipping' => $this->quotedShipping ?: 0,
                'validity_days' => $this->validityDays,
                'note' => $this->adminNote ?: null,
                'item_prices' => $this->itemPrices,
            ]);

            $this->quote->refresh();
            $this->modal('confirm-send')->close();
            $this->dispatch('notify', variant: 'success', message: 'Quotation sent to customer successfully');
        } catch (Throwable $e) {
            $this->dispatch('notify', variant: 'danger', message: 'Something went wrong while sending the quotation. Please try again');
        }
    }

    // =========================================================================
    //  UPLOAD SAP PDF
    // =========================================================================

    public function uploadSapPdf(): void
    {
        $this->validate([
            'sapPdfUpload' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        try {
            $path = $this->sapPdfUpload->store('quotations', 'local');
            $this->quote->update(['document_path' => $path]);
            $this->quote->refresh();

            $this->sapPdfUpload = null;
            $this->modal('upload-sap-pdf')->close();
            $this->dispatch('notify', variant: 'success', message: 'Quotation PDF uploaded successfully');
        } catch (Throwable $e) {
            $this->dispatch('notify', variant: 'danger', message: 'Failed to upload the PDF. Please try again');
        }
    }

    // =========================================================================
    //  CANCEL
    // =========================================================================

    public function cancelQuotation(): void
    {
        $this->validate([
            'cancelNote' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! $this->canCancel) {
            $this->dispatch('notify', variant: 'danger', message: 'This quotation cannot be cancelled');

            return;
        }

        try {
            app(QuotationService::class)->cancel($this->quote, $this->cancelNote ?: null);

            $this->quote->refresh();
            $this->cancelNote = '';
            $this->modal('cancel-quote')->close();
            $this->dispatch('notify', variant: 'warning', message: 'Quotation cancelled');
        } catch (Throwable $e) {
            $this->dispatch('notify', variant: 'danger', message: 'Something went wrong. Please try again');
        }
    }

    // =========================================================================
    //  PREVIEW DOCUMENT (pending — saves draft then regenerates PDF)
    // =========================================================================

    public function previewDocument(): void
    {
        $settings = app(QuotationSettings::class);

        $this->validate([
            'quotedShipping' => ['nullable', 'numeric', 'min:0'],
            'validityDays' => ['required', 'integer', "min:{$settings->min_validity_days}", "max:{$settings->max_validity_days}"],
            'adminNote' => ['nullable', 'string', 'max:1000'],
            'itemPrices.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            if ($this->canPrice) {
                app(QuotationService::class)->prepare($this->quote, [
                    'shipping' => $this->quotedShipping ?: 0,
                    'validity_days' => $this->validityDays,
                    'note' => $this->adminNote ?: null,
                    'item_prices' => $this->itemPrices,
                ]);
                $this->quote->refresh();
            }

            $path = app(DocumentService::class)->generateQuotation($this->quote);

            if (! $path) {
                $this->dispatch('notify', variant: 'danger', message: 'Unable to generate preview. Please try again');

                return;
            }

            $this->quote->refresh();

            $this->dispatch('open-pdf', url: route('admin.quotations.pdf', $this->quote));
        } catch (Throwable $e) {
            $this->dispatch('notify', variant: 'danger', message: 'Something went wrong generating the preview');
        }
    }
};
?>

<div>

    {{-- ================================================================== --}}
    {{-- PAGE HEADER                                                         --}}
    {{-- ================================================================== --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            @push('breadcrumbs')
                <flux:breadcrumbs>
                    <flux:breadcrumbs.item :href="route('admin.quotations.index')" wire:navigate>Quotations
                    </flux:breadcrumbs.item>
                    <flux:breadcrumbs.item>{{ $quote->reference }}</flux:breadcrumbs.item>
                </flux:breadcrumbs>
            @endpush

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
                Submitted {{ $quote->created_at->format('M d, Y') }} at {{ $quote->created_at->format('g:i A') }}
            </flux:text>
        </div>

        {{-- Header actions --}}
        <div class="flex items-center gap-2 flex-wrap">

            <flux:button variant="outline" icon="document-magnifying-glass" size="sm" wire:click="previewDocument"
                class="cursor-pointer">
                <span wire:loading.remove wire:target="previewDocument">Preview Document</span>
                <span wire:loading wire:target="previewDocument">Opening...</span>
            </flux:button>

            @if ($this->canPrice)
                <flux:modal.trigger name="upload-sap-pdf">
                    <flux:button size="sm" variant="outline" icon="arrow-up-tray" class="cursor-pointer">
                        Upload PDF
                    </flux:button>
                </flux:modal.trigger>

                <flux:button size="sm" variant="outline" icon="bookmark" wire:click="saveDraft"
                    class="cursor-pointer">
                    <span wire:loading.remove wire:target="saveDraft">Save Draft</span>
                    <span wire:loading wire:target="saveDraft">Saving...</span>
                </flux:button>

                <flux:modal.trigger name="confirm-send">
                    <flux:button size="sm" variant="primary" icon="paper-airplane" class="cursor-pointer">
                        Send to Customer
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

    @if ($quote->isSent() && $quote->expires_at?->diffInHours(now()) <= 48 && ! $quote->expires_at?->isPast())
        <flux:callout icon="exclamation-triangle" variant="danger" class="mb-5">
            <flux:heading size="sm" class="font-medium!">
                Expires {{ $quote->expires_at->diffForHumans() }}
            </flux:heading>
            <flux:subheading class="mt-0.5">Follow up with the customer — they may not have seen the quote yet.
            </flux:subheading>
        </flux:callout>
    @endif

    @if ($quote->order)
        <flux:callout icon="check-circle" icon-variant="outline" color="green" class="mb-5">
            <flux:callout.heading>
                This quotation was converted to a sales order
                <flux:link :href="route('admin.orders.show', $quote->order)" wire:navigate class="font-medium">
                    {{ $quote->order->reference }}
                </flux:link>
            </flux:callout.heading>
        </flux:callout>
    @endif

    {{-- ================================================================== --}}
    {{-- MAIN LAYOUT                                                         --}}
    {{-- ================================================================== --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-5">

        {{-- ── Left: main content ── --}}
        <div class="lg:col-span-3 space-y-5">

            {{-- ── ITEMS TABLE ── --}}
            <flux:card
                class="p-0 overflow-hidden **:data-flux-columns:bg-zinc-50 dark:**:data-flux-columns:bg-zinc-800">
                <div
                    class="px-6 py-3 border-b border-zinc-200 dark:border-zinc-600 flex items-center justify-between">
                    <flux:heading level="3" class="font-semibold">Items</flux:heading>
                    <flux:badge variant="outline">{{ $quote->items->sum('quantity') }} items</flux:badge>
                </div>

                <flux:table>
                    <flux:table.columns>
                        <flux:table.column class="ps-6! w-[40%]">Product</flux:table.column>
                        <flux:table.column>SKU</flux:table.column>
                        <flux:table.column>Qty</flux:table.column>
                        <flux:table.column>List Price</flux:table.column>
                        <flux:table.column>
                            @if ($this->canPrice)
                                <span class="text-primary">Quoted Price</span>
                            @else
                                Quoted Price
                            @endif
                        </flux:table.column>
                        <flux:table.column>Line Total</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($quote->items as $item)
                            @php
                                $lineTotal =
                                    (float) str_replace(',', '', $itemPrices[$item->id] ?? '0') * $item->quantity;
                            @endphp
                            <flux:table.row :key="$item->id">

                                <flux:table.cell class="ps-6!">
                                    <div class="flex items-center gap-3">
                                        <div class="shrink-0 w-10 h-10 rounded border overflow-hidden bg-zinc-50">
                                            @if ($item->productImageUrl())
                                                <img src="{{ asset($item->productImageUrl()) }}"
                                                    alt="{{ $item->productName() }}"
                                                    class="w-full h-full object-cover" />
                                            @else
                                                <flux:icon name="photo"
                                                    class="w-full h-full p-2 text-zinc-300" />
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

                                <flux:table.cell>
                                    <flux:text class="text-sm text-zinc-400">
                                        {{ format_currency($item->original_price) }}
                                    </flux:text>
                                </flux:table.cell>

                                <flux:table.cell>
                                    @if ($this->canPrice)
                                        <div class="w-32">
                                            <input type="number" step="0.01" min="0"
                                                wire:model.live.debounce.400ms="itemPrices.{{ $item->id }}"
                                                placeholder="{{ number_format($item->original_price, 2) }}"
                                                class="w-full border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-2.5 py-1.5 text-sm rounded focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors" />
                                        </div>
                                    @else
                                        @if ($item->quoted_price_cents)
                                            <span
                                                class="{{ $item->hasCustomPrice() ? 'text-blue-600 dark:text-blue-400 font-medium' : '' }}">
                                                {{ format_currency($item->quoted_price) }}
                                            </span>
                                        @else
                                            <span class="text-zinc-400">—</span>
                                        @endif
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell class="font-medium">
                                    @if ($this->canPrice)
                                        <span wire:key="line-{{ $item->id }}" class="tabular-nums">
                                            {{ format_currency($lineTotal) }}
                                        </span>
                                    @else
                                        {{ format_currency($item->effective_price * $item->quantity) }}
                                    @endif
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
                <div class="bg-zinc-50 dark:bg-zinc-800 border-t border-zinc-100 dark:border-zinc-600 p-6">
                    <div class="flex flex-col items-end">
                        <div class="w-full max-w-xs space-y-2">
                            <div class="flex justify-between text-sm">
                                <flux:text>Subtotal</flux:text>
                                <flux:text class="font-medium tabular-nums">
                                    {{ $this->canPrice ? format_currency($this->quotedSubtotal) : format_currency($quote->subtotal) }}
                                </flux:text>
                            </div>
                            @if ($quote->discount > 0)
                                <div class="flex justify-between text-sm">
                                    <flux:text>Discount</flux:text>
                                    <flux:text class="font-medium text-green-600 tabular-nums">
                                        − {{ format_currency($quote->discount) }}
                                    </flux:text>
                                </div>
                            @endif
                            <div class="flex justify-between items-center text-sm">
                                <flux:text>Shipping</flux:text>
                                @if ($this->canPrice)
                                    <input type="number" step="0.01" min="0"
                                        wire:model.live.debounce.400ms="quotedShipping"
                                        placeholder="0.00"
                                        class="w-28 border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-2.5 py-1 text-sm text-right rounded focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors tabular-nums" />
                                @else
                                    <flux:text class="font-medium tabular-nums">
                                        @if ($quote->shipping_cents === 0 && ! $quote->status->isTerminal())
                                            <span class="text-amber-500">TBD</span>
                                        @elseif ($quote->shipping_cents === 0)
                                            <span class="text-green-600">Free</span>
                                        @else
                                            {{ format_currency($quote->shipping) }}
                                        @endif
                                    </flux:text>
                                @endif
                            </div>
                            @php $taxService = app(\App\Services\TaxService::class); @endphp
                            @if ($taxService->isEnabled())
                                <div class="flex justify-between text-sm">
                                    <flux:text>
                                        {{ $taxService->name() }}
                                        @if ($taxService->isInclusive())
                                            <span class="text-zinc-400">(incl.)</span>
                                        @endif
                                        <span class="text-zinc-400">({{ $taxService->rateLabel() }})</span>
                                    </flux:text>
                                    <flux:text class="font-medium tabular-nums">
                                        {{ $this->canPrice ? format_currency($this->quotedTax) : format_currency($quote->tax) }}
                                    </flux:text>
                                </div>
                            @endif
                            <div
                                class="flex justify-between pt-2 border-t border-zinc-200 dark:border-zinc-600">
                                <flux:heading size="lg">Total</flux:heading>
                                <flux:heading size="lg" class="font-bold tabular-nums">
                                    {{ $this->canPrice ? format_currency($this->quotedTotal) : format_currency($quote->total) }}
                                </flux:heading>
                            </div>
                        </div>
                    </div>
                </div>
            </flux:card>

            {{-- ── PRICING CONFIGURATION (pending only) ── --}}
            @if ($this->canPrice)
                <flux:card class="p-0">
                    <div class="px-5 py-3 border-b border-zinc-200 dark:border-zinc-600">
                        <flux:heading>Quote Configuration</flux:heading>
                    </div>

                    <div class="p-5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <flux:field>
                                <flux:label>Validity Period (days)</flux:label>
                                <flux:input type="number"
                                    min="{{ app(QuotationSettings::class)->min_validity_days }}"
                                    max="{{ app(QuotationSettings::class)->max_validity_days }}"
                                    wire:model="validityDays" />
                                <flux:description>Default from settings. Override per quote if needed.
                                </flux:description>
                            </flux:field>
                        </div>

                        <div class="mt-5">
                            <flux:field>
                                <flux:label>Note to Customer <span
                                        class="text-zinc-400 font-normal">(optional)</span></flux:label>
                                <flux:textarea wire:model="adminNote"
                                    placeholder="Additional terms, installation notes, payment instructions..."
                                    rows="3" />
                            </flux:field>
                        </div>
                    </div>
                </flux:card>
            @endif

            {{-- ── TIMELINE ── --}}
            <flux:card class="p-0">
                <div
                    class="px-5 py-3 border-b border-zinc-200 dark:border-zinc-600 flex items-center justify-between">
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
                                $isActive = $index === $currentStepIndex && ! $isTerminal;
                                $isLast = $index === count($mainPath) - 1;
                                $next = $mainPath[$index + 1] ?? null;
                                $nextReached = $next && $histories->has($next->value);
                                $dimmed = $isTerminal && ! $reached;
                            @endphp

                            <div class="relative flex gap-4 {{ $isLast ? 'pb-0' : 'pb-6' }}">
                                @if (! $isLast)
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
                                                class="text-xs mt-0.5 {{ $quote->expires_at->isPast() ? 'text-rose-500' : 'text-zinc-400' }}">
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

        {{-- ── Right: Sidebar ── --}}
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
                            <flux:icon.user class="size-5 text-zinc-400" />
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
                                <flux:link href="mailto:{{ $quote->customerEmail() }}">
                                    {{ $quote->customerEmail() }}
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
                            :href="route('admin.customers.show', $quote->user)" wire:navigate
                            class="w-full">
                            View Customer
                        </flux:button>
                    @endif
                </div>
            </flux:card>

            {{-- Delivery preference --}}
            <flux:card class="p-0">
                <div class="px-5 py-3 border-b border-zinc-200 dark:border-zinc-600">
                    <flux:heading>Fulfilment</flux:heading>
                </div>
                <div class="p-5 space-y-3 text-sm">
                    @if (($quote->delivery_type ?? 'delivery') === 'pickup')
                        <div class="flex items-center gap-2">
                            <flux:icon name="building-storefront" class="size-4 text-zinc-400" />
                            <flux:text class="font-medium">In-store pickup</flux:text>
                        </div>
                        <flux:text class="text-xs text-zinc-400">Customer will collect from your warehouse.</flux:text>
                    @else
                        <div class="flex items-start gap-2">
                            <flux:icon name="map-pin" class="size-4 text-zinc-400 mt-0.5" />
                            <div>
                                <flux:text class="font-medium mb-0.5">Deliver to location</flux:text>
                                @if ($quote->preferred_area || $quote->preferred_county)
                                    <flux:text class="text-zinc-500">
                                        {{ implode(', ', array_filter([$quote->preferred_area, $quote->preferred_county])) }}
                                    </flux:text>
                                @else
                                    <flux:text class="text-zinc-400 text-xs">No location specified</flux:text>
                                @endif
                            </div>
                        </div>
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

            {{-- Admin notes (display only when not pricing) --}}
            @if (! $this->canPrice && $quote->admin_notes)
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

    {{-- Confirm send --}}
    <flux:modal name="confirm-send" class="max-w-sm">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">Send Quotation?</flux:heading>
                <flux:text class="mt-1">
                    This will email the priced quote to
                    <strong>{{ $quote->customerName() }}</strong> and mark it as sent. The quote will be valid for
                    <strong>{{ $validityDays }} {{ Str::plural('day', $validityDays) }}</strong>.
                </flux:text>
            </div>

            <div class="flex justify-end gap-3">
                <flux:modal.close>
                    <flux:button variant="ghost">Go Back</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" icon="paper-airplane" wire:click="sendQuote"
                    class="cursor-pointer">
                    <span wire:loading.remove wire:target="sendQuote">Confirm & Send</span>
                    <span wire:loading wire:target="sendQuote">Sending...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Upload SAP PDF --}}
    <flux:modal name="upload-sap-pdf" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Upload Quotation PDF</flux:heading>
                <flux:text class="mt-1">Upload a prepared quotation PDF. This replaces any existing document.
                </flux:text>
            </div>

            <form wire:submit="uploadSapPdf" class="space-y-5">
                <div>
                    <flux:label>PDF File</flux:label>
                    <input type="file" wire:model="sapPdfUpload" accept=".pdf"
                        class="mt-1 block w-full text-sm text-zinc-500
                            file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0
                            file:text-sm file:font-medium file:bg-zinc-100 file:text-zinc-700
                            hover:file:bg-zinc-200 dark:file:bg-zinc-800 dark:file:text-zinc-300
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

                <div class="flex justify-end gap-3 pt-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" icon="arrow-up-tray"
                        :disabled="!$sapPdfUpload">
                        <span wire:loading.remove wire:target="uploadSapPdf">Upload PDF</span>
                        <span wire:loading wire:target="uploadSapPdf">Uploading...</span>
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Cancel --}}
    <flux:modal name="cancel-quote" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Cancel Quotation</flux:heading>
                <flux:text class="mt-1">This action cannot be undone. The customer will not be notified automatically.
                </flux:text>
            </div>

            <form wire:submit="cancelQuotation" class="space-y-5">
                <flux:textarea wire:model="cancelNote" label="Reason (optional)"
                    placeholder="Why is this quotation being cancelled?" rows="3" />

                <div class="flex justify-end gap-3">
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

@script
<script>
    $wire.on('open-pdf', ({ url }) => {
        window.open(url, '_blank');
    });
</script>
@endscript
