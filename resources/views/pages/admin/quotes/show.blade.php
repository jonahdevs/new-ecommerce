<?php

use App\Enums\OrderStatus;
use App\Enums\QuoteStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\Quote;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Quote — Admin')] class extends Component {
    #[Locked]
    public Quote $quote;

    public bool $isEditing = false;

    public string $title = '';
    public string $status = '';
    public string $contact_name = '';
    public string $contact_email = '';
    public string $contact_phone = '';
    public string $contact_company = '';
    public string $notes = '';
    public string $expires_at = '';

    /** @var array<int, array{id: ?int, product_name: string, product_sku: string, unit_price: float|string, quantity: int}> */
    public array $lineItems = [];

    public string $productSearch = '';

    public function mount(Quote $quote): void
    {
        $this->quote = $quote->load('items', 'user');
        $this->syncFromQuote();
    }

    private function syncFromQuote(): void
    {
        $this->title = $this->quote->title;
        $this->status = $this->quote->status->value;
        $this->contact_name = (string) $this->quote->contact_name;
        $this->contact_email = (string) $this->quote->contact_email;
        $this->contact_phone = (string) $this->quote->contact_phone;
        $this->contact_company = (string) $this->quote->contact_company;
        $this->notes = (string) $this->quote->notes;
        $this->expires_at = $this->quote->expires_at?->format('Y-m-d') ?? '';

        $this->lineItems = $this->quote->items->map(fn ($item) => [
            'id' => $item->id,
            'product_name' => $item->product_name,
            'product_sku' => (string) $item->product_sku,
            'unit_price' => $item->unit_price_cents / 100,
            'quantity' => $item->quantity,
        ])->all();
    }

    public function edit(): void
    {
        $this->isEditing = true;
    }

    public function cancelEdit(): void
    {
        $this->syncFromQuote();
        $this->productSearch = '';
        $this->isEditing = false;
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
        $this->syncFromQuote();
        $this->productSearch = '';
        $this->isEditing = false;

        Flux::toast(heading: 'Quote saved', text: $this->quote->quote_number.' has been updated.', variant: 'success');
    }

    public function sendToCustomer(): void
    {
        if ($this->quote->items->isEmpty()) {
            Flux::toast(heading: 'Cannot send', text: 'Add at least one line item before sending.', variant: 'warning');
            return;
        }

        $email = $this->quote->user?->email ?? $this->quote->contact_email;

        if (! $email) {
            Flux::toast(heading: 'Cannot send', text: 'Add a customer email before sending.', variant: 'warning');
            return;
        }

        $this->quote->update(['status' => QuoteStatus::SENT]);
        $this->quote->refresh()->load('items');
        $this->syncFromQuote();

        Flux::toast(heading: 'Quote sent', text: $this->quote->quote_number.' has been marked as sent to the customer.', variant: 'success');
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

        Flux::toast(heading: 'Quote declined', text: $this->quote->quote_number.' has been declined.', variant: 'success');
    }

    public function convertToOrder(): void
    {
        $sequence = Order::whereYear('created_at', now()->year)->count() + 1;
        $orderNumber = 'SHF-'.now()->year.'-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);

        $order = Order::create([
            'user_id' => $this->quote->user_id,
            'order_number' => $orderNumber,
            'status' => OrderStatus::PENDING,
            'subtotal_cents' => $this->quote->total_cents,
            'vat_cents' => 0,
            'delivery_cents' => 0,
            'installation_cents' => 0,
            'total_cents' => $this->quote->total_cents,
            'notes' => 'Converted from quote '.$this->quote->quote_number,
        ]);

        foreach ($this->quote->items as $item) {
            $order->items()->create([
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'product_sku' => $item->product_sku,
                'unit_price_cents' => $item->unit_price_cents,
                'quantity' => $item->quantity,
                'line_total_cents' => $item->line_total_cents,
            ]);
        }

        $this->redirectRoute('admin.orders.show', $order, navigate: true);
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
    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('admin.quotes.index')" wire:navigate>Quotes</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $quote->quote_number }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <form wire:submit="save">
        <div class="mt-2 flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="xl" class="font-mono">{{ $quote->quote_number }}</flux:heading>
                <flux:subheading>Created {{ $quote->created_at->format('d F Y') }}</flux:subheading>
            </div>

            <div class="flex items-center gap-2">
                @if ($isEditing)
                    <flux:button variant="ghost" wire:click="cancelEdit" type="button">Cancel</flux:button>
                    <flux:button type="submit" variant="primary" icon="check">Save changes</flux:button>
                @else
                    <flux:button size="sm" variant="ghost" icon="pencil-square" tooltip="Edit quote" wire:click="edit" type="button" />

                    @if ($quote->status === App\Enums\QuoteStatus::DRAFT)
                        <flux:button variant="primary" icon="paper-airplane" wire:click="sendToCustomer" type="button">Send to customer</flux:button>
                    @elseif (in_array($quote->status, [App\Enums\QuoteStatus::SENT, App\Enums\QuoteStatus::AWAITING_APPROVAL]))
                        <flux:button variant="ghost" icon="x-mark" wire:click="decline" type="button">Decline</flux:button>
                        <flux:button variant="primary" icon="check" wire:click="approve" type="button">Approve</flux:button>
                    @elseif ($quote->status === App\Enums\QuoteStatus::APPROVED)
                        <flux:button variant="primary" icon="shopping-cart" wire:click="convertToOrder" type="button">Convert to order</flux:button>
                    @endif
                @endif
            </div>
        </div>

        <div class="mt-6 flex flex-col gap-6 lg:flex-row lg:items-start">

            {{-- Main column --}}
            <div class="min-w-0 flex-1 space-y-6">

                {{-- Details --}}
                <flux:card class="p-0 overflow-hidden">
                    <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                        <flux:heading size="sm">Details</flux:heading>
                    </div>
                    <div class="space-y-4 p-6">
                    @if ($isEditing)
                        <flux:input wire:model="title" label="Title" required />
                        <flux:textarea wire:model="notes" label="Notes" rows="3" placeholder="Internal notes or terms shown to the customer." />
                    @else
                        <div>
                            <flux:label>Title</flux:label>
                            <flux:text class="mt-1">{{ $quote->title }}</flux:text>
                        </div>
                        @if ($quote->notes)
                            <div>
                                <flux:label>Notes</flux:label>
                                <flux:text class="mt-1 whitespace-pre-line">{{ $quote->notes }}</flux:text>
                            </div>
                        @endif
                    @endif
                    </div>
                </flux:card>

                {{-- Line items --}}
                <flux:card class="p-0 overflow-hidden">
                    <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                        <flux:heading size="sm">Line items</flux:heading>
                    </div>

                    @if ($isEditing)
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
                    @endif

                    <flux:table
                        container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
                        <flux:table.columns class="bg-zinc-50 dark:bg-zinc-800/60">
                            <flux:table.column>Product</flux:table.column>
                            <flux:table.column class="w-32">SKU</flux:table.column>
                            <flux:table.column class="w-32" align="end">Unit (KES)</flux:table.column>
                            <flux:table.column class="w-20" align="end">Qty</flux:table.column>
                            <flux:table.column class="w-32" align="end">Line total</flux:table.column>
                            @if ($isEditing)
                                <flux:table.column class="w-10"></flux:table.column>
                            @endif
                        </flux:table.columns>
                        <flux:table.rows>
                            @forelse ($lineItems as $index => $item)
                                @php
                                    $lineTotal = (int) round(((float) $item['unit_price']) * 100) * max(1, (int) $item['quantity']);
                                @endphp
                                <flux:table.row :key="'line-'.$index">
                                    <flux:table.cell>
                                        @if ($isEditing)
                                            <flux:input wire:model="lineItems.{{ $index }}.product_name" placeholder="Product name" />
                                        @else
                                            <span class="font-medium dark:text-white">{{ $item['product_name'] }}</span>
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        @if ($isEditing)
                                            <flux:input wire:model="lineItems.{{ $index }}.product_sku" placeholder="—" />
                                        @else
                                            <span class="font-mono text-xs text-zinc-500">{{ $item['product_sku'] ?: '—' }}</span>
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell align="{{ $isEditing ? 'left' : 'end' }}">
                                        @if ($isEditing)
                                            <flux:input wire:model.live.debounce.500ms="lineItems.{{ $index }}.unit_price" type="number" min="0" step="0.01" class="text-right" />
                                        @else
                                            <span class="tabular-nums text-zinc-500">{!! $kes(round((float) $item['unit_price'] * 100)) !!}</span>
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell align="{{ $isEditing ? 'left' : 'end' }}">
                                        @if ($isEditing)
                                            <flux:input wire:model.live.debounce.500ms="lineItems.{{ $index }}.quantity" type="number" min="1" class="text-right" />
                                        @else
                                            <span class="tabular-nums text-zinc-500">{{ $item['quantity'] }}</span>
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell align="end" class="font-medium tabular-nums">{!! $kes($lineTotal) !!}</flux:table.cell>
                                    @if ($isEditing)
                                        <flux:table.cell align="end">
                                            <flux:button size="xs" variant="ghost" icon="trash" tooltip="Remove line" wire:click="removeLine({{ $index }})" type="button"
                                                class="text-red-500! hover:text-red-600!" />
                                        </flux:table.cell>
                                    @endif
                                </flux:table.row>
                            @empty
                                <flux:table.row>
                                    <flux:table.cell colspan="{{ $isEditing ? 6 : 5 }}" class="py-8 text-center text-sm text-zinc-400">
                                        No line items.
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforelse
                        </flux:table.rows>
                    </flux:table>

                    <div class="flex items-center justify-between border-t border-zinc-200 px-6 py-3 dark:border-zinc-700">
                        @if ($isEditing)
                            <flux:button size="sm" variant="ghost" icon="plus" wire:click="addBlankLine" type="button">Add blank line</flux:button>
                        @else
                            <div></div>
                        @endif
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
                <flux:card class="p-0 overflow-hidden">
                    <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                        <flux:heading size="sm">Status</flux:heading>
                        <flux:badge size="sm" :color="$quote->status->badgeColor()">{{ $quote->status->label() }}</flux:badge>
                    </div>
                    <div class="space-y-4 p-6">
                    @if ($isEditing)
                        <flux:select wire:model="status" label="Change status">
                            @foreach ($this->statuses() as $statusOption)
                                <flux:select.option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:input wire:model="expires_at" type="date" label="Expires on" />
                    @else
                        @if ($quote->expires_at)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-zinc-500">Expires</span>
                                <span class="{{ $quote->expires_at->isPast() ? 'text-red-500 font-medium' : 'text-zinc-700 dark:text-zinc-300' }}">
                                    {{ $quote->expires_at->format('M j, Y') }}
                                </span>
                            </div>
                        @else
                            <flux:text size="sm" class="text-zinc-400">No expiry set.</flux:text>
                        @endif
                    @endif
                    </div>
                </flux:card>

                {{-- Customer / contact --}}
                <flux:card class="p-0 overflow-hidden">
                    <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                        <flux:heading size="sm">Customer</flux:heading>
                    </div>
                    <div class="space-y-4 p-6">

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

                    @if ($isEditing)
                        <flux:input wire:model="contact_name" label="Contact name" />
                        <flux:input wire:model="contact_email" type="email" label="Contact email" />
                        <flux:input wire:model="contact_phone" label="Contact phone" />
                        <flux:input wire:model="contact_company" label="Company" />
                    @else
                        @php
                            $contactName = $quote->contact_name ?? $quote->user?->name;
                            $contactEmail = $quote->contact_email ?? $quote->user?->email;
                        @endphp

                        @if ($contactName || $contactEmail || $quote->contact_phone || $quote->contact_company)
                            <div class="space-y-1.5 text-sm">
                                @if ($contactName)
                                    <div class="font-medium dark:text-white">{{ $contactName }}</div>
                                @endif
                                @if ($contactEmail)
                                    <div class="text-zinc-500">{{ $contactEmail }}</div>
                                @endif
                                @if ($quote->contact_phone)
                                    <div class="text-zinc-500">{{ $quote->contact_phone }}</div>
                                @endif
                                @if ($quote->contact_company)
                                    <div class="text-zinc-400 text-xs">{{ $quote->contact_company }}</div>
                                @endif
                            </div>
                        @else
                            <flux:text size="sm" class="text-zinc-400">No contact details.</flux:text>
                        @endif
                    @endif
                    </div>
                </flux:card>

            </aside>
        </div>
    </form>
</div>
