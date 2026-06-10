<?php

use App\Enums\QuoteStatus;
use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('New Quote — Admin')] class extends Component {
    public string $notes = '';
    public string $expires_at = '';
    public string $contact_name = '';
    public string $contact_email = '';
    public string $contact_phone = '';
    public string $contact_company = '';
    public string $productSearch = '';
    public string $customerSearch = '';
    public ?int $selectedUserId = null;

    /** @var array<int, array{product_name: string, product_sku: string, product_model_number: string, unit_price: float|string, quantity: int}> */
    public array $lineItems = [];

    public function mount(): void
    {
        $this->expires_at = now()->addDays(app(\App\Settings\QuotationSettings::class)->default_validity_days)->format('Y-m-d');
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

    /** @return Collection<int, User> */
    #[Computed]
    public function customerResults(): Collection
    {
        if (strlen(trim($this->customerSearch)) < 2) {
            return collect();
        }

        $term = '%'.$this->customerSearch.'%';

        return User::query()
            ->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('email', 'like', $term))
            ->limit(6)
            ->get();
    }

    #[Computed]
    public function selectedUser(): ?User
    {
        return $this->selectedUserId ? User::find($this->selectedUserId) : null;
    }

    #[Computed]
    public function totalCents(): int
    {
        return collect($this->lineItems)->sum(
            fn ($item) => (int) round(((float) $item['unit_price']) * 100) * max(1, (int) $item['quantity'])
        );
    }

    public function selectCustomer(int $userId): void
    {
        $user = User::findOrFail($userId);
        $this->selectedUserId = $userId;
        $this->contact_name = $user->name;
        $this->contact_email = $user->email;
        $this->customerSearch = '';
        unset($this->customerResults);
    }

    public function clearCustomer(): void
    {
        $this->selectedUserId = null;
        $this->contact_name = '';
        $this->contact_email = '';
        unset($this->selectedUser);
    }

    public function addProduct(int $productId): void
    {
        $product = Product::findOrFail($productId);

        $this->lineItems[] = [
            'product_name' => $product->name,
            'product_sku' => (string) $product->sku,
            'product_model_number' => (string) $product->model_number,
            'unit_price' => ($product->sale_price ?? $product->price ?? 0) / 100,
            'quantity' => 1,
        ];

        $this->productSearch = '';
        unset($this->productResults);
    }

    public function addBlankLine(): void
    {
        $this->lineItems[] = ['product_name' => '', 'product_sku' => '', 'product_model_number' => '', 'unit_price' => 0, 'quantity' => 1];
    }

    public function removeLine(int $index): void
    {
        unset($this->lineItems[$index]);
        $this->lineItems = array_values($this->lineItems);
    }

    public function create(): void
    {
        $this->validate([
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

        $quote = Quote::create([
            'user_id' => $this->selectedUserId,
            'quote_number' => Quote::generateNumber(),
            'status' => QuoteStatus::DRAFT,
            'contact_name' => $this->contact_name ?: null,
            'contact_email' => $this->contact_email ?: null,
            'contact_phone' => $this->contact_phone ?: null,
            'contact_company' => $this->contact_company ?: null,
            'notes' => $this->notes ?: null,
            'expires_at' => $this->expires_at ?: null,
            'total_cents' => $this->totalCents,
        ]);

        foreach ($this->lineItems as $item) {
            $unitCents = (int) round(((float) $item['unit_price']) * 100);
            $quantity = max(1, (int) $item['quantity']);

            $quote->items()->create([
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

        $this->redirectRoute('admin.quotes.show', $quote, navigate: true);
    }
}; ?>

<div>
    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('admin.quotes.index')" wire:navigate>Quotes</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>New quote</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <form wire:submit="create">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="xl">New quote</flux:heading>
                <flux:subheading>Create an admin-initiated quote for a customer.</flux:subheading>
            </div>
            <div class="flex items-center gap-3">
                <flux:button variant="ghost" :href="route('admin.quotes.index')" wire:navigate>Cancel</flux:button>
                <flux:button type="submit" variant="primary" icon="document-plus">Create quote</flux:button>
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
                        <flux:textarea wire:model="notes" label="Notes" rows="3" placeholder="Internal notes or terms shown to the customer." />
                    </div>
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
                                    <flux:table.cell align="end" class="font-medium tabular-nums">{!! money($lineTotal) !!}</flux:table.cell>
                                    <flux:table.cell align="end">
                                        <flux:button size="xs" variant="ghost" icon="trash-2" tooltip="Remove line" wire:click="removeLine({{ $index }})" type="button"
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
                            <span class="ml-3 text-xl font-semibold text-brand-500 tabular-nums">{!! money($this->totalCents) !!}</span>
                        </div>
                    </div>
                </flux:card>
            </div>

            {{-- Sidebar --}}
            <aside class="w-full shrink-0 space-y-6 lg:w-80">

                {{-- Customer --}}
                <flux:card class="p-0 overflow-hidden">
                    <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                        <flux:heading size="sm">Customer</flux:heading>
                    </div>
                    <div class="space-y-4 p-6">
                    @if ($this->selectedUser)
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <flux:avatar :name="$this->selectedUser->name" :initials="$this->selectedUser->initials()" size="sm" />
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-medium dark:text-white">{{ $this->selectedUser->name }}</div>
                                    <div class="truncate text-xs text-zinc-500">{{ $this->selectedUser->email }}</div>
                                </div>
                            </div>
                            <flux:button size="xs" variant="ghost" icon="x-mark" tooltip="Remove" wire:click="clearCustomer" type="button" />
                        </div>
                        <flux:separator />
                    @else
                        <div class="relative">
                            <flux:input
                                wire:model.live.debounce.300ms="customerSearch"
                                placeholder="Search customer by name or email…"
                                icon="magnifying-glass"
                                clearable />
                            @if ($this->customerResults->isNotEmpty())
                                <div class="absolute z-10 mt-1 w-full overflow-hidden rounded-md border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-800">
                                    @foreach ($this->customerResults as $user)
                                        <button type="button" wire:click="selectCustomer({{ $user->id }})"
                                            class="flex w-full items-center gap-3 px-3 py-2 text-left text-sm hover:bg-zinc-100 dark:hover:bg-zinc-700">
                                            <flux:avatar :name="$user->name" :initials="$user->initials()" size="xs" />
                                            <span>
                                                <span class="font-medium dark:text-white">{{ $user->name }}</span>
                                                <span class="block text-xs text-zinc-400">{{ $user->email }}</span>
                                            </span>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif

                    <flux:input wire:model="contact_name" label="Contact name" placeholder="Full name" />
                    <flux:input wire:model="contact_email" type="email" label="Contact email" placeholder="email@example.com" />
                    <flux:input wire:model="contact_phone" label="Contact phone" placeholder="+254 7XX XXX XXX" />
                    <flux:input wire:model="contact_company" label="Company" placeholder="Company name" />
                    </div>
                </flux:card>

                {{-- Quote settings --}}
                <flux:card class="p-0 overflow-hidden">
                    <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                        <flux:heading size="sm">Settings</flux:heading>
                    </div>
                    <div class="p-6">
                        <flux:input wire:model="expires_at" type="date" label="Expires on" />
                    </div>
                </flux:card>

            </aside>
        </div>
    </form>
</div>
