<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\QuoteStatus;
use App\Models\Order;
use App\Models\OrderTag;
use App\Models\Quote;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Orders')] class extends Component
{
    use WithPagination;

    // =========================================================================
    //  STATE
    // =========================================================================

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'payment')]
    public string $paymentFilter = 'all';

    #[Url(as: 'tag')]
    public string $tagFilter = 'all';

    #[Url(as: 'from')]
    public string $dateFrom = '';

    #[Url(as: 'to')]
    public string $dateTo = '';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    #[Url(as: 'per_page')]
    public int $perPage = 10;

    // =========================================================================
    //  PAGINATION RESETS
    //  Reset to page 1 whenever any filter or tab changes.
    // =========================================================================

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingPaymentFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTagFilter(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function setDateRange(string $from, string $to): void
    {
        $this->dateFrom = $from;
        $this->dateTo = $to;
        $this->resetPage();
    }

    // =========================================================================
    //  COMPUTED — ORDERS
    // =========================================================================

    #[Computed]
    public function orders()
    {
        return Order::query()
            ->with(['user', 'payment', 'tags'])
            ->withCount('items')

            // Search by reference, customer name, or email
            ->when($this->search, fn ($q) => $q->where('reference', 'like', "%{$this->search}%")->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%")))

            // Status filter
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))

            // Payment status filter
            ->when($this->paymentFilter !== 'all', fn ($q) => $q->where('payment_status', $this->paymentFilter))

            // Tag filter
            ->when($this->tagFilter !== 'all', fn ($q) => $q->whereHas('tags', fn ($t) => $t->where('order_tags.id', $this->tagFilter)))

            // Date range filter
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))

            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    // =========================================================================
    //  COMPUTED — AVAILABLE TAGS
    // =========================================================================

    #[Computed]
    public function availableTags()
    {
        return OrderTag::withCount('orders')->orderBy('name')->get();
    }

    // =========================================================================
    //  COMPUTED — STATS
    // =========================================================================

    #[Computed]
    public function periodLabel(): string
    {
        if (! $this->dateFrom && ! $this->dateTo) {
            return 'All time';
        }
        $from = $this->dateFrom ? Carbon::parse($this->dateFrom) : null;
        $to = $this->dateTo ? Carbon::parse($this->dateTo) : null;
        if ($from && $to && $from->isSameDay($to)) {
            return $from->format('M j, Y');
        }

        return ($from ? $from->format('M j') : '…').' – '.($to ? $to->format('M j, Y') : '…');
    }

    #[Computed]
    public function stats(): array
    {
        // All stats are current-state — not date-filtered
        // These are actionable metrics for order management

        return [
            'total' => Order::count(),
            'processing' => Order::query()
                ->whereIn('status', [OrderStatus::PENDING->value, OrderStatus::PROCESSING->value])
                ->count(),
            'unpaid' => Order::query()
                ->whereIn('payment_status', [PaymentStatus::PENDING->value, PaymentStatus::FAILED->value])
                ->whereNotIn('status', [OrderStatus::CANCELLED->value])
                ->count(),
            'shipped' => Order::query()->where('status', OrderStatus::SHIPPED->value)->count(),
            'requires_attention' => Order::query()
                ->where(function ($q) {
                    $q->where('payment_status', PaymentStatus::FAILED->value)->orWhere(function ($q2) {
                        $q2->where('status', OrderStatus::PENDING->value)->where('created_at', '<', now()->subHours(24));
                    });
                })
                ->whereNotIn('status', [OrderStatus::CANCELLED->value, OrderStatus::DELIVERED->value])
                ->count(),
        ];
    }

    // =========================================================================
    //  COMPUTED — STATUS OPTIONS
    // =========================================================================

    #[Computed]
    public function statusOptions(): array
    {
        $options = ['all' => 'All Orders'];

        foreach (OrderStatus::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    // =========================================================================
    //  COMPUTED — STATUS COUNTS
    // =========================================================================

    #[Computed]
    public function statusCounts(): array
    {
        $counts = Order::query()->selectRaw('status, count(*) as count')->groupBy('status')->pluck('count', 'status')->toArray();

        return array_merge(['all' => array_sum($counts)], $counts);
    }

    // =========================================================================
    //  COMPUTED — PENDING QUOTES COUNT (for badge)
    // =========================================================================

    #[Computed]
    public function pendingQuotesCount(): int
    {
        return Quote::where('status', QuoteStatus::PENDING->value)->count();
    }

    // =========================================================================
    //  COMPUTED — PAYMENT STATUS OPTIONS
    // =========================================================================

    #[Computed]
    public function paymentStatusOptions(): array
    {
        $options = ['all' => 'All Payments'];

        foreach (PaymentStatus::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    // =========================================================================
    //  SORTING
    // =========================================================================

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

    // =========================================================================
    //  BULK ACTIONS
    // =========================================================================

    public function executeBulkAction(string $action, array $ids): void
    {
        if (empty($ids)) {
            return;
        }
        if ($action === 'export') {
            return;
        }

        $targetStatus = OrderStatus::tryFrom($action);
        if (! $targetStatus) {
            return;
        }

        $orders = Order::whereIn('id', $ids)->get();
        $updated = 0;
        $skipped = 0;

        foreach ($orders as $order) {
            if ($order->status->canTransitionTo($targetStatus)) {
                try {
                    $order->transitionTo($targetStatus, notes: 'Bulk status update by admin.', changedByType: 'user');
                    $updated++;
                } catch (Exception $e) {
                    $skipped++;
                }
            } else {
                $skipped++;
            }
        }

        unset($this->orders, $this->statusCounts, $this->stats);

        $message = "{$updated} order(s) updated to {$targetStatus->label()}.";
        if ($skipped > 0) {
            $message .= " {$skipped} skipped (invalid transition).";
        }

        $this->dispatch('notify', title: 'Bulk Update Complete', variant: $skipped > 0 ? 'warning' : 'success', message: $message);
    }

    // =========================================================================
    //  EXPORT
    // =========================================================================

    public function exportSelected(array $ids)
    {
        $orders = Order::whereIn('id', $ids)
            ->with(['user', 'payment'])
            ->withCount('items')
            ->get();

        return $this->buildCsvDownload($orders, 'orders-selected-'.now()->format('Y-m-d'));
    }

    public function exportFiltered()
    {
        $orders = Order::query()
            ->with(['user', 'payment'])
            ->withCount('items')
            ->when($this->search, fn ($q) => $q->where('reference', 'like', "%{$this->search}%")->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%")))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->paymentFilter !== 'all', fn ($q) => $q->where('payment_status', $this->paymentFilter))
            ->latest()
            ->get();

        return $this->buildCsvDownload($orders, 'orders-'.now()->format('Y-m-d'));
    }

    private function buildCsvDownload($orders, string $filename)
    {
        $rows = [['Reference', 'Customer', 'Email', 'Status', 'Payment Status', 'Gateway', 'Total', 'Items', 'Date']];

        foreach ($orders as $order) {
            $rows[] = [$order->reference, $order->user?->name ?? ($order->guest_info['name'] ?? 'Guest'), $order->user?->email ?? ($order->guest_info['email'] ?? 'N/A'), $order->status->label(), $order->payment?->status?->label() ?? 'N/A', ucfirst($order->payment?->gateway ?? 'N/A'), $order->total, $order->items_count, $order->created_at->format('Y-m-d H:i')];
        }

        $handle = fopen('php://temp', 'r+');
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return Response::streamDownload(fn () => print $csv, $filename.'.csv', ['Content-Type' => 'text/csv']);
    }

    // =========================================================================
    //  MISC
    // =========================================================================

    public function clearFilters(): void
    {
        $this->search = '';
        $this->statusFilter = 'all';
        $this->paymentFilter = 'all';
        $this->tagFilter = 'all';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }

    #[On('echo-private:admin.orders,.order.updated')]
    public function handleOrderUpdate(array $data): void
    {
        $messages = [
            'created' => "New order {$data['reference']} received!",
            'status' => "Order {$data['reference']} is now {$data['status_label']}",
            'payment' => "Payment updated for {$data['reference']}",
        ];

        $this->dispatch('notify', title: $data['update_type'] === 'created' ? 'New Order!' : 'Order Updated', variant: $data['update_type'] === 'created' ? 'success' : 'info', message: $messages[$data['update_type']] ?? "Order {$data['reference']} was updated");

        unset($this->orders, $this->stats, $this->statusCounts, $this->pendingQuotesCount);
    }

    public function rendered(): void
    {
        $this->dispatch('orders-refreshed', ids: $this->orders->pluck('id')->toArray());
    }
};
?>

<div x-data="{
    selected: [],
    allIds: @js($this->orders->pluck('id')->toArray()),

    get allSelected() {
        return this.allIds.length > 0 && this.allIds.every(id => this.selected.includes(id));
    },
    get someSelected() {
        return this.selected.length > 0 && !this.allSelected;
    },
    toggleAll() { this.selected = this.allSelected ? [] : [...this.allIds]; },
    toggle(id) { this.selected.includes(id) ? this.selected = this.selected.filter(i => i !== id) : this.selected.push(id); },
    isSelected(id) { return this.selected.includes(id); },
    clearSelection() { this.selected = []; },

    runBulkAction(action) {
        if (this.selected.length === 0) return;
        $wire.executeBulkAction(action, this.selected);
        this.clearSelection();
    },
    runExport() {
        this.selected.length > 0 ?
            $wire.exportSelected(this.selected) :
            $wire.exportFiltered();
    },

    // Column visibility
    get storageKey() { return 'orders_columns'; },
    get columns() {
        return JSON.parse(localStorage.getItem(this.storageKey) ?? 'null') ?? {
            customer: true,
            date: true,
            items: true,
            payment: true,
        };
    },
    toggleColumn(col) {
        let cols = this.columns;
        cols[col] = !cols[col];
        localStorage.setItem(this.storageKey, JSON.stringify(cols));
    },
}" @orders-refreshed.window="allIds = [...$event.detail.ids]; selected = [];">

    {{-- Breadcrumb --}}
    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item>Orders</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    {{-- Page header --}}
    <div class="flex items-start justify-between mb-6">
        <div>
            <flux:heading size="xl">Orders</flux:heading>
            <flux:subheading>
                {{ $this->periodLabel }} · Manage sales orders and delivery tracking.
            </flux:subheading>
        </div>
        <div class="flex items-center gap-2 flex-wrap justify-end">
            <flux:icon.loading wire:loading wire:target="setDateRange" class="size-3.5 text-zinc-400" />

            <div class="relative" wire:ignore>
                <input type="text" readonly
                    class="orders-date-range w-56 pl-8 pr-3 py-2 text-sm border border-zinc-200 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 cursor-pointer focus:outline-none focus:ring-2 focus:ring-zinc-300 hover:border-zinc-400 transition-colors"
                    placeholder="All time" />
                <flux:icon.calendar-days
                    class="size-4 absolute left-2.5 top-1/2 -translate-y-1/2 text-zinc-400 pointer-events-none" />
            </div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- STATS CARDS                                                         --}}
    {{-- ================================================================== --}}

    <div wire:key="orders-stats-{{ $this->dateFrom }}-{{ $this->dateTo }}"
        class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <flux:card class="p-4 border-l-4 border-l-blue-500 dark:border-l-blue-500 rounded-l-none!">
            <div class="flex items-center justify-between">
                <div>
                    <flux:subheading class="text-xs! uppercase tracking-wide mb-1">Total Orders</flux:subheading>
                    <flux:heading size="xl" class="text-2xl! font-bold!" x-data="countUp({ to: {{ $this->stats['total'] }} })" x-text="display">
                    </flux:heading>
                    <flux:subheading class="text-xs! mt-1">All time</flux:subheading>
                </div>
                <div
                    class="w-10 h-10 rounded-full bg-blue-50 dark:bg-blue-500/15 flex items-center justify-center shrink-0">
                    <flux:icon.shopping-bag class="size-5 text-blue-500" />
                </div>
            </div>
        </flux:card>

        <flux:card
            class="p-4 border-l-4 border-l-amber-500 dark:border-l-amber-500 rounded-l-none! cursor-pointer hover:shadow-md transition-shadow"
            wire:click="$set('statusFilter', '{{ OrderStatus::PROCESSING->value }}')">
            <div class="flex items-center justify-between">
                <div>
                    <flux:subheading class="text-xs! uppercase tracking-wide mb-1">Processing</flux:subheading>
                    <flux:heading size="xl" class="text-2xl! font-bold!" x-data="countUp({ to: {{ $this->stats['processing'] }} })" x-text="display">
                    </flux:heading>
                    <flux:subheading class="text-xs! mt-1">Pending / Processing</flux:subheading>
                </div>
                <div
                    class="w-10 h-10 rounded-full bg-amber-50 dark:bg-amber-500/15 flex items-center justify-center shrink-0">
                    <flux:icon.clock class="size-5 text-amber-500" />
                </div>
            </div>
        </flux:card>

        <flux:card
            class="p-4 border-l-4 border-l-rose-500 dark:border-l-rose-500 rounded-l-none! cursor-pointer hover:shadow-md transition-shadow"
            wire:click="$set('paymentFilter', 'pending')">
            <div class="flex items-center justify-between">
                <div>
                    <flux:subheading class="text-xs! uppercase tracking-wide mb-1">Unpaid</flux:subheading>
                    <flux:heading size="xl" class="text-2xl! font-bold!" x-data="countUp({ to: {{ $this->stats['unpaid'] }} })" x-text="display">
                    </flux:heading>
                    <flux:subheading class="text-xs! mt-1">Awaiting Payment</flux:subheading>
                </div>
                <div
                    class="w-10 h-10 rounded-full bg-rose-50 dark:bg-rose-500/15 flex items-center justify-center shrink-0">
                    <flux:icon.credit-card class="size-5 text-rose-500" />
                </div>
            </div>
        </flux:card>

        <flux:card
            class="p-4 border-l-4 border-l-violet-500 dark:border-l-violet-500 rounded-l-none! cursor-pointer hover:shadow-md transition-shadow"
            wire:click="$set('statusFilter', '{{ OrderStatus::SHIPPED->value }}')">
            <div class="flex items-center justify-between">
                <div>
                    <flux:subheading class="text-xs! uppercase tracking-wide mb-1">Shipped</flux:subheading>
                    <flux:heading size="xl" class="text-2xl! font-bold!" x-data="countUp({ to: {{ $this->stats['shipped'] }} })" x-text="display">
                    </flux:heading>
                    <flux:subheading class="text-xs! mt-1">In Transit</flux:subheading>
                </div>
                <div
                    class="w-10 h-10 rounded-full bg-violet-50 dark:bg-violet-500/15 flex items-center justify-center shrink-0">
                    <flux:icon.truck class="size-5 text-violet-500" />
                </div>
            </div>
        </flux:card>

        <flux:card
            class="p-4 border-l-4 border-l-orange-500 dark:border-l-orange-500 rounded-l-none! cursor-pointer hover:shadow-md transition-shadow"
            wire:click="$set('paymentFilter', 'failed')">
            <div class="flex items-center justify-between">
                <div>
                    <flux:subheading class="text-xs! uppercase tracking-wide mb-1">Needs Attention</flux:subheading>
                    <flux:heading size="xl" class="text-2xl! font-bold!" x-data="countUp({ to: {{ $this->stats['requires_attention'] }} })" x-text="display">
                    </flux:heading>
                    <flux:subheading class="text-xs! mt-1">Failed / Stale Orders</flux:subheading>
                </div>
                <div
                    class="w-10 h-10 rounded-full bg-orange-50 dark:bg-orange-500/15 flex items-center justify-center shrink-0">
                    <flux:icon.exclamation-triangle class="size-5 text-orange-500" />
                </div>
            </div>
        </flux:card>
    </div>

    {{-- ================================================================== --}}
    {{-- MAIN TABLE CARD                                                     --}}
    {{-- ================================================================== --}}
    <flux:card class="p-0 **:data-flux-columns:bg-zinc-50 dark:**:data-flux-columns:bg-zinc-800">

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-2 px-5 py-3 border-b dark:border-zinc-600 border-zinc-200">
            <flux:button icon="arrow-down-tray" variant="ghost" size="sm" @click="runExport()">
                <span x-text="selected.length > 0 ? 'Export Selected (' + selected.length + ')' : 'Export'"></span>
            </flux:button>
            <flux:button :href="route('admin.orders.create')" wire:navigate icon="plus" variant="primary"
                size="sm">
                Create Order
            </flux:button>
        </div>

        {{-- Filters --}}
        <div
            class="flex flex-wrap items-center gap-3 px-5 py-3 border-b dark:border-zinc-600 border-zinc-200 dark:border-zinc-600">

            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass"
                placeholder="Search reference, name or email..." class="max-w-xs" clearable />

            <div class="flex items-center gap-2 ms-auto flex-wrap">

                {{-- Status filter — options are scoped to the active tab --}}
                <flux:select wire:model.live="statusFilter" class="w-44">
                    @foreach ($this->statusOptions as $value => $label)
                        <flux:select.option value="{{ $value }}">
                            {{ $label }}
                            @if ($value !== 'all')
                                ({{ $this->statusCounts[$value] ?? 0 }})
                            @endif
                        </flux:select.option>
                    @endforeach
                </flux:select>

                {{-- Payment status filter --}}
                <flux:select wire:model.live="paymentFilter" class="w-36">
                    @foreach ($this->paymentStatusOptions as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>

                {{-- Tag filter --}}
                @if ($this->availableTags->isNotEmpty())
                    <flux:select wire:model.live="tagFilter" class="w-36">
                        <flux:select.option value="all">All Tags</flux:select.option>
                        @foreach ($this->availableTags as $tag)
                            <flux:select.option value="{{ $tag->id }}">
                                {{ $tag->name }} ({{ $tag->orders_count }})
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                @endif

                <flux:select wire:model.live="perPage" class="w-24">
                    <flux:select.option value="10">10</flux:select.option>
                    <flux:select.option value="25">25</flux:select.option>
                    <flux:select.option value="50">50</flux:select.option>
                    <flux:select.option value="100">100</flux:select.option>
                </flux:select>

                {{-- Column visibility --}}
                <flux:dropdown>
                    <flux:button icon="view-columns" variant="ghost" size="sm">Columns</flux:button>
                    <flux:menu>
                        @foreach (['customer' => 'Customer', 'date' => 'Date', 'items' => 'Items', 'payment' => 'Payment'] as $col => $colLabel)
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

                @if ($search || $statusFilter !== 'all' || $paymentFilter !== 'all' || $tagFilter !== 'all' || $dateFrom || $dateTo)
                    <flux:button wire:click="clearFilters" variant="ghost" size="sm" icon="x-mark">Clear
                    </flux:button>
                @endif

            </div>
        </div>

        {{-- Bulk action bar --}}
        <div x-cloak x-show="selected.length > 0" x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            class="flex flex-wrap items-center gap-2 px-5 py-2.5 bg-zinc-50 dark:bg-zinc-800 border-b dark:border-zinc-600 border-zinc-200 dark:border-zinc-600">

            <flux:subheading class="text-sm! font-semibold! me-1">
                <span x-text="selected.length"></span> selected
            </flux:subheading>

            {{-- Bulk actions --}}
            <flux:button size="sm" variant="ghost" icon="arrow-path" icon-variant="outline"
                class="cursor-pointer" @click="runBulkAction('{{ OrderStatus::PROCESSING->value }}')">Mark
                Processing</flux:button>

            <flux:button size="sm" variant="ghost" icon="truck" icon-variant="outline"
                class="cursor-pointer" @click="runBulkAction('{{ OrderStatus::SHIPPED->value }}')">Mark Shipped
            </flux:button>

            <flux:button size="sm" variant="ghost" icon="check-circle" icon-variant="outline"
                class="cursor-pointer" @click="runBulkAction('{{ OrderStatus::DELIVERED->value }}')">Mark
                Delivered</flux:button>

            {{-- Export --}}
            <flux:button size="sm" variant="ghost" icon="arrow-down-tray" icon-variant="outline"
                class="cursor-pointer" @click="runExport()">Export Selected</flux:button>

            {{-- Cancel --}}
            <flux:button size="sm" variant="ghost" icon="x-circle" icon-variant="outline"
                class="text-red-500! ms-auto cursor-pointer"
                @click="
                    if (confirm('Cancel ' + selected.length + ' order(s)?')) {
                        runBulkAction('{{ OrderStatus::CANCELLED->value }}')
                    }
                ">
                Cancel
            </flux:button>

            <flux:button size="sm" variant="ghost" icon="x-mark" icon-variant="outline"
                class="cursor-pointer" @click="clearSelection()">Clear</flux:button>
        </div>

        {{-- ============================================================== --}}
        {{-- TABLE                                                           --}}
        {{-- ============================================================== --}}
        <flux:table :paginate="$this->orders">
            <flux:table.columns>

                {{-- Select all --}}
                <flux:table.column class="w-10 ps-4!">
                    <flux:checkbox x-ref="selectAll"
                        x-effect="const cb = $refs.selectAll?.querySelector('input'); if (cb) cb.indeterminate = someSelected"
                        ::checked="allSelected" @change="toggleAll()" />
                </flux:table.column>

                {{-- Reference --}}
                <flux:table.column sortable :sorted="$sortBy === 'reference'" :direction="$sortDirection"
                    wire:click="sort('reference')">
                    Order
                </flux:table.column>

                {{-- Customer --}}
                <flux:table.column x-show="columns.customer">Customer</flux:table.column>

                {{-- Date --}}
                <flux:table.column x-show="columns.date" sortable :sorted="$sortBy === 'created_at'"
                    :direction="$sortDirection" wire:click="sort('created_at')">
                    Date
                </flux:table.column>

                {{-- Items --}}
                <flux:table.column x-show="columns.items">Items</flux:table.column>

                {{-- Total --}}
                <flux:table.column sortable :sorted="$sortBy === 'total_cents'" :direction="$sortDirection"
                    wire:click="sort('total_cents')">
                    Total
                </flux:table.column>

                {{-- Payment --}}
                <flux:table.column x-show="columns.payment">Payment</flux:table.column>

                {{-- Status --}}
                <flux:table.column>Status</flux:table.column>

                {{-- Actions --}}
                <flux:table.column align="end" class="pe-4!">Actions</flux:table.column>

            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->orders as $order)
                    <flux:table.row :key="$order->id"
                        x-bind:class="isSelected({{ $order->id }}) ? 'bg-blue-50 dark:bg-blue-900/20' : ''">

                        {{-- Checkbox --}}
                        <flux:table.cell class="ps-4! w-10">
                            <flux:checkbox ::checked="isSelected({{ $order->id }})"
                                @change="toggle({{ $order->id }})" />
                        </flux:table.cell>

                        {{-- Reference --}}
                        <flux:table.cell>
                            <a href="{{ route('admin.orders.show', $order) }}" wire:navigate
                                class="font-semibold text-zinc-800 dark:text-white hover:text-primary transition-colors">
                                {{ $order->reference }}
                            </a>
                            @if ($order->tags->isNotEmpty())
                                <div class="flex flex-wrap gap-1 mt-1">
                                    @foreach ($order->tags->take(3) as $tag)
                                        <span
                                            class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-{{ $tag->color }}-100 text-{{ $tag->color }}-700 dark:bg-{{ $tag->color }}-900/30 dark:text-{{ $tag->color }}-400">
                                            {{ $tag->name }}
                                        </span>
                                    @endforeach
                                    @if ($order->tags->count() > 3)
                                        <span
                                            class="text-[10px] text-zinc-400">+{{ $order->tags->count() - 3 }}</span>
                                    @endif
                                </div>
                            @endif
                        </flux:table.cell>

                        {{-- Customer --}}
                        <flux:table.cell x-show="columns.customer">
                            <flux:heading size="sm" class="font-medium!">
                                {{ $order->user?->name ?? ($order->guest_info['name'] ?? 'Guest') }}</flux:heading>
                            <flux:subheading class="text-xs!">
                                {{ $order->user?->email ?? ($order->guest_info['email'] ?? '—') }}</flux:subheading>
                        </flux:table.cell>

                        {{-- Date --}}
                        <flux:table.cell x-show="columns.date">
                            <flux:text class="text-sm">{{ $order->created_at->format('M d, Y') }}</flux:text>
                            <flux:subheading class="text-xs!">{{ $order->created_at->format('h:i A') }}
                            </flux:subheading>
                        </flux:table.cell>

                        {{-- Items --}}
                        <flux:table.cell x-show="columns.items">
                            <flux:text class="text-sm">
                                {{ $order->items_count }} {{ Str::plural('item', $order->items_count) }}
                            </flux:text>
                        </flux:table.cell>

                        {{-- Total --}}
                        <flux:table.cell>
                            <flux:heading size="sm" class="font-semibold!">
                                {{ format_currency($order->total) }}
                            </flux:heading>
                        </flux:table.cell>

                        {{-- Payment column --}}
                        <flux:table.cell x-show="columns.payment">
                            @if ($order->payment)
                                <flux:badge size="sm" variant="flat" :color="$order->payment->status->color()">
                                    {{ $order->payment->status?->label() }}
                                </flux:badge>
                            @else
                                <flux:badge size="sm" color="zinc">No Payment</flux:badge>
                            @endif
                        </flux:table.cell>

                        {{-- Status --}}
                        <flux:table.cell>
                            <flux:badge size="sm" variant="flat" :color="$order->status->color()"
                                :icon="$order->status->icon()">
                                {{ $order->status->label() }}
                            </flux:badge>
                        </flux:table.cell>

                        {{-- Actions --}}
                        <flux:table.cell align="end" class="pe-4!">
                            <flux:dropdown align="end">
                                    <flux:button variant="ghost" size="xs" icon="ellipsis-horizontal"
                                        class="cursor-pointer" />
                                    <flux:menu>

                                        {{-- View --}}
                                        <flux:menu.item icon="eye" icon-variant="outline"
                                            :href="route('admin.orders.show', $order)" wire:navigate>
                                            View Order
                                        </flux:menu.item>

                                        <flux:menu.separator />

                                        {{-- Change Log --}}
                                        <flux:menu.item icon="clock" icon-variant="outline"
                                            href="{{ route('admin.changelog', ['modelType' => 'order', 'id' => $order->id]) }}"
                                            wire:navigate>
                                            Change Log
                                        </flux:menu.item>

                                        <flux:menu.separator />

                                        {{-- Status transitions --}}
                                        @if (count($order->status->allowedTransitions()) > 0)
                                            <flux:menu.submenu heading="Set Status">
                                                @foreach ($order->status->allowedTransitions() as $transition)
                                                    <flux:menu.item :icon="$transition->icon()" icon-variant="outline"
                                                        wire:click="executeBulkAction('{{ $transition->value }}', [{{ $order->id }}])">
                                                        {{ $transition->label() }}
                                                    </flux:menu.item>
                                                @endforeach
                                            </flux:menu.submenu>
                                            <flux:menu.separator />
                                        @endif

                                        {{-- Cancel --}}
                                        @if ($order->status->canTransitionTo(OrderStatus::CANCELLED))
                                            <flux:menu.item icon="x-circle" variant="danger" icon-variant="outline"
                                                wire:click="executeBulkAction('{{ OrderStatus::CANCELLED->value }}', [{{ $order->id }}])"
                                                wire:confirm="Cancel order {{ $order->reference }}?">
                                                Cancel Order
                                            </flux:menu.item>
                                        @endif

                                    </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>

                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="10" class="text-center py-16">
                            <div class="flex flex-col items-center justify-center">
                                <flux:icon.inbox class="size-12 stroke-1 mb-3 text-zinc-400" />
                                <flux:heading size="sm" class="font-medium!">
                                    No orders found
                                </flux:heading>
                                <flux:subheading class="text-xs! mt-1">Try adjusting your filters or search query
                                </flux:subheading>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

    </flux:card>

    {{-- ================================================================== --}}
    {{-- KEYBOARD SHORTCUTS MODAL                                            --}}
    {{-- ================================================================== --}}
    <flux:modal name="shortcuts-help" class="max-w-sm" @show-shortcuts-modal.window="$el.showModal()">
        <flux:heading size="lg" class="mb-4">Keyboard Shortcuts</flux:heading>
        <div class="space-y-3 text-sm">
            <div class="flex justify-between items-center">
                <span class="text-zinc-600 dark:text-zinc-400">Focus search</span>
                <div class="flex gap-1">
                    <kbd class="px-2 py-1 bg-zinc-100 dark:bg-zinc-800 rounded text-xs font-mono">/</kbd>
                    <span class="text-zinc-400">or</span>
                    <kbd class="px-2 py-1 bg-zinc-100 dark:bg-zinc-800 rounded text-xs font-mono">Ctrl+K</kbd>
                </div>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-zinc-600 dark:text-zinc-400">New order</span>
                <kbd class="px-2 py-1 bg-zinc-100 dark:bg-zinc-800 rounded text-xs font-mono">N</kbd>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-zinc-600 dark:text-zinc-400">Export orders</span>
                <kbd class="px-2 py-1 bg-zinc-100 dark:bg-zinc-800 rounded text-xs font-mono">E</kbd>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-zinc-600 dark:text-zinc-400">Clear filters</span>
                <kbd class="px-2 py-1 bg-zinc-100 dark:bg-zinc-800 rounded text-xs font-mono">C</kbd>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-zinc-600 dark:text-zinc-400">Show this help</span>
                <kbd class="px-2 py-1 bg-zinc-100 dark:bg-zinc-800 rounded text-xs font-mono">?</kbd>
            </div>
        </div>
        <div class="mt-4 pt-4 border-t dark:border-zinc-700">
            <flux:subheading class="text-xs!">Press <kbd
                    class="px-1.5 py-0.5 bg-zinc-100 dark:bg-zinc-800 rounded text-[10px] font-mono">?</kbd> anytime to
                see shortcuts</flux:subheading>
        </div>
    </flux:modal>

</div>

@assets
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
@endassets

@script
    <script>
        function waitForLibraries(cb) {
            if (typeof jQuery !== 'undefined' && typeof moment !== 'undefined' && typeof jQuery.fn.daterangepicker !==
                'undefined') {
                cb();
            } else {
                setTimeout(() => waitForLibraries(cb), 100);
            }
        }

        function initDateRangePicker() {
            const el = $('.orders-date-range').first();
            if (!el.length) return;

            if (el.data('daterangepicker')) {
                el.data('daterangepicker').remove();
            }

            el.daterangepicker({
                autoUpdateInput: false,
                opens: 'left',
                showDropdowns: true,
                alwaysShowCalendars: false,
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month')
                        .endOf('month')
                    ],
                },
                locale: {
                    format: 'MMM DD, YYYY',
                    separator: ' – ',
                    cancelLabel: 'Clear',
                },
            }, function(start, end) {
                $wire.setDateRange(start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD'));
                el.val(start.format('MMM DD, YYYY') + ' – ' + end.format('MMM DD, YYYY'));
            });

            el.on('cancel.daterangepicker', function() {
                $wire.setDateRange('', '');
                el.val('');
            });

            if ($wire.dateFrom && $wire.dateTo) {
                el.val(moment($wire.dateFrom).format('MMM DD, YYYY') + ' – ' + moment($wire.dateTo).format('MMM DD, YYYY'));
            }
        }

        waitForLibraries(() => initDateRangePicker());

        // =====================================================================
        // KEYBOARD SHORTCUTS
        // =====================================================================
        document.addEventListener('keydown', function(e) {
            // Ignore if user is typing in an input/textarea
            if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) {
                return;
            }

            // Ignore if modal is open
            if (document.querySelector('[data-flux-modal][data-open="true"]')) {
                return;
            }

            const key = e.key.toLowerCase();
            const ctrl = e.ctrlKey || e.metaKey;

            // / or Ctrl+K - Focus search
            if (key === '/' || (ctrl && key === 'k')) {
                e.preventDefault();
                const searchInput = document.querySelector(
                    'input[wire\\:model\\.live\\.debounce\\.300ms="search"]');
                if (searchInput) searchInput.focus();
                return;
            }

            // n - New order
            if (key === 'n' && !ctrl) {
                e.preventDefault();
                window.location.href = '{{ route('admin.orders.create') }}';
                return;
            }

            // e - Export
            if (key === 'e' && !ctrl) {
                e.preventDefault();
                const exportBtn = document.querySelector('[\\@click="runExport()"]');
                if (exportBtn) exportBtn.click();
                return;
            }

            // c - Clear filters
            if (key === 'c' && !ctrl) {
                e.preventDefault();
                $wire.clearFilters();
                return;
            }

            // ? - Show shortcuts help
            if (key === '?' || (e.shiftKey && key === '/')) {
                e.preventDefault();
                $wire.dispatch('show-shortcuts-modal');
                return;
            }
        });
    </script>
@endscript
