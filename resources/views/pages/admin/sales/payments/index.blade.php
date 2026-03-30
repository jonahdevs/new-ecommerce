<?php

use App\Models\Payment;
use App\Enums\PaymentStatus;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\{Title, Computed};

new #[Title('Transactions')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';
    public string $gatewayFilter = 'all';
    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';
    public int $perPage = 25;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }
    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }
    public function updatingGatewayFilter(): void
    {
        $this->resetPage();
    }
    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    // ── Computed

    #[Computed]
    public function payments()
    {
        return Payment::query()
            ->with(['order', 'order.user'])
            ->when(
                $this->search,
                fn($q) => $q
                    ->where('transaction_id', 'like', "%{$this->search}%")
                    ->orWhereHas('order', fn($q) => $q->where('reference', 'like', "%{$this->search}%"))
                    ->orWhereHas('order.user', fn($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%")),
            )
            ->when($this->statusFilter !== 'all', fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->gatewayFilter !== 'all', fn($q) => $q->where('gateway', $this->gatewayFilter))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    #[Computed]
    public function statusOptions(): array
    {
        $options = ['all' => 'All Payments'];
        foreach (PaymentStatus::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }

    #[Computed]
    public function gatewayOptions(): array
    {
        // Dynamically pull distinct gateways from DB
        return array_merge(['all' => 'All Gateways'], Payment::query()->distinct()->pluck('gateway')->filter()->mapWithKeys(fn($g) => [$g => ucfirst($g)])->toArray());
    }

    #[Computed]
    public function statusCounts(): array
    {
        $counts = Payment::query()->selectRaw('status, count(*) as count')->groupBy('status')->pluck('count', 'status')->toArray();

        return array_merge(['all' => array_sum($counts)], $counts);
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'revenue' => Payment::where('status', PaymentStatus::PAID)->sum('amount_cents') / 100,
            'pending' => Payment::whereIn('status', [PaymentStatus::PENDING->value, PaymentStatus::PROCESSING->value])->sum('amount_cents') / 100,
            'failed' => Payment::where('status', PaymentStatus::FAILED)->count(),
            'total' => Payment::count(),
        ];
    }

    //  Sorting

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->statusFilter = 'all';
        $this->gatewayFilter = 'all';
        $this->resetPage();
    }
};
?>

<div x-data="{
    columns: JSON.parse(localStorage.getItem('payments_columns') ?? 'null') ?? {
        customer: true,
        gateway: true,
        method: true,
        date: true,
    },
    toggleColumn(col) {
        this.columns[col] = !this.columns[col];
        localStorage.setItem('payments_columns', JSON.stringify(this.columns));
    },
}">

    {{-- Breadcrumb --}}
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate />
        <flux:breadcrumbs.item>Transactions</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    {{-- Page header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" class="mb-1">Transactions</flux:heading>
            <flux:subheading>Monitor payment transactions, track revenue, and manage refunds.</flux:subheading>
        </div>
    </div>

    {{-- Stats cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

        <flux:card class="p-4 border-l-4 border-l-emerald-500 rounded-l-none!">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-xs text-zinc-500 uppercase tracking-wide mb-1">
                        Total Revenue
                    </flux:text>
                    <flux:heading size="xl" class="text-2xl! font-bold! text-emerald-600">
                        {{ format_currency($this->stats['revenue']) }}
                    </flux:heading>
                    <flux:text class="text-xs text-zinc-400 mt-1">Paid transactions</flux:text>
                </div>
                <div
                    class="w-10 h-10 rounded-full bg-emerald-50 dark:bg-emerald-900 flex items-center justify-center shrink-0">
                    <flux:icon.banknotes class="size-5 text-emerald-500" />
                </div>
            </div>
        </flux:card>

        <flux:card class="p-4 border-l-4 border-l-amber-500 rounded-l-none!">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-xs text-zinc-500 uppercase tracking-wide mb-1">
                        Pending Value
                    </flux:text>
                    <flux:heading size="xl" class="text-2xl! font-bold! text-amber-600">
                        {{ format_currency($this->stats['pending']) }}
                    </flux:heading>
                    <flux:text class="text-xs text-zinc-400 mt-1">Pending / Processing</flux:text>
                </div>
                <div
                    class="w-10 h-10 rounded-full bg-amber-50 dark:bg-amber-900 flex items-center justify-center shrink-0">
                    <flux:icon.clock class="size-5 text-amber-500" />
                </div>
            </div>
        </flux:card>

        <flux:card class="p-4 border-l-4 border-l-red-500 rounded-l-none!">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-xs text-zinc-500 uppercase tracking-wide mb-1">
                        Failed
                    </flux:text>
                    <flux:heading size="xl" class="text-2xl! font-bold! text-red-600">
                        {{ number_format($this->stats['failed']) }}
                    </flux:heading>
                    <flux:text class="text-xs text-zinc-400 mt-1">Failed transactions</flux:text>
                </div>
                <div class="w-10 h-10 rounded-full bg-red-50 dark:bg-red-900 flex items-center justify-center shrink-0">
                    <flux:icon.exclamation-triangle class="size-5 text-red-500" />
                </div>
            </div>
        </flux:card>

        <flux:card class="p-4 border-l-4 border-l-blue-500 rounded-l-none!">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-xs text-zinc-500 uppercase tracking-wide mb-1">
                        Total Transactions
                    </flux:text>
                    <flux:heading size="xl" class="text-2xl! font-bold!">
                        {{ number_format($this->stats['total']) }}
                    </flux:heading>
                    <flux:text class="text-xs text-zinc-400 mt-1">All time</flux:text>
                </div>
                <div
                    class="w-10 h-10 rounded-full bg-blue-50 dark:bg-blue-900 flex items-center justify-center shrink-0">
                    <flux:icon.credit-card class="size-5 text-blue-500" />
                </div>
            </div>
        </flux:card>

    </div>


    {{-- Main card --}}
    <flux:card class="p-0">

        {{-- Toolbar --}}
        <div class="flex flex-wrap items-center gap-3 px-5 py-3 border-b border-zinc-200 dark:border-zinc-700">

            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass"
                placeholder="Search transaction, order ref, or customer..." class="max-w-xs" clearable />

            <div class="flex items-center gap-2 ms-auto flex-wrap">

                {{-- Gateway filter --}}
                <flux:select wire:model.live="gatewayFilter" class="w-40">
                    @foreach ($this->gatewayOptions as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="statusFilter" class="w-48">
                    <flux:select.option value="all">All Payments</flux:select.option>
                    @foreach (PaymentStatus::cases() as $s)
                        <flux:select.option value="{{ $s->value }}">
                            {{ $s->label() }} ({{ $this->statusCounts[$s->value] ?? 0 }})
                        </flux:select.option>
                    @endforeach
                </flux:select>

                {{-- Per page --}}
                <flux:select wire:model.live="perPage" class="w-24">
                    <flux:select.option value="10">10</flux:select.option>
                    <flux:select.option value="25">25</flux:select.option>
                    <flux:select.option value="50">50</flux:select.option>
                    <flux:select.option value="100">100</flux:select.option>
                </flux:select>

                {{-- Column visibility --}}
                <flux:dropdown>
                    <flux:button icon="view-columns" variant="ghost" size="sm" class="cursor-pointer">
                        Columns
                    </flux:button>
                    <flux:menu>
                        @foreach (['customer' => 'Customer', 'gateway' => 'Gateway', 'method' => 'Payment Method', 'date' => 'Date'] as $col => $colLabel)
                            <flux:menu.item @click.prevent="toggleColumn('{{ $col }}')">
                                <span class="flex items-center gap-2">
                                    <span x-text="columns.{{ $col }} ? '✓' : ''"
                                        class="w-4 text-green-600 font-bold"></span>
                                    {{ $colLabel }}
                                </span>
                            </flux:menu.item>
                        @endforeach
                    </flux:menu>
                </flux:dropdown>

                {{-- Clear filters --}}
                @if ($search || $statusFilter !== 'all' || $gatewayFilter !== 'all')
                    <flux:button wire:click="clearFilters" variant="ghost" size="sm" icon="x-mark"
                        class="cursor-pointer">
                        Clear
                    </flux:button>
                @endif

            </div>
        </div>

        {{-- Table --}}
        <flux:table :paginate="$this->payments">
            <flux:table.columns>

                {{-- Transaction --}}
                <flux:table.column class="ps-4!">Transaction</flux:table.column>

                {{-- Order --}}
                <flux:table.column>Order</flux:table.column>

                {{-- Customer --}}
                <flux:table.column x-show="columns.customer">Customer</flux:table.column>

                {{-- Gateway --}}
                <flux:table.column x-show="columns.gateway">Gateway</flux:table.column>

                {{-- Payment Method --}}
                <flux:table.column x-show="columns.method">Method</flux:table.column>

                {{-- Amount --}}
                <flux:table.column sortable :sorted="$this->sortBy === 'amount_cents'" :direction="$this->sortDirection"
                    wire:click="sort('amount_cents')">
                    Amount
                </flux:table.column>

                {{-- Status --}}
                <flux:table.column>Status</flux:table.column>

                {{-- Date --}}
                <flux:table.column x-show="columns.date" sortable :sorted="$this->sortBy === 'created_at'"
                    :direction="$this->sortDirection" wire:click="sort('created_at')">
                    Date
                </flux:table.column>

                {{-- Actions --}}
                <flux:table.column align="end" class="pe-4!">Actions</flux:table.column>

            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->payments as $payment)
                    <flux:table.row :key="$payment->id">

                        {{-- Transaction ID --}}
                        <flux:table.cell class="ps-4!">
                            <div class="font-mono text-xs text-zinc-700 dark:text-zinc-300 max-w-40 truncate">
                                {{ $payment->transaction_id ?? '—' }}
                            </div>
                            @if ($payment->gateway_order_id && $payment->gateway_order_id !== $payment->transaction_id)
                                <div class="font-mono text-xs text-zinc-400 max-w-40 truncate mt-0.5">
                                    {{ $payment->gateway_order_id }}
                                </div>
                            @endif
                        </flux:table.cell>

                        {{-- Order --}}
                        <flux:table.cell>
                            @if ($payment->order)
                                <a href="{{ route('admin.orders.show', $payment->order) }}" wire:navigate
                                    class="font-semibold text-zinc-800 dark:text-white hover:text-sheffield-red transition-colors">
                                    #{{ $payment->order->reference }}
                                </a>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </flux:table.cell>

                        {{-- Customer --}}
                        <flux:table.cell x-show="columns.customer">
                            @if ($payment->order?->user)
                                <div class="font-medium text-zinc-800 dark:text-zinc-200">
                                    {{ $payment->order->user->name }}
                                </div>
                                <div class="text-xs text-zinc-400">
                                    {{ $payment->order->user->email }}
                                </div>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </flux:table.cell>

                        {{-- Gateway --}}
                        <flux:table.cell x-show="columns.gateway">
                            <flux:badge size="sm" color="blue" variant="outline">
                                {{ ucfirst($payment->gateway ?? '—') }}
                            </flux:badge>
                        </flux:table.cell>

                        {{-- Payment Method --}}
                        <flux:table.cell x-show="columns.method">
                            @if ($payment->card_brand && $payment->card_last4)
                                <div class="flex items-center gap-1.5">
                                    <flux:icon.credit-card class="size-3.5 text-zinc-400" />
                                    <span class="text-sm text-zinc-700 dark:text-zinc-300">
                                        {{ ucfirst($payment->card_brand) }} ···· {{ $payment->card_last4 }}
                                    </span>
                                </div>
                            @elseif ($payment->gateway === 'mpesa' || ($payment->meta['payment_method'] ?? null) === 'mpesa')
                                <div class="flex items-center gap-1.5">
                                    <flux:icon.device-phone-mobile class="size-3.5 text-zinc-400" />
                                    <span class="text-sm text-zinc-700 dark:text-zinc-300">M-Pesa</span>
                                </div>
                            @else
                                <span class="text-zinc-400 text-sm">—</span>
                            @endif
                        </flux:table.cell>

                        {{-- Amount --}}
                        <flux:table.cell>
                            <div class="font-semibold text-sm text-zinc-800 dark:text-zinc-200">
                                {{ format_currency($payment->amount_cents / 100) }}
                            </div>
                            <div class="text-xs text-zinc-400">{{ $payment->currency ?? get_currency_code() }}</div>
                        </flux:table.cell>

                        {{-- Status --}}
                        <flux:table.cell>
                            <flux:badge size="sm" variant="flat" :color="$payment->status->color()">
                                {{ $payment->status->label() }}
                            </flux:badge>
                            @if ($payment->paid_at)
                                <div class="text-xs text-zinc-400 mt-0.5">
                                    {{ $payment->paid_at->format('M d, g:i A') }}
                                </div>
                            @endif
                        </flux:table.cell>

                        {{-- Date --}}
                        <flux:table.cell x-show="columns.date">
                            <div class="text-sm">{{ $payment->created_at->format('M d, Y') }}</div>
                            <div class="text-xs text-zinc-400">{{ $payment->created_at->format('h:i A') }}</div>
                        </flux:table.cell>

                        {{-- Actions --}}
                        <flux:table.cell align="end" class="pe-4!">
                            <flux:dropdown align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal"
                                    class="cursor-pointer" />
                                <flux:menu>

                                    {{-- View payment --}}
                                    <flux:menu.item icon="eye" icon-variant="outline"
                                        :href="route('admin.payments.show', $payment)" wire:navigate>
                                        View Payment
                                    </flux:menu.item>

                                    {{-- View related order --}}
                                    @if ($payment->order)
                                        <flux:menu.item icon="shopping-bag" icon-variant="outline"
                                            :href="route('admin.orders.show', $payment->order)" wire:navigate>
                                            View Order
                                        </flux:menu.item>
                                    @endif

                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>

                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="9" class="text-center py-16">
                            <div class="flex flex-col items-center justify-center text-zinc-400">
                                <flux:icon.credit-card class="size-12 stroke-1 mb-3" />
                                <flux:text class="font-medium text-zinc-500">No transactions found</flux:text>
                                <flux:text class="text-xs mt-1">Try adjusting your filters or search query</flux:text>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

    </flux:card>
</div>

<style>
    [data-flux-pagination] {
        padding-inline: 1rem;
        padding-bottom: 1rem;
    }
</style>
