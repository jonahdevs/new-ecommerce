<?php

use App\Models\Order;
use App\Enums\OrdersStatus;
use App\Enums\PaymentStatus;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\{Title, Computed};
use Illuminate\Support\Facades\Response;

new #[Title('Orders')] class extends Component {
    use WithPagination;

    // =========================================================================
    //  STATE
    // =========================================================================

    public string $search = '';
    public string $statusFilter = 'all';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';
    public int $perPage = 25;

    // Controls which tab is active — determines document_type filter on all queries.
    // 'sales_orders' → document_type = sales_order
    // 'quotations'   → document_type = quotation
    public string $activeTab = 'sales_orders';

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
    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }
    public function updatingDateTo(): void
    {
        $this->resetPage();
    }
    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    // Switching tabs also resets the status filter — a status from the sales
    // order tab (e.g. 'confirmed') is meaningless on the quotations tab.
    public function updatingActiveTab(): void
    {
        $this->statusFilter = 'all';
        $this->resetPage();
    }

    // =========================================================================
    //  TAB SWITCHER
    //  Called from the tab buttons in the Blade template.
    // =========================================================================

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->statusFilter = 'all';
        $this->resetPage();

        // Clear computed caches so stats and counts recalculate for the new tab
        unset($this->orders, $this->stats, $this->statusCounts, $this->statusOptions);
    }

    // =========================================================================
    //  COMPUTED — ORDERS
    //
    //  Applies document_type scope based on the active tab, then the same
    //  search, status, date, and sort filters as before.
    // =========================================================================

    #[Computed]
    public function orders()
    {
        return Order::query()
            ->with(['user', 'payment'])
            ->withCount('items')

            // Core tab filter — always applied
            ->where('document_type', $this->activeTab === 'quotations' ? 'quotation' : 'sales_order')

            // Search by reference, customer name, or email
            ->when($this->search, fn($q) => $q->where('reference', 'like', "%{$this->search}%")->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%")))

            // Status filter
            ->when($this->statusFilter !== 'all', fn($q) => $q->where('status', $this->statusFilter))

            // Date range
            ->when($this->dateFrom, fn($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('created_at', '<=', $this->dateTo))

            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    // =========================================================================
    //  COMPUTED — STATS
    //
    //  Sales orders tab: total, revenue, today, needs attention (pending/processing)
    //  Quotations tab:   total, pending admin action, awaiting customer, expiring soon
    // =========================================================================

    #[Computed]
    public function stats(): array
    {
        $today = now()->toDateString();
        $documentType = $this->activeTab === 'quotations' ? 'quotation' : 'sales_order';
        $base = Order::where('document_type', $documentType);

        if ($this->activeTab === 'quotations') {
            return [
                // All quotations ever created
                'total' => (clone $base)->count(),

                // Submitted but not yet priced by admin — needs admin action
                'pending_admin' => (clone $base)->where('status', OrdersStatus::PENDING_QUOTE->value)->count(),

                // Priced and sent — waiting for customer to accept or reject
                'awaiting_customer' => (clone $base)->where('status', OrdersStatus::QUOTE_SENT->value)->count(),

                // Sent quotes expiring within the next 48 hours
                'expiring_soon' => (clone $base)
                    ->where('status', OrdersStatus::QUOTE_SENT->value)
                    ->whereNotNull('expires_at')
                    ->where('expires_at', '<=', now()->addHours(48))
                    ->where('expires_at', '>', now())
                    ->count(),
            ];
        }

        return [
            'total' => (clone $base)->count(),
            'revenue' => (clone $base)->sum('total_cents') / 100,
            'today' => (clone $base)->whereDate('created_at', $today)->count(),

            // Orders that need admin attention (not yet shipped)
            'pending' => (clone $base)->whereIn('status', [OrdersStatus::PENDING->value, OrdersStatus::PROCESSING->value])->count(),
        ];
    }

    // =========================================================================
    //  COMPUTED — STATUS OPTIONS
    //
    //  Only shows statuses relevant to the active tab — avoids showing
    //  quotation statuses (QUOTE_SENT etc.) in the sales orders dropdown
    //  and vice versa.
    // =========================================================================

    #[Computed]
    public function statusOptions(): array
    {
        $label = $this->activeTab === 'quotations' ? 'All Quotations' : 'All Orders';

        $options = ['all' => $label];

        foreach (OrdersStatus::cases() as $case) {
            // Filter: only show quotation statuses on quotations tab, and vice versa
            $isQuotationStatus = $case->isQuotationStatus();

            if ($this->activeTab === 'quotations' && !$isQuotationStatus) {
                continue;
            }
            if ($this->activeTab === 'sales_orders' && $isQuotationStatus) {
                continue;
            }

            $options[$case->value] = $case->label();
        }

        return $options;
    }

    // =========================================================================
    //  COMPUTED — STATUS COUNTS
    //  Scoped to the active document_type so counts are accurate per tab.
    // =========================================================================

    #[Computed]
    public function statusCounts(): array
    {
        $documentType = $this->activeTab === 'quotations' ? 'quotation' : 'sales_order';

        $counts = Order::query()->where('document_type', $documentType)->selectRaw('status, count(*) as count')->groupBy('status')->pluck('count', 'status')->toArray();

        return array_merge(['all' => array_sum($counts)], $counts);
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
    //
    //  Sales orders: full status transitions (confirm, processing, ship, deliver, cancel)
    //  Quotations:   cancel only — pricing and sending happens on the show page
    //
    //  The transition guard in Order::transitionTo() and OrdersStatus::canTransitionTo()
    //  prevents invalid transitions even if an action is called with the wrong IDs.
    // =========================================================================

    public function executeBulkAction(string $action, array $ids): void
    {
        if (empty($ids)) {
            return;
        }
        if ($action === 'export') {
            return;
        }

        $targetStatus = OrdersStatus::tryFrom($action);
        if (!$targetStatus) {
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
                } catch (\Exception $e) {
                    $skipped++;
                }
            } else {
                $skipped++;
            }
        }

        unset($this->orders, $this->statusCounts, $this->stats);

        $message = "{$updated} " . ($this->activeTab === 'quotations' ? 'quotation(s)' : 'order(s)') . " updated to {$targetStatus->label()}.";
        if ($skipped > 0) {
            $message .= " {$skipped} skipped (invalid transition).";
        }

        $this->dispatch('notify', variant: $skipped > 0 ? 'warning' : 'success', message: $message);
    }

    // =========================================================================
    //  EXPORT
    //
    //  CSV includes document_type and quotation_type columns so exported
    //  data is self-explanatory regardless of which tab it came from.
    // =========================================================================

    public function exportSelected(array $ids)
    {
        $orders = Order::whereIn('id', $ids)
            ->with(['user', 'payment'])
            ->get();
        $label = $this->activeTab === 'quotations' ? 'quotations' : 'orders';

        return $this->buildCsvDownload($orders, "{$label}-selected-" . now()->format('Y-m-d'));
    }

    public function exportFiltered()
    {
        $documentType = $this->activeTab === 'quotations' ? 'quotation' : 'sales_order';
        $label = $this->activeTab === 'quotations' ? 'quotations' : 'orders';

        $orders = Order::query()
            ->where('document_type', $documentType)
            ->with(['user', 'payment'])
            ->when($this->search, fn($q) => $q->where('reference', 'like', "%{$this->search}%")->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%")))
            ->when($this->statusFilter !== 'all', fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->dateFrom, fn($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->latest()
            ->get();

        return $this->buildCsvDownload($orders, "{$label}-" . now()->format('Y-m-d'));
    }

    private function buildCsvDownload($orders, string $filename)
    {
        $isQuotations = $this->activeTab === 'quotations';

        // Column headers differ between tabs
        $rows = $isQuotations ? [['Reference', 'Type', 'Customer', 'Email', 'Status', 'Subtotal', 'Shipping', 'Total', 'Items', 'Submitted', 'Expires']] : [['Reference', 'Customer', 'Email', 'Status', 'Payment Status', 'Gateway', 'Total', 'Items', 'Date']];

        foreach ($orders as $order) {
            if ($isQuotations) {
                $rows[] = [$order->reference, ucfirst($order->quotation_type ?? 'N/A'), $order->user->name, $order->user->email, $order->status->label(), $order->subtotal, $order->shipping, $order->total, $order->items()->count(), $order->created_at->format('Y-m-d H:i'), $order->expires_at?->format('Y-m-d H:i') ?? 'Not set'];
            } else {
                $rows[] = [$order->reference, $order->user->name, $order->user->email, $order->status->label(), $order->payment?->status?->label() ?? 'N/A', ucfirst($order->payment?->gateway ?? 'N/A'), $order->total, $order->items()->count(), $order->created_at->format('Y-m-d H:i')];
            }
        }

        $handle = fopen('php://temp', 'r+');
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return Response::streamDownload(fn() => print $csv, $filename . '.csv', ['Content-Type' => 'text/csv']);
    }

    // =========================================================================
    //  MISC
    // =========================================================================

    public function clearFilters(): void
    {
        $this->search = '';
        $this->statusFilter = 'all';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
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

    // Column visibility — persisted per tab so each tab can have its own preference
    get storageKey() { return 'orders_columns_' + $wire.activeTab; },
    get columns() {
        return JSON.parse(localStorage.getItem(this.storageKey) ?? 'null') ?? {
            customer: true,
            date: true,
            items: true,
            payment: true, // sales orders
            type: true, // quotations
            expires: true, // quotations
        };
    },
    toggleColumn(col) {
        let cols = this.columns;
        cols[col] = !cols[col];
        localStorage.setItem(this.storageKey, JSON.stringify(cols));
    },
}" @orders-refreshed.window="allIds = [...$event.detail.ids]; selected = [];">

    {{-- Breadcrumb --}}
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate />
        <flux:breadcrumbs.item>Orders</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    {{-- Page header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" class="mb-1">Orders & Quotations</flux:heading>
            <flux:subheading>Manage sales orders, quotation requests, and delivery tracking.</flux:subheading>
        </div>
        <flux:button icon="arrow-down-tray" variant="ghost" size="sm" @click="runExport()">
            <span x-text="selected.length > 0 ? 'Export Selected (' + selected.length + ')' : 'Export'"></span>
        </flux:button>
    </div>

    {{-- ================================================================== --}}
    {{-- TABS                                                                --}}
    {{-- ================================================================== --}}
    <div class="flex items-center gap-1 mb-4 border-b border-zinc-200 dark:border-zinc-700">

        <button wire:click="switchTab('sales_orders')" @class([
            'px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors cursor-pointer',
            'border-brand-primary text-brand-primary' => $activeTab === 'sales_orders',
            'border-transparent text-zinc-500 hover:text-zinc-700' =>
                $activeTab !== 'sales_orders',
        ])>
            <flux:icon.shopping-bag class="size-4 inline-block me-1.5 -mt-0.5" />
            Sales Orders
            <span @class([
                'ms-1.5 text-xs px-1.5 py-0.5 rounded-full font-semibold',
                'bg-brand-primary/10 text-brand-primary' => $activeTab === 'sales_orders',
                'bg-zinc-100 text-zinc-500' => $activeTab !== 'sales_orders',
            ])>
                {{ $this->statusCounts['all'] ?? 0 }}
            </span>
        </button>

        <button wire:click="switchTab('quotations')" @class([
            'px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors cursor-pointer',
            'border-brand-primary text-brand-primary' => $activeTab === 'quotations',
            'border-transparent text-zinc-500 hover:text-zinc-700' =>
                $activeTab !== 'quotations',
        ])>
            <flux:icon.tag class="size-4 inline-block me-1.5 -mt-0.5" />
            Quotations
            {{-- Show a dot when there are quotations awaiting admin action --}}
            @if ($activeTab !== 'quotations')
                @php
                    $pendingQuoteCount = Order::where('document_type', 'quotation')
                        ->where('status', OrdersStatus::PENDING_QUOTE->value)
                        ->count();
                @endphp
                @if ($pendingQuoteCount > 0)
                    <span class="ms-1.5 text-xs px-1.5 py-0.5 rounded-full font-semibold bg-amber-100 text-amber-700">
                        {{ $pendingQuoteCount }} pending
                    </span>
                @endif
            @else
                <span
                    class="ms-1.5 text-xs px-1.5 py-0.5 rounded-full font-semibold bg-brand-primary/10 text-brand-primary">
                    {{ $this->statusCounts['all'] ?? 0 }}
                </span>
            @endif
        </button>

    </div>

    {{-- ================================================================== --}}
    {{-- STATS CARDS                                                         --}}
    {{-- ================================================================== --}}

    {{-- Sales orders stats --}}
    @if ($activeTab === 'sales_orders')
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <flux:card class="p-4 border-l-4 border-l-blue-500 rounded-l-none!">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="text-xs text-zinc-500 uppercase tracking-wide mb-1">Total Orders</flux:text>
                        <flux:heading size="xl" class="text-2xl! font-bold!">
                            {{ number_format($this->stats['total']) }}</flux:heading>
                        <flux:text class="text-xs text-zinc-400 mt-1">All time</flux:text>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center shrink-0">
                        <flux:icon.shopping-bag class="size-5 text-blue-500" />
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-emerald-500 rounded-l-none!">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="text-xs text-zinc-500 uppercase tracking-wide mb-1">Total Revenue</flux:text>
                        <flux:heading size="xl" class="text-2xl! font-bold!">
                            {{ format_currency($this->stats['revenue']) }}</flux:heading>
                        <flux:text class="text-xs text-zinc-400 mt-1">All time</flux:text>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center shrink-0">
                        <flux:icon.banknotes class="size-5 text-emerald-500" />
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-violet-500 rounded-l-none!">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="text-xs text-zinc-500 uppercase tracking-wide mb-1">Orders Today</flux:text>
                        <flux:heading size="xl" class="text-2xl! font-bold!">
                            {{ number_format($this->stats['today']) }}</flux:heading>
                        <flux:text class="text-xs text-zinc-400 mt-1">{{ now()->format('M j, Y') }}</flux:text>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-violet-50 flex items-center justify-center shrink-0">
                        <flux:icon.calendar-days class="size-5 text-violet-500" />
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-amber-500 rounded-l-none!">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="text-xs text-zinc-500 uppercase tracking-wide mb-1">Needs Attention
                        </flux:text>
                        <flux:heading size="xl" class="text-2xl! font-bold!">
                            {{ number_format($this->stats['pending']) }}</flux:heading>
                        <flux:text class="text-xs text-zinc-400 mt-1">Pending / Processing</flux:text>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-amber-50 flex items-center justify-center shrink-0">
                        <flux:icon.clock class="size-5 text-amber-500" />
                    </div>
                </div>
            </flux:card>
        </div>

        {{-- Quotations stats --}}
    @else
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <flux:card class="p-4 border-l-4 border-l-blue-500 rounded-l-none!">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="text-xs text-zinc-500 uppercase tracking-wide mb-1">Total Quotations
                        </flux:text>
                        <flux:heading size="xl" class="text-2xl! font-bold!">
                            {{ number_format($this->stats['total']) }}</flux:heading>
                        <flux:text class="text-xs text-zinc-400 mt-1">All time</flux:text>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center shrink-0">
                        <flux:icon.tag class="size-5 text-blue-500" />
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-amber-500 rounded-l-none!">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="text-xs text-zinc-500 uppercase tracking-wide mb-1">Awaiting Pricing
                        </flux:text>
                        <flux:heading size="xl" class="text-2xl! font-bold!">
                            {{ number_format($this->stats['pending_admin']) }}</flux:heading>
                        <flux:text class="text-xs text-zinc-400 mt-1">Needs admin action</flux:text>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-amber-50 flex items-center justify-center shrink-0">
                        <flux:icon.pencil-square class="size-5 text-amber-500" />
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-cyan-500 rounded-l-none!">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="text-xs text-zinc-500 uppercase tracking-wide mb-1">Awaiting Customer
                        </flux:text>
                        <flux:heading size="xl" class="text-2xl! font-bold!">
                            {{ number_format($this->stats['awaiting_customer']) }}</flux:heading>
                        <flux:text class="text-xs text-zinc-400 mt-1">Quote sent, pending response</flux:text>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-cyan-50 flex items-center justify-center shrink-0">
                        <flux:icon.clock class="size-5 text-cyan-500" />
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-rose-500 rounded-l-none!">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="text-xs text-zinc-500 uppercase tracking-wide mb-1">Expiring Soon</flux:text>
                        <flux:heading size="xl" class="text-2xl! font-bold!">
                            {{ number_format($this->stats['expiring_soon']) }}</flux:heading>
                        <flux:text class="text-xs text-zinc-400 mt-1">Within 48 hours</flux:text>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-rose-50 flex items-center justify-center shrink-0">
                        <flux:icon.exclamation-triangle class="size-5 text-rose-500" />
                    </div>
                </div>
            </flux:card>
        </div>
    @endif

    {{-- ================================================================== --}}
    {{-- MAIN TABLE CARD                                                     --}}
    {{-- ================================================================== --}}
    <flux:card class="p-0">

        {{-- Toolbar --}}
        <div class="flex flex-wrap items-center gap-3 px-5 py-3 border-b border-zinc-200 dark:border-zinc-700">

            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass"
                placeholder="Search reference, name or email..." class="max-w-xs" clearable />

            <div class="flex items-center gap-2 ms-auto flex-wrap">

                <x-my-datepicker wire:model.live="dateFrom" placeholder="From date" icon="o-calendar"
                    class="max-w-40" />
                <x-my-datepicker wire:model.live="dateTo" placeholder="To date" icon="o-calendar" class="max-w-40" />

                {{-- Status filter — options are scoped to the active tab --}}
                <flux:select wire:model.live="statusFilter" class="w-48">
                    @foreach ($this->statusOptions as $value => $label)
                        <flux:select.option value="{{ $value }}">
                            {{ $label }}
                            @if ($value !== 'all')
                                ({{ $this->statusCounts[$value] ?? 0 }})
                            @endif
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="perPage" class="w-24">
                    <flux:select.option value="10">10</flux:select.option>
                    <flux:select.option value="25">25</flux:select.option>
                    <flux:select.option value="50">50</flux:select.option>
                    <flux:select.option value="100">100</flux:select.option>
                </flux:select>

                {{-- Column visibility — options differ per tab --}}
                <flux:dropdown>
                    <flux:button icon="view-columns" variant="ghost" size="sm">Columns</flux:button>
                    <flux:menu>
                        @foreach (['customer' => 'Customer', 'date' => 'Date', 'items' => 'Items'] as $col => $colLabel)
                            <flux:menu.item @click.prevent="toggleColumn('{{ $col }}')">
                                <span class="flex items-center gap-2">
                                    <span x-text="columns.{{ $col }} ? '✓' : ''"
                                        class="w-4 text-green-600 font-bold"></span>
                                    {{ $colLabel }}
                                </span>
                            </flux:menu.item>
                        @endforeach

                        @if ($activeTab === 'sales_orders')
                            <flux:menu.item @click.prevent="toggleColumn('payment')">
                                <span class="flex items-center gap-2">
                                    <span x-text="columns.payment ? '✓' : ''"
                                        class="w-4 text-green-600 font-bold"></span>
                                    Payment
                                </span>
                            </flux:menu.item>
                        @else
                            <flux:menu.item @click.prevent="toggleColumn('type')">
                                <span class="flex items-center gap-2">
                                    <span x-text="columns.type ? '✓' : ''"
                                        class="w-4 text-green-600 font-bold"></span>
                                    Quote Type
                                </span>
                            </flux:menu.item>
                            <flux:menu.item @click.prevent="toggleColumn('expires')">
                                <span class="flex items-center gap-2">
                                    <span x-text="columns.expires ? '✓' : ''"
                                        class="w-4 text-green-600 font-bold"></span>
                                    Expires
                                </span>
                            </flux:menu.item>
                        @endif
                    </flux:menu>
                </flux:dropdown>

                @if ($search || $dateFrom || $dateTo || $statusFilter !== 'all')
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
            class="flex flex-wrap items-center gap-2 px-5 py-2.5 bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">

            <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 me-1">
                <span x-text="selected.length"></span> selected
            </span>

            {{-- Sales order bulk actions --}}
            @if ($activeTab === 'sales_orders')
                <flux:button size="sm" variant="ghost" icon="check-badge" icon-variant="outline"
                    class="cursor-pointer" @click="runBulkAction('{{ OrdersStatus::CONFIRMED->value }}')">Confirm
                </flux:button>

                <flux:button size="sm" variant="ghost" icon="arrow-path" icon-variant="outline"
                    class="cursor-pointer" @click="runBulkAction('{{ OrdersStatus::PROCESSING->value }}')">Mark
                    Processing</flux:button>

                <flux:button size="sm" variant="ghost" icon="truck" icon-variant="outline"
                    class="cursor-pointer" @click="runBulkAction('{{ OrdersStatus::SHIPPED->value }}')">Mark Shipped
                </flux:button>

                <flux:button size="sm" variant="ghost" icon="check-circle" icon-variant="outline"
                    class="cursor-pointer" @click="runBulkAction('{{ OrdersStatus::DELIVERED->value }}')">Mark
                    Delivered</flux:button>
            @endif

            {{-- Export — available on both tabs --}}
            <flux:button size="sm" variant="ghost" icon="arrow-down-tray" icon-variant="outline"
                class="cursor-pointer" @click="runExport()">Export Selected</flux:button>

            {{-- Cancel — available on both tabs, always far right --}}
            <flux:button size="sm" variant="ghost" icon="x-circle" icon-variant="outline"
                class="text-red-500! ms-auto cursor-pointer"
                @click="
                    const label = $wire.activeTab === 'quotations' ? 'quotation(s)' : 'order(s)';
                    if (confirm('Cancel ' + selected.length + ' ' + label + '?')) {
                        runBulkAction('{{ OrdersStatus::CANCELLED->value }}')
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
                        x-effect="$refs.selectAll.querySelector('input').indeterminate = someSelected"
                        ::checked="allSelected" @change="toggleAll()" />
                </flux:table.column>

                {{-- Reference --}}
                <flux:table.column sortable :sorted="$sortBy === 'reference'" :direction="$sortDirection"
                    wire:click="sort('reference')">
                    {{ $activeTab === 'quotations' ? 'Quotation' : 'Order' }}
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
                    {{ $activeTab === 'quotations' ? 'Subtotal' : 'Total' }}
                </flux:table.column>

                {{-- Payment (sales orders) or Quote type (quotations) --}}
                @if ($activeTab === 'sales_orders')
                    <flux:table.column x-show="columns.payment">Payment</flux:table.column>
                @else
                    <flux:table.column x-show="columns.type">Type</flux:table.column>
                    <flux:table.column x-show="columns.expires">Expires</flux:table.column>
                @endif

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
                            @php
                                // Route to the correct show page based on document type
                                $showRoute = $order->isQuotation()
                                    ? route('admin.orders.quotations.show', $order)
                                    : route('admin.orders.show', $order);
                            @endphp
                            <a href="{{ $showRoute }}" wire:navigate
                                class="font-semibold text-zinc-800 dark:text-white hover:text-brand-primary transition-colors">
                                {{ $order->reference }}
                            </a>
                            {{-- Show a "converted" badge on quotations that became a sales order --}}
                            @if ($order->isQuotation() && $order->hasBeenConverted())
                                <div class="mt-0.5">
                                    <flux:badge size="sm" color="teal" variant="flat">Converted</flux:badge>
                                </div>
                            @endif
                            {{-- Show "from quote" badge on sales orders converted from a quotation --}}
                            @if ($order->isSalesOrder() && $order->wasConverted())
                                <div class="mt-0.5">
                                    <flux:badge size="sm" color="blue" variant="flat">From quote</flux:badge>
                                </div>
                            @endif
                        </flux:table.cell>

                        {{-- Customer --}}
                        <flux:table.cell x-show="columns.customer">
                            <div class="font-medium text-zinc-800 dark:text-zinc-200">{{ $order->user->name }}</div>
                            <div class="text-xs text-zinc-400">{{ $order->user->email }}</div>
                        </flux:table.cell>

                        {{-- Date --}}
                        <flux:table.cell x-show="columns.date">
                            <div class="text-sm">{{ $order->created_at->format('M d, Y') }}</div>
                            <div class="text-xs text-zinc-400">{{ $order->created_at->format('h:i A') }}</div>
                        </flux:table.cell>

                        {{-- Items --}}
                        <flux:table.cell x-show="columns.items">
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $order->items_count }} {{ Str::plural('item', $order->items_count) }}
                            </span>
                        </flux:table.cell>

                        {{-- Total / Subtotal --}}
                        <flux:table.cell>
                            <div class="font-semibold text-sm text-zinc-800 dark:text-zinc-200">
                                {{ format_currency($order->total) }}
                            </div>
                            {{-- For quotations, show "+ shipping TBD" if shipping not yet priced --}}
                            @if ($order->isQuotation() && $order->shipping_cents === 0 && !$order->status->isTerminal())
                                <div class="text-xs text-amber-500">+ shipping TBD</div>
                            @endif
                        </flux:table.cell>

                        {{-- Payment column (sales orders only) --}}
                        @if ($activeTab === 'sales_orders')
                            <flux:table.cell x-show="columns.payment">
                                @if ($order->payment)
                                    <flux:badge size="sm" variant="flat"
                                        :color="$order->payment->status->color()">
                                        {{ $order->payment->status?->label() }}
                                    </flux:badge>
                                @else
                                    <flux:badge size="sm" color="zinc">No Payment</flux:badge>
                                @endif
                            </flux:table.cell>

                            {{-- Type + Expires columns (quotations only) --}}
                        @else
                            <flux:table.cell x-show="columns.type">
                                @if ($order->quotation_type === 'delivery')
                                    <flux:badge size="sm" color="indigo" variant="flat" icon="truck">Delivery
                                    </flux:badge>
                                @elseif ($order->quotation_type === 'product')
                                    <flux:badge size="sm" color="purple" variant="flat" icon="tag">Product
                                    </flux:badge>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell x-show="columns.expires">
                                @if ($order->expires_at)
                                    <div @class([
                                        'text-sm font-medium',
                                        'text-rose-600' =>
                                            $order->expires_at->isPast() ||
                                            $order->expires_at->diffInHours(now()) <= 24,
                                        'text-amber-600' =>
                                            !$order->expires_at->isPast() &&
                                            $order->expires_at->diffInHours(now()) <= 48,
                                        'text-zinc-600' => $order->expires_at->diffInHours(now()) > 48,
                                    ])>
                                        {{ $order->expires_at->format('M d, Y') }}
                                    </div>
                                    <div class="text-xs text-zinc-400">
                                        {{ $order->expires_at->isPast() ? 'Expired ' : 'Expires ' }}
                                        {{ $order->expires_at->diffForHumans() }}
                                    </div>
                                @else
                                    <span class="text-xs text-zinc-400">Not set</span>
                                @endif
                            </flux:table.cell>
                        @endif

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
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal"
                                    class="cursor-pointer" />
                                <flux:menu>

                                    {{-- View — routes to correct show page --}}
                                    <flux:menu.item icon="eye" icon-variant="outline" :href="$showRoute"
                                        wire:navigate>
                                        {{ $order->isQuotation() ? 'View Quotation' : 'View Order' }}
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
                                    @if ($order->status->canTransitionTo(OrdersStatus::CANCELLED))
                                        <flux:menu.item icon="x-circle" variant="danger" icon-variant="outline"
                                            wire:click="executeBulkAction('{{ OrdersStatus::CANCELLED->value }}', [{{ $order->id }}])"
                                            wire:confirm="{{ $order->isQuotation() ? 'Cancel quotation' : 'Cancel order' }} {{ $order->reference }}?">
                                            {{ $order->isQuotation() ? 'Cancel Quotation' : 'Cancel Order' }}
                                        </flux:menu.item>
                                    @endif

                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>

                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="10" class="text-center py-16">
                            <div class="flex flex-col items-center justify-center text-zinc-400">
                                <flux:icon.inbox class="size-12 stroke-1 mb-3" />
                                <flux:text class="font-medium text-zinc-500">
                                    No {{ $activeTab === 'quotations' ? 'quotations' : 'orders' }} found
                                </flux:text>
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
