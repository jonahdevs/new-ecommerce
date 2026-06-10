<?php

use App\Enums\QuoteStatus;
use App\Models\Product;
use App\Models\Quote;
use App\Notifications\Quotes\QuoteDecisionReceived;
use App\Notifications\Quotes\QuoteReadyForReview;
use App\Services\QuoteConversionService;
use App\Services\QuotePdfService;
use App\Settings\QuotationSettings;
use App\Support\TaxCalculator;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Quote — Admin')] class extends Component
{
    #[Locked]
    public Quote $quote;

    public string $notes = '';

    public string $internalNotes = '';

    public string $terms = '';

    public string $expires_at = '';

    public int $shippingCents = 0;

    public string $discountType = '';

    public string $discountValue = '';

    /** @var array<int, array{id: ?int, product_name: string, product_sku: string, unit_price: float|string, quantity: int}> */
    public array $lineItems = [];

    public string $productSearch = '';

    public bool $showSendConfirmation = false;

    public function mount(Quote $quote): void
    {
        $this->quote = $quote->load('items', 'user');
        $this->syncFromQuote();
    }

    private function syncFromQuote(): void
    {
        $this->notes = (string) $this->quote->notes;
        $this->internalNotes = (string) $this->quote->internal_notes;
        $this->terms = (string) $this->quote->terms;
        $this->expires_at = $this->quote->expires_at?->format('Y-m-d')
            ?? now()->addDays(app(QuotationSettings::class)->default_validity_days)->format('Y-m-d');
        $this->shippingCents = (int) $this->quote->shipping_cents;
        $this->discountType = (string) $this->quote->discount_type;
        $this->discountValue = $this->quote->discount_value ? (string) $this->quote->discount_value : '';

        $this->lineItems = $this->quote->items
            ->map(
                fn ($item) => [
                    'id' => $item->id,
                    'product_name' => (string) $item->product_name,
                    'product_sku' => (string) $item->product_sku,
                    'product_model_number' => (string) $item->product_model_number,
                    'unit_price' => $item->unit_price_cents / 100,
                    'quantity' => $item->quantity,
                ],
            )
            ->all();
    }

    #[Computed]
    public function subtotalCents(): int
    {
        return collect($this->lineItems)->sum(fn ($item) => (int) round(((float) $item['unit_price']) * 100) * max(1, (int) $item['quantity']));
    }

    #[Computed]
    public function discountCents(): int
    {
        if (! $this->discountType || ! $this->discountValue || (float) $this->discountValue <= 0) {
            return 0;
        }

        if ($this->discountType === 'percentage') {
            return (int) round($this->subtotalCents * ((float) $this->discountValue / 100));
        }

        return (int) round((float) $this->discountValue * 100);
    }

    #[Computed]
    public function vatRate(): float
    {
        $tax = app(TaxCalculator::class);

        return $tax->enabled() ? $tax->defaultRate() : 0.0;
    }

    #[Computed]
    public function taxInclusive(): bool
    {
        $tax = app(TaxCalculator::class);

        return $tax->enabled() && $tax->pricesIncludeTax();
    }

    #[Computed]
    public function vatCents(): int
    {
        if ($this->vatRate <= 0) {
            return 0;
        }

        return app(TaxCalculator::class)->taxForLine($this->subtotalCents - $this->discountCents, $this->vatRate);
    }

    #[Computed]
    public function totalCents(): int
    {
        $afterDiscount = $this->subtotalCents - $this->discountCents;

        // When prices include tax, VAT is already inside the subtotal — don't add it again.
        $vatAddition = $this->taxInclusive ? 0 : $this->vatCents;

        return max(0, $afterDiscount + $vatAddition + $this->shippingCents);
    }

    /** @return array<int, string> */
    #[Computed]
    public function sendWarnings(): array
    {
        $warnings = [];

        if ($this->totalCents === 0) {
            $warnings[] = 'No pricing has been added — the quote total is zero.';
        } elseif (collect($this->lineItems)->some(fn ($item) => (float) $item['unit_price'] == 0)) {
            $warnings[] = 'One or more line items have no unit price set.';
        }

        return $warnings;
    }

    /** @return Collection<int, Product> */
    #[Computed]
    public function productResults(): Collection
    {
        if (trim($this->productSearch) === '') {
            return collect();
        }

        $term = '%'.$this->productSearch.'%';

        return Product::query()->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('sku', 'like', $term))->limit(8)->get();
    }

    public function addProduct(int $productId): void
    {
        $product = Product::findOrFail($productId);

        $this->lineItems[] = [
            'id' => null,
            'product_name' => $product->name,
            'product_sku' => (string) $product->sku,
            'product_model_number' => (string) $product->model_number,
            'unit_price' => ($product->sale_price ?? ($product->price ?? 0)) / 100,
            'quantity' => 1,
        ];

        $this->productSearch = '';
        unset($this->productResults);
    }

    public function addBlankLine(): void
    {
        $this->lineItems[] = ['id' => null, 'product_name' => '', 'product_sku' => '', 'product_model_number' => '', 'unit_price' => 0, 'quantity' => 1];
    }

    public function removeLine(int $index): void
    {
        unset($this->lineItems[$index]);
        $this->lineItems = array_values($this->lineItems);
    }

    public function save(): void
    {
        $this->validate([
            'notes' => ['nullable', 'string'],
            'internalNotes' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],
            'expires_at' => ['nullable', 'date'],
            'discountType' => ['nullable', 'in:fixed,percentage'],
            'discountValue' => ['nullable', 'numeric', 'min:0'],
            'lineItems' => ['array'],
            'lineItems.*.unit_price' => ['numeric', 'min:0'],
        ]);

        $this->quote->update([
            'notes' => $this->notes ?: null,
            'internal_notes' => $this->internalNotes ?: null,
            'terms' => $this->terms ?: null,
            'expires_at' => $this->expires_at ?: null,
            'discount_type' => $this->discountType ?: null,
            'discount_value' => $this->discountValue ?: 0,
            'discount_cents' => $this->discountCents,
            'subtotal_cents' => $this->subtotalCents,
            'vat_cents' => $this->vatCents,
            'vat_rate' => $this->vatRate,
            'tax_inclusive' => $this->taxInclusive,
            'shipping_cents' => $this->shippingCents,
            'total_cents' => $this->totalCents,
        ]);

        $this->quote->items()->delete();
        foreach ($this->lineItems as $item) {
            $unitCents = (int) round(((float) $item['unit_price']) * 100);
            $quantity = max(1, (int) $item['quantity']);

            $this->quote->items()->create([
                'product_snapshot' => [
                    'name' => $item['product_name'],
                    'sku' => $item['product_sku'] ?: null,
                    'model_number' => $item['product_model_number'] ?: null,
                ],
                'unit_price_cents' => $unitCents,
                'quantity' => $quantity,
                'line_total_cents' => $unitCents * $quantity,
            ]);
        }

        $this->quote->refresh()->load('items');
        $this->syncFromQuote();
        $this->productSearch = '';

        Flux::toast(heading: 'Quote saved', text: $this->quote->quote_number.' has been updated.', variant: 'success');
    }

    public function sendToCustomer(): void
    {
        if ($this->quote->items->isEmpty()) {
            Flux::toast(heading: 'Cannot send', text: 'Add at least one line item before sending.', variant: 'warning');

            return;
        }

        if (! ($this->quote->user?->email ?? $this->quote->contact_email)) {
            Flux::toast(heading: 'Cannot send', text: 'No customer email on file.', variant: 'warning');

            return;
        }

        if (! empty($this->sendWarnings)) {
            $this->showSendConfirmation = true;

            return;
        }

        $this->dispatchSend();
    }

    public function confirmSend(): void
    {
        $this->showSendConfirmation = false;
        $this->dispatchSend();
    }

    private function dispatchSend(): void
    {
        $this->quote->update([
            'status' => QuoteStatus::AWAITING_APPROVAL,
            'subtotal_cents' => $this->subtotalCents,
            'discount_cents' => $this->discountCents,
            'vat_cents' => $this->vatCents,
            'vat_rate' => $this->vatRate,
            'tax_inclusive' => $this->taxInclusive,
            'shipping_cents' => $this->shippingCents,
            'total_cents' => $this->totalCents,
            'sent_at' => now(),
        ]);

        $this->quote->refresh()->load('items');
        $this->syncFromQuote();

        app(QuotePdfService::class)->generate($this->quote);

        $this->quote->notifyContact(new QuoteReadyForReview($this->quote));

        Flux::toast(heading: 'Quote sent', text: $this->quote->quote_number.' has been sent to the customer for review.', variant: 'success');
    }

    public function resend(): void
    {
        if (! ($this->quote->user?->email ?? $this->quote->contact_email)) {
            Flux::toast(heading: 'Cannot resend', text: 'No customer email on file.', variant: 'warning');

            return;
        }

        $this->quote->notifyContact(new QuoteReadyForReview($this->quote));

        Flux::toast(heading: 'Quote resent', text: $this->quote->quote_number.' has been resent to the customer.', variant: 'success');
    }

    public function approve(): void
    {
        $this->quote->update(['status' => QuoteStatus::APPROVED]);
        $this->quote->refresh()->load('items');
        $this->syncFromQuote();

        Flux::toast(heading: 'Quote approved', text: $this->quote->quote_number.' has been approved.', variant: 'success');
    }

    public function decline(): void
    {
        $this->quote->update(['status' => QuoteStatus::DECLINED]);
        $this->quote->refresh()->load('items');
        $this->syncFromQuote();

        $this->quote->notifyContact(new QuoteDecisionReceived($this->quote));

        Flux::toast(heading: 'Quote declined', text: $this->quote->quote_number.' has been declined and the customer notified.', variant: 'success');
    }

    public function convertToOrder(): void
    {
        $order = app(QuoteConversionService::class)->convert($this->quote);

        $this->redirectRoute('admin.orders.show', $order, navigate: true);
    }

    public function resetTermsToDefault(): void
    {
        $this->terms = (string) app(QuotationSettings::class)->quote_terms;
    }
}; ?>

