<?php

use App\Enums\QuoteStatus;
use App\Models\Quote;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::app')] #[Title('Quotes — Admin')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $filterStatus = '';

    #[Url]
    public int $perPage = 10;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function quotes()
    {
        return Quote::query()
            ->with('user')
            ->withCount('items')
            ->when($this->search, function ($query) {
                $term = '%'.$this->search.'%';
                $query->where(function ($q) use ($term) {
                    $q->where('quote_number', 'like', $term)
                        ->orWhere('title', 'like', $term)
                        ->orWhere('contact_name', 'like', $term)
                        ->orWhere('contact_email', 'like', $term)
                        ->orWhere('contact_company', 'like', $term)
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term)->orWhere('email', 'like', $term));
                });
            })
            ->when($this->filterStatus !== '', fn ($q) => $q->where('status', $this->filterStatus))
            ->latest()
            ->paginate($this->perPage);
    }

    /** @return array<string, int> */
    #[Computed]
    public function stats(): array
    {
        return [
            'sent' => Quote::where('status', QuoteStatus::SENT)->count(),
            'awaiting' => Quote::where('status', QuoteStatus::AWAITING_APPROVAL)->count(),
            'approved' => Quote::where('status', QuoteStatus::APPROVED)->count(),
        ];
    }

    /** @return array<int, QuoteStatus> */
    public function statuses(): array
    {
        return QuoteStatus::cases();
    }
}; ?>

<div>
    <div class="flex items-center justify-between">
        <div>
            @push('breadcrumbs')
<flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Quotes</flux:breadcrumbs.item>
            </flux:breadcrumbs>
@endpush
            <flux:heading size="xl">Quotes</flux:heading>
            <flux:subheading>Price and respond to quotation requests.</flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" :href="route('admin.quotes.create')" wire:navigate>New quote</flux:button>
    </div>

    {{-- Stat tiles --}}
    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <flux:card class="flex items-center gap-4">
            <flux:icon.paper-airplane class="size-9 text-blue-400" />
            <div>
                <div class="text-2xl font-semibold tabular-nums dark:text-white">{{ $this->stats['sent'] }}</div>
                <flux:text size="sm">Sent to customer</flux:text>
            </div>
        </flux:card>
        <flux:card class="flex items-center gap-4">
            <flux:icon.clock class="size-9 text-amber-400" />
            <div>
                <div class="text-2xl font-semibold tabular-nums dark:text-white">{{ $this->stats['awaiting'] }}</div>
                <flux:text size="sm">Awaiting approval</flux:text>
            </div>
        </flux:card>
        <flux:card class="flex items-center gap-4">
            <flux:icon.check-circle class="size-9 text-emerald-400" />
            <div>
                <div class="text-2xl font-semibold tabular-nums dark:text-white">{{ $this->stats['approved'] }}</div>
                <flux:text size="sm">Approved</flux:text>
            </div>
        </flux:card>
    </div>

    <flux:card class="mt-6 p-0 overflow-hidden">

        {{-- Toolbar --}}
        <div class="flex items-center justify-between gap-4 border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search quote #, title or customer…"
                icon="magnifying-glass"
                clearable
                class="max-w-xs" />

            <div class="flex items-center gap-2">
                <flux:select wire:model.live="filterStatus" class="w-48">
                    <flux:select.option value="">All statuses</flux:select.option>
                    @foreach ($this->statuses() as $status)
                        <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="perPage" class="w-28">
                    <flux:select.option value="10">10 / page</flux:select.option>
                    <flux:select.option value="25">25 / page</flux:select.option>
                    <flux:select.option value="50">50 / page</flux:select.option>
                    <flux:select.option value="100">100 / page</flux:select.option>
                    <flux:select.option value="250">250 / page</flux:select.option>
                </flux:select>
            </div>
        </div>

        <flux:table
            container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
            <flux:table.columns class="bg-zinc-50 dark:bg-zinc-800/60">
                <flux:table.column>Quote</flux:table.column>
                <flux:table.column>Customer</flux:table.column>
                <flux:table.column align="end">Items</flux:table.column>
                <flux:table.column align="end">Total</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end">Expires</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->quotes as $quote)
                    <flux:table.row :key="$quote->id">
                        <flux:table.cell variant="strong">
                            <span class="font-mono">{{ $quote->quote_number }}</span>
                            <span class="block text-xs font-normal text-zinc-400">{{ Str::limit($quote->title, 40) }}</span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="font-medium text-sm dark:text-white">
                                {{ $quote->user?->name ?? $quote->contact_name ?? '—' }}
                            </div>
                            <div class="text-xs text-zinc-500">{{ $quote->user?->email ?? $quote->contact_email }}</div>
                        </flux:table.cell>
                        <flux:table.cell align="end" class="tabular-nums text-zinc-500">{{ $quote->items_count }}</flux:table.cell>
                        <flux:table.cell align="end" class="font-medium tabular-nums">{!! money($quote->total_cents) !!}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" inset="top bottom" :color="$quote->status->badgeColor()">
                                {{ $quote->status->label() }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end" class="text-sm">
                            @if ($quote->expires_at)
                                <span class="{{ $quote->expires_at->isPast() ? 'text-red-500' : 'text-zinc-500' }}">
                                    {{ $quote->expires_at->format('M j, Y') }}
                                </span>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:button size="xs" variant="ghost" icon="eye" tooltip="View quote" :href="route('admin.quotes.show', $quote)" wire:navigate />
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="py-12 text-center text-zinc-400">
                            No quotes found.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if ($this->quotes->hasPages())
            <div class="border-t border-zinc-200 px-6 pb-3 dark:border-zinc-700">
                <flux:pagination :paginator="$this->quotes" />
            </div>
        @endif
    </flux:card>
</div>
