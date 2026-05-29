<?php

use App\Enums\QuoteStatus;
use App\Models\Product;
use App\Models\Quote;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Quote — Admin')] class extends Component {
    #[Locked]
    public Quote $quote;

    public string $title = '';
    public string $status = '';
    public string $contact_name = '';
    public string $contact_email = '';
    public string $contact_phone = '';
    public string $contact_company = '';
    public string $notes = '';
    public string $expires_at = '';

    /**
     * Editable line items.
     *
     * @var array<int, array{id: ?int, product_name: string, product_sku: string, unit_price: float|string, quantity: int}>
     */
    public array $lineItems = [];

    public string $productSearch = '';

    public function mount(Quote $quote): void
    {
        $this->quote = $quote->load('items', 'user');
        $this->title = $quote->title;
        $this->status = $quote->status->value;
        $this->contact_name = (string) $quote->contact_name;
        $this->contact_email = (string) $quote->contact_email;
        $this->contact_phone = (string) $quote->contact_phone;
        $this->contact_company = (string) $quote->contact_company;
        $this->notes = (string) $quote->notes;
        $this->expires_at = $quote->expires_at?->format('Y-m-d') ?? '';

        $this->lineItems = $quote->items->map(fn ($item) => [
            'id' => $item->id,
            'product_name' => $item->product_name,
            'product_sku' => (string) $item->product_sku,
            'unit_price' => $item->unit_price_cents / 100,
            'quantity' => $item->quantity,
        ])->all();
    }

    #[Computed]
    public function totalCents(): int
    {
        return collect($this->lineItems)->sum(
            fn ($item) => (int) round(((float) $item['unit_price']) * 100) * max(1, (int) $item['quantity'])
        );
    }

    /** @return Collection<int, Product> */
    #[Computed]
    public function productResults(): Collection
    {
        if (trim($this->productSearch) === '') {
            return collect();
        }

        $term = '%'.$this->productSearch.'%';

        return Product::query()
            ->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('sku', 'like', $term))
            ->limit(8)
            ->get();
    }

    public function addBlankLine(): void
    {
        $this->lineItems[] = ['id' => null, 'product_name' => '', 'product_sku' => '', 'unit_price' => 0, 'quantity' => 1];
    }

    public function addProduct(int $productId): void
    {
        $product = Product::findOrFail($productId);

        $this->lineItems[] = [
            'id' => null,
            'product_name' => $product->name,
            'product_sku' => (string) $product->sku,
            'unit_price' => ($product->sale_price ?? $product->price ?? 0) / 100,
            'quantity' => 1,
        ];

        $this->productSearch = '';
        unset($this->productResults);
    }

    public function removeLine(int $index): void
    {
        unset($this->lineItems[$index]);
        $this->lineItems = array_values($this->lineItems);
    }

    public function save(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::enum(QuoteStatus::class)],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_company' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'expires_at' => ['nullable', 'date'],
            'lineItems' => ['array'],
            'lineItems.*.product_name' => ['required', 'string', 'max:255'],
            'lineItems.*.unit_price' => ['numeric', 'min:0'],
            'lineItems.*.quantity' => ['integer', 'min:1'],
        ]);

        $this->quote->update([
            'title' => $this->title,
            'status' => $this->status,
            'contact_name' => $this->contact_name ?: null,
            'contact_email' => $this->contact_email ?: null,
            'contact_phone' => $this->contact_phone ?: null,
            'contact_company' => $this->contact_company ?: null,
            'notes' => $this->notes ?: null,
            'expires_at' => $this->expires_at ?: null,
            'total_cents' => $this->totalCents,
        ]);

        // Replace the line items with the current editable set.
        $this->quote->items()->delete();
        foreach ($this->lineItems as $item) {
            $unitCents = (int) round(((float) $item['unit_price']) * 100);
            $quantity = max(1, (int) $item['quantity']);

            $this->quote->items()->create([
                'product_name' => $item['product_name'],
                'product_sku' => $item['product_sku'] ?: null,
                'unit_price_cents' => $unitCents,
                'quantity' => $quantity,
                'line_total_cents' => $unitCents * $quantity,
            ]);
        }

        $this->quote->refresh()->load('items');

        Flux::toast(heading: 'Quote saved', text: $this->quote->quote_number.' has been updated.', variant: 'success');
    }

    /** @return array<int, QuoteStatus> */
    public function statuses(): array
    {
        return QuoteStatus::cases();
    }
}; ?>

@php
    $kes = fn ($cents) => 'KES&nbsp;'.number_format(intdiv((int) $cents, 100), 0, '.', ',');
@endphp