<div>
    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('admin.quotes.index')" wire:navigate>Quotes</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $quote->quote_number }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <form wire:submit="save">

        {{-- Page header --}}
        <div class="mt-2 flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <flux:heading size="xl" class="font-mono">{{ $quote->quote_number }}</flux:heading>
                    <flux:badge :color="$quote->status->badgeColor()">{{ $quote->status->label() }}</flux:badge>
                </div>
                <flux:subheading class="mt-1">
                    Created {{ $quote->created_at->format('d F Y') }}
                    @if ($quote->expires_at)
                        · <span class="{{ $quote->expires_at->isPast() ? 'text-red-500' : '' }}">
                            Expires {{ $quote->expires_at->format('M j, Y') }}
                        </span>
                    @endif
                </flux:subheading>
            </div>

            <div class="flex items-center gap-2">
                <flux:button size="sm" variant="ghost" icon="eye"
                    :href="route('admin.quotes.preview', $quote)" wire:navigate type="button">Preview</flux:button>

                @if ($quote->status === App\Enums\QuoteStatus::DRAFT)
                    <flux:button variant="primary" icon="paper-airplane" wire:click="sendToCustomer" type="button">
                        Send to customer</flux:button>
                @elseif (in_array($quote->status, [App\Enums\QuoteStatus::SENT, App\Enums\QuoteStatus::AWAITING_APPROVAL]))
                    <flux:button variant="ghost" icon="arrow-path" wire:click="resend" type="button">Resend</flux:button>
                    <flux:button variant="ghost" icon="x-mark" wire:click="decline" type="button"
                        wire:confirm="Decline this quote? The customer will not be able to accept it after this.">Decline</flux:button>
                    <flux:button variant="primary" icon="check" wire:click="approve" type="button">Approve</flux:button>
                @elseif ($quote->status === App\Enums\QuoteStatus::APPROVED)
                    @if ($quote->order_id)
                        <flux:button variant="ghost" icon="shopping-cart" type="button"
                            :href="route('admin.orders.show', $quote->order_id)" wire:navigate>
                            View order</flux:button>
                    @else
                        <flux:button variant="primary" icon="shopping-cart" wire:click="convertToOrder" type="button">
                            Convert to order</flux:button>
                    @endif
                @endif

                <flux:button type="submit" variant="primary" icon="check">Save</flux:button>
            </div>
        </div>

        {{-- Main layout --}}
        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- Left column --}}
            <div class="space-y-6 lg:col-span-2">

                {{-- Line items --}}
                <flux:card class="overflow-hidden p-0">
                    <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                        <flux:heading size="sm">Line items</flux:heading>
                    </div>

                    {{-- Product search --}}
                    <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                        <div class="relative max-w-sm">
                            <flux:input wire:model.live.debounce.300ms="productSearch"
                                placeholder="Search catalog to add a product…" icon="magnifying-glass" clearable />
                            @if ($this->productResults->isNotEmpty())
                                <div
                                    class="absolute z-10 mt-1 w-full overflow-hidden rounded-md border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-800">
                                    @foreach ($this->productResults as $product)
                                        <button type="button" wire:click="addProduct({{ $product->id }})"
                                            class="flex w-full items-center justify-between px-3 py-2 text-left text-sm hover:bg-zinc-50 dark:hover:bg-zinc-700">
                                            <span>
                                                <span class="font-medium dark:text-white">{{ $product->name }}</span>
                                                <span class="ml-1.5 text-xs text-zinc-400">{{ $product->sku }}</span>
                                            </span>
                                            <flux:icon.plus variant="micro" class="size-4 text-zinc-400" />
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    <flux:table
                        container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
                        <flux:table.columns class="bg-zinc-50 dark:bg-zinc-800/60">
                            <flux:table.column>Product</flux:table.column>
                            <flux:table.column class="w-32">SKU</flux:table.column>
                            <flux:table.column class="w-36" align="end">Unit price</flux:table.column>
                            <flux:table.column class="w-20" align="end">Qty</flux:table.column>
                            <flux:table.column class="w-36" align="end">Line total</flux:table.column>
                            <flux:table.column class="w-10"></flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @forelse ($lineItems as $index => $item)
                                @php $lineTotal = (int) round(((float) $item['unit_price']) * 100) * max(1, (int) $item['quantity']); @endphp
                                <flux:table.row :key="'line-'.$index">
                                    <flux:table.cell>
                                        <span class="font-medium dark:text-white">{{ $item['product_name'] }}</span>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <span class="font-mono text-xs text-zinc-400">{{ $item['product_sku'] ?: '—' }}</span>
                                    </flux:table.cell>
                                    <flux:table.cell align="end">
                                        <flux:input size="sm"
                                            wire:model.blur="lineItems.{{ $index }}.unit_price"
                                            type="number" min="0" step="0.01" class="text-right" />
                                    </flux:table.cell>
                                    <flux:table.cell align="end">
                                        <span class="tabular-nums text-zinc-500">{{ $item['quantity'] }}</span>
                                    </flux:table.cell>
                                    <flux:table.cell align="end" class="font-semibold tabular-nums">
                                        {!! money($lineTotal) !!}
                                    </flux:table.cell>
                                    <flux:table.cell align="end">
                                        <flux:button size="xs" variant="ghost" icon="trash-2" tooltip="Remove line"
                                            wire:click="removeLine({{ $index }})" type="button"
                                            wire:confirm="Remove this line item from the quote?"
                                            class="text-red-500! hover:text-red-600!" />
                                    </flux:table.cell>
                                </flux:table.row>
                            @empty
                                <flux:table.row>
                                    <flux:table.cell colspan="6"
                                        class="py-10 text-center text-sm text-zinc-400">
                                        No line items yet. Search for a product above to add one.
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforelse
                        </flux:table.rows>
                    </flux:table>

                    {{-- Summary --}}
                    <div class="flex justify-end border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                        <div class="w-72 space-y-2 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-zinc-500 dark:text-zinc-400">Subtotal</span>
                                <span class="tabular-nums font-medium dark:text-white">{!! money($this->subtotalCents) !!}</span>
                            </div>

                            {{-- Discount --}}
                            <div class="flex items-center gap-2">
                                <span class="shrink-0 text-zinc-500 dark:text-zinc-400">Discount</span>
                                <div class="ml-auto flex items-center gap-1.5">
                                    <flux:select wire:model.live="discountType" class="w-28!" size="sm">
                                        <flux:select.option value="">None</flux:select.option>
                                        <flux:select.option value="percentage">%</flux:select.option>
                                        <flux:select.option value="fixed">Fixed</flux:select.option>
                                    </flux:select>
                                    @if ($discountType)
                                        <flux:input size="sm" wire:model.blur="discountValue"
                                            type="number" min="0"
                                            :placeholder="$discountType === 'percentage' ? '0' : '0.00'"
                                            class="w-20! text-right" />
                                        @if ($this->discountCents > 0)
                                            <span class="tabular-nums text-red-500">−{!! money($this->discountCents) !!}</span>
                                        @endif
                                    @endif
                                </div>
                            </div>

                            @if ($quote->delivery_required)
                                <div class="flex items-center justify-between">
                                    <span class="text-zinc-500 dark:text-zinc-400">Shipping</span>
                                    <flux:input size="sm" wire:model.blur="shippingCents"
                                        type="number" min="0" step="1" class="w-fit! text-right" />
                                </div>
                            @endif
                            @if ($this->vatRate > 0)
                                <div class="flex items-center justify-between">
                                    <span class="text-zinc-500 dark:text-zinc-400">VAT ({{ $this->vatRate }}%)</span>
                                    <span class="tabular-nums dark:text-white">{!! money($this->vatCents) !!}</span>
                                </div>
                            @endif
                            <div
                                class="flex items-center justify-between border-t border-zinc-200 pt-2 dark:border-zinc-700">
                                <span class="font-semibold dark:text-white">Total</span>
                                <span class="text-lg font-bold text-brand-500 tabular-nums">{!! money($this->totalCents) !!}</span>
                            </div>
                        </div>
                    </div>
                </flux:card>

                {{-- Instructions & Notes --}}
                <flux:card class="overflow-hidden p-0">
                    <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                        <flux:heading size="sm">Instructions & notes</flux:heading>
                    </div>
                    <div class="space-y-6 p-6">

                        {{-- Customer instructions (read from submission) --}}
                        <div>
                            <flux:label class="mb-1.5">Customer instructions</flux:label>
                            <flux:text size="sm" class="mb-2 text-zinc-400">Written by the customer when submitting the request.</flux:text>
                            @if ($quote->notes)
                                <div class="rounded-md border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm whitespace-pre-line text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">{{ $quote->notes }}</div>
                            @else
                                <div class="rounded-md border border-dashed border-zinc-200 px-4 py-3 text-sm text-zinc-400 dark:border-zinc-700">No instructions provided by the customer.</div>
                            @endif
                        </div>

                        <flux:separator />

                        {{-- Quote terms / instructions (system default, per-quote overridable) --}}
                        <div>
                            <div class="mb-1.5 flex items-center justify-between gap-3">
                                <flux:label>Quote terms & instructions</flux:label>
                                <flux:button size="xs" variant="ghost" icon="arrow-path" type="button"
                                    wire:click="resetTermsToDefault" tooltip="Reset to global default from settings">
                                    Reset to default
                                </flux:button>
                            </div>
                            <flux:text size="sm" class="mb-2 text-zinc-400">Shown on the quote document. Defaults from global quotation settings but can be overridden per quote.</flux:text>
                            <flux:textarea wire:model="terms" rows="4"
                                placeholder="Payment terms, warranty conditions, validity notice…" />
                        </div>

                        <flux:separator />

                        {{-- Internal notes --}}
                        <flux:textarea wire:model="internalNotes" label="Internal notes"
                            description="Admin only — never shown to the customer."
                            rows="3" placeholder="Private pricing notes, sourcing details, follow-up reminders…" />
                    </div>
                </flux:card>

            </div>

            {{-- Right sidebar --}}
            <aside class="space-y-6">

                {{-- Status & expiry --}}
                <flux:card class="overflow-hidden p-0">
                    <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                        <flux:heading size="sm">Quote details</flux:heading>
                    </div>
                    <div class="space-y-4 p-6">
                        <flux:input wire:model="expires_at" type="date" label="Valid until" />
                    </div>
                </flux:card>

                {{-- Customer (read-only) --}}
                <flux:card class="overflow-hidden p-0">
                    <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                        <flux:heading size="sm">Customer</flux:heading>
                    </div>
                    <div class="space-y-4 p-6">
                        @if ($quote->user)
                            <div class="flex items-center gap-3">
                                <flux:avatar :name="$quote->user->name" :initials="$quote->user->initials()"
                                    size="sm" />
                                <div class="min-w-0">
                                    <a href="{{ route('admin.customers.show', $quote->user) }}" wire:navigate
                                        class="block truncate text-sm font-medium hover:text-brand-500 dark:text-white">
                                        {{ $quote->user->name }}
                                    </a>
                                    <div class="truncate text-xs text-zinc-500">{{ $quote->user->email }}</div>
                                </div>
                            </div>
                        @endif

                        @php
                            $contactName = $quote->contact_name ?? $quote->user?->name;
                            $contactEmail = $quote->contact_email ?? $quote->user?->email;
                        @endphp

                        @if ($contactName || $contactEmail || $quote->contact_phone || $quote->contact_company)
                            @if ($quote->user)
                                <flux:separator />
                            @endif
                            <div class="space-y-1.5 text-sm">
                                @if ($contactName && !$quote->user)
                                    <div class="font-medium dark:text-white">{{ $contactName }}</div>
                                @endif
                                @if ($contactEmail && !$quote->user)
                                    <div class="text-zinc-500">{{ $contactEmail }}</div>
                                @endif
                                @if ($quote->contact_phone)
                                    <div class="text-zinc-500">{{ $quote->contact_phone }}</div>
                                @endif
                                @if ($quote->contact_company)
                                    <div class="text-xs text-zinc-400">{{ $quote->contact_company }}</div>
                                @endif
                            </div>
                        @elseif (!$quote->user)
                            <flux:text size="sm" class="text-zinc-400">No contact details.</flux:text>
                        @endif
                    </div>
                </flux:card>

                {{-- Delivery preference (read-only) --}}
                <flux:card class="overflow-hidden p-0">
                    <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                        <flux:heading size="sm">Delivery preference</flux:heading>
                    </div>
                    <div class="p-6">
                        @if ($quote->delivery_required)
                            <div class="flex items-start gap-2.5">
                                <flux:icon.truck variant="micro" class="mt-0.5 size-4 shrink-0 text-zinc-400" />
                                <div class="space-y-1">
                                    <flux:text size="sm" class="font-medium dark:text-white">Delivery requested</flux:text>
                                    @if ($quote->delivery_address)
                                        <flux:text size="sm" class="text-zinc-500 whitespace-pre-line">{{ $quote->delivery_address }}</flux:text>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="flex items-center gap-2.5">
                                <flux:icon.building-storefront variant="micro" class="size-4 shrink-0 text-zinc-400" />
                                <flux:text size="sm" class="text-zinc-500">No delivery — collection only</flux:text>
                            </div>
                        @endif
                    </div>
                </flux:card>

            </aside>
        </div>
    </form>

    {{-- Incomplete quote confirmation modal --}}
    <flux:modal wire:model.self="showSendConfirmation" class="md:w-[480px]">
        <flux:heading>Send incomplete quote?</flux:heading>
        <flux:subheading>This quote has the following issues. Are you sure you want to send it to the customer now?</flux:subheading>

        <ul class="mt-4 space-y-2">
            @foreach ($this->sendWarnings as $warning)
                <li class="flex items-start gap-2 text-sm text-amber-700 dark:text-amber-400">
                    <flux:icon.exclamation-triangle variant="micro" class="mt-0.5 size-4 shrink-0" />
                    {{ $warning }}
                </li>
            @endforeach
        </ul>

        <div class="mt-6 flex justify-end gap-3">
            <flux:modal.close>
                <flux:button variant="ghost">Cancel</flux:button>
            </flux:modal.close>
            <flux:button variant="primary" icon="paper-airplane" wire:click="confirmSend">
                Send anyway
            </flux:button>
        </div>
    </flux:modal>
</div>