<div>
    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('admin.quotes.index')" wire:navigate>Quotes</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $quote->quote_number }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <form wire:submit="save">
        <div class="mt-2 flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="xl" class="font-mono">{{ $quote->quote_number }}</flux:heading>
                <flux:subheading>Created {{ $quote->created_at->format('d F Y') }}</flux:subheading>
            </div>
            <div class="flex items-center gap-3">
                <flux:badge size="lg" :color="$quote->status->badgeColor()">{{ $quote->status->label() }}</flux:badge>
                <flux:button type="submit" variant="primary" icon="check">Save quote</flux:button>
            </div>
        </div>

        <div class="mt-6 flex flex-col gap-6 lg:flex-row lg:items-start">

            {{-- Main column --}}
            <div class="min-w-0 flex-1 space-y-6">

                {{-- Details --}}
                <flux:card class="space-y-4">
                    <flux:heading size="sm">Details</flux:heading>
                    <flux:input wire:model="title" label="Title" required />
                    <flux:textarea wire:model="notes" label="Notes" rows="3" placeholder="Internal notes or terms shown to the customer." />
                </flux:card>

                {{-- Line items --}}
                <flux:card class="p-0 overflow-hidden">
                    <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                        <flux:heading size="sm">Line items</flux:heading>
                    </div>

                    {{-- Product picker --}}
                    <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                        <div class="relative max-w-md">
                            <flux:input
                                wire:model.live.debounce.300ms="productSearch"
                                placeholder="Search catalog to add a product…"
                                icon="magnifying-glass"
                                clearable />
                            @if ($this->productResults->isNotEmpty())
                                <div class="absolute z-10 mt-1 w-full overflow-hidden rounded-md border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-800">
                                    @foreach ($this->productResults as $product)
                                        <button type="button" wire:click="addProduct({{ $product->id }})"
                                            class="flex w-full items-center justify-between px-3 py-2 text-left text-sm hover:bg-zinc-100 dark:hover:bg-zinc-700">
                                            <span>
                                                <span class="font-medium dark:text-white">{{ $product->name }}</span>
                                                <span class="ml-1 text-xs text-zinc-400">{{ $product->sku }}</span>
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
                            <flux:table.column class="w-32" align="end">Unit (KES)</flux:table.column>
                            <flux:table.column class="w-20" align="end">Qty</flux:table.column>
                            <flux:table.column class="w-32" align="end">Line total</flux:table.column>
                            <flux:table.column class="w-10"></flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @forelse ($lineItems as $index => $item)
                                @php
                                    $lineTotal = (int) round(((float) $item['unit_price']) * 100) * max(1, (int) $item['quantity']);
                                @endphp
                                <flux:table.row :key="'line-'.$index">
                                    <flux:table.cell>
                                        <flux:input wire:model="lineItems.{{ $index }}.product_name" placeholder="Product name" />
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <flux:input wire:model="lineItems.{{ $index }}.product_sku" placeholder="—" />
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <flux:input wire:model.live.debounce.500ms="lineItems.{{ $index }}.unit_price" type="number" min="0" step="0.01" class="text-right" />
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <flux:input wire:model.live.debounce.500ms="lineItems.{{ $index }}.quantity" type="number" min="1" class="text-right" />
                                    </flux:table.cell>
                                    <flux:table.cell align="end" class="font-medium tabular-nums">{!! $kes($lineTotal) !!}</flux:table.cell>
                                    <flux:table.cell align="end">
                                        <flux:button size="xs" variant="ghost" icon="trash" wire:click="removeLine({{ $index }})"
                                            class="text-red-500! hover:text-red-600!" />
                                    </flux:table.cell>
                                </flux:table.row>
                            @empty
                                <flux:table.row>
                                    <flux:table.cell colspan="6" class="py-8 text-center text-sm text-zinc-400">
                                        No line items yet. Search the catalog above or add a blank line.
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforelse
                        </flux:table.rows>
                    </flux:table>

                    <div class="flex items-center justify-between border-t border-zinc-200 px-6 py-3 dark:border-zinc-700">
                        <flux:button size="sm" variant="ghost" icon="plus" wire:click="addBlankLine" type="button">Add blank line</flux:button>
                        <div class="text-right">
                            <span class="text-xs font-bold uppercase tracking-wide text-zinc-500">Total</span>
                            <span class="ml-3 text-xl font-semibold text-brand-500 tabular-nums">{!! $kes($this->totalCents) !!}</span>
                        </div>
                    </div>
                </flux:card>
            </div>

            {{-- Sidebar --}}
            <aside class="w-full shrink-0 space-y-6 lg:w-80">

                {{-- Status & expiry --}}
                <flux:card class="space-y-4">
                    <flux:heading size="sm">Status</flux:heading>
                    <flux:select wire:model="status">
                        @foreach ($this->statuses() as $statusOption)
                            <flux:select.option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="expires_at" type="date" label="Expires" />
                </flux:card>

                {{-- Customer / contact --}}
                <flux:card class="space-y-4">
                    <flux:heading size="sm">Customer</flux:heading>
                    @if ($quote->user)
                        <div class="flex items-center gap-3">
                            <flux:avatar :name="$quote->user->name" :initials="$quote->user->initials()" size="sm" />
                            <div class="min-w-0">
                                <a href="{{ route('admin.customers.show', $quote->user) }}" wire:navigate
                                    class="block truncate text-sm font-medium hover:text-brand-500 dark:text-white">
                                    {{ $quote->user->name }}
                                </a>
                                <div class="truncate text-xs text-zinc-500">{{ $quote->user->email }}</div>
                            </div>
                        </div>
                        <flux:separator />
                    @endif
                    <flux:input wire:model="contact_name" label="Contact name" />
                    <flux:input wire:model="contact_email" type="email" label="Contact email" />
                    <flux:input wire:model="contact_phone" label="Contact phone" />
                    <flux:input wire:model="contact_company" label="Company" />
                </flux:card>
            </aside>
        </div>
    </form>
</div>
