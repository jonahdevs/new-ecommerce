<?php

use App\Models\Quote;
use App\Enums\QuoteStatus;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\{Title, Computed};
use Illuminate\Support\Facades\Response;

new #[Title('Quotations')] class extends Component {
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
    public int $perPage = 10;

    // =========================================================================
    //  PAGINATION RESETS
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

    // =========================================================================
    //  COMPUTED — QUOTATIONS
    // =========================================================================

    #[Computed]
    public function quotations()
    {
        return Quote::query()
            ->with(['user', 'items' => fn($q) => $q->with('product')->limit(1)])
            ->withCount('items')
            ->when($this->search, fn($q) => $q->where('reference', 'like', "%{$this->search}%")->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%")))
            ->when($this->statusFilter !== 'all', fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->dateFrom, fn($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    // =========================================================================
    //  COMPUTED — PERIOD LABEL
    // =========================================================================

    #[Computed]
    public function periodLabel(): string
    {
        if (! $this->dateFrom && ! $this->dateTo) {
            return 'All time';
        }
        $from = $this->dateFrom ? \Carbon\Carbon::parse($this->dateFrom) : null;
        $to = $this->dateTo ? \Carbon\Carbon::parse($this->dateTo) : null;
        if ($from && $to && $from->isSameDay($to)) {
            return $from->format('M j, Y');
        }

        return ($from ? $from->format('M j') : '…').' – '.($to ? $to->format('M j, Y') : '…');
    }

    // =========================================================================
    //  COMPUTED — STATS
    // =========================================================================

    #[Computed]
    public function stats(): array
    {
        $today = now()->toDateString();

        return [
            'total' => Quote::count(),
            'pending' => Quote::where('status', QuoteStatus::PENDING)->count(),
            'sent' => Quote::where('status', QuoteStatus::SENT)->count(),
            'expiring' => Quote::where('status', QuoteStatus::SENT)
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now()->addDays(3))
                ->where('expires_at', '>', now())
                ->count(),
        ];
    }

    // =========================================================================
    //  COMPUTED — STATUS OPTIONS
    // =========================================================================

    #[Computed]
    public function statusOptions(): array
    {
        $options = ['all' => 'All Quotations'];

        foreach (QuoteStatus::cases() as $case) {
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
        $counts = Quote::query()->selectRaw('status, count(*) as count')->groupBy('status')->pluck('count', 'status')->toArray();

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
    //  EXPORT
    // =========================================================================

    public function exportFiltered()
    {
        $quotes = Quote::query()
            ->with(['user', 'items'])
            ->when($this->search, fn($q) => $q->where('reference', 'like', "%{$this->search}%")->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%")))
            ->when($this->statusFilter !== 'all', fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->dateFrom, fn($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->latest()
            ->get();

        $rows = [['Reference', 'Customer', 'Email', 'Status', 'Total', 'Items', 'Expires At', 'Date']];

        foreach ($quotes as $quote) {
            $rows[] = [$quote->reference, $quote->customerName(), $quote->customerEmail(), $quote->status->label(), $quote->total, $quote->items->count(), $quote->expires_at?->format('Y-m-d') ?? 'N/A', $quote->created_at->format('Y-m-d H:i')];
        }

        $handle = fopen('php://temp', 'r+');
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return Response::streamDownload(fn() => print $csv, 'quotations-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }

    // =========================================================================
    //  MISC
    // =========================================================================

    public function setDateRange(string $from, string $to): void
    {
        $this->dateFrom = $from;
        $this->dateTo = $to;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->statusFilter = 'all';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }
};
?>

<div>
    {{-- Breadcrumb --}}
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate />
        <flux:breadcrumbs.item>Quotations</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    {{-- Page header --}}
    <div class="flex items-start justify-between mb-6">
        <div>
            <flux:heading size="xl">Quotations</flux:heading>
            <flux:subheading>
                {{ $this->periodLabel }} · Manage customer quote requests and pricing.
            </flux:subheading>
        </div>
        <div class="flex items-center gap-2">
            <flux:icon.loading wire:loading wire:target="setDateRange" class="size-3.5 text-zinc-400" />

            <div class="relative" wire:ignore>
                <input type="text" readonly
                    class="quotations-date-range w-56 pl-8 pr-3 py-2 text-sm border border-zinc-200 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 cursor-pointer focus:outline-none focus:ring-2 focus:ring-zinc-300 hover:border-zinc-400 transition-colors"
                    placeholder="All time" />
                <flux:icon.calendar-days class="size-4 absolute left-2.5 top-1/2 -translate-y-1/2 text-zinc-400 pointer-events-none" />
            </div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- STATS CARDS                                                         --}}
    {{-- ================================================================== --}}

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <flux:card class="p-4 border-l-4 border-l-blue-500 dark:border-l-blue-500 rounded-l-none!">
            <div class="flex items-center justify-between">
                <div>
                    <flux:subheading class="text-xs! uppercase tracking-wide mb-1">Total Quotes</flux:subheading>
                    <flux:heading size="xl" class="text-2xl! font-bold!"
                        x-data="countUp({ to: {{ $this->stats['total'] }} })" x-text="display">
                    </flux:heading>
                    <flux:subheading class="text-xs! mt-1">All time</flux:subheading>
                </div>
                <div
                    class="w-10 h-10 rounded-full bg-blue-50 dark:bg-blue-500/15 flex items-center justify-center shrink-0">
                    <flux:icon.document-text class="size-5 text-blue-500" />
                </div>
            </div>
        </flux:card>

        <flux:card class="p-4 border-l-4 border-l-amber-500 dark:border-l-amber-500 rounded-l-none!">
            <div class="flex items-center justify-between">
                <div>
                    <flux:subheading class="text-xs! uppercase tracking-wide mb-1">Pending Review</flux:subheading>
                    <flux:heading size="xl" class="text-2xl! font-bold!"
                        x-data="countUp({ to: {{ $this->stats['pending'] }} })" x-text="display">
                    </flux:heading>
                    <flux:subheading class="text-xs! mt-1">Awaiting pricing</flux:subheading>
                </div>
                <div
                    class="w-10 h-10 rounded-full bg-amber-50 dark:bg-amber-500/15 flex items-center justify-center shrink-0">
                    <flux:icon.clock class="size-5 text-amber-500" />
                </div>
            </div>
        </flux:card>

        <flux:card class="p-4 border-l-4 border-l-indigo-500 dark:border-l-indigo-500 rounded-l-none!">
            <div class="flex items-center justify-between">
                <div>
                    <flux:subheading class="text-xs! uppercase tracking-wide mb-1">Sent to Customer</flux:subheading>
                    <flux:heading size="xl" class="text-2xl! font-bold!"
                        x-data="countUp({ to: {{ $this->stats['sent'] }} })" x-text="display">
                    </flux:heading>
                    <flux:subheading class="text-xs! mt-1">Awaiting response</flux:subheading>
                </div>
                <div
                    class="w-10 h-10 rounded-full bg-indigo-50 dark:bg-indigo-500/15 flex items-center justify-center shrink-0">
                    <flux:icon.paper-airplane class="size-5 text-indigo-500" />
                </div>
            </div>
        </flux:card>

        <flux:card class="p-4 border-l-4 border-l-rose-500 dark:border-l-rose-500 rounded-l-none!">
            <div class="flex items-center justify-between">
                <div>
                    <flux:subheading class="text-xs! uppercase tracking-wide mb-1">Expiring Soon</flux:subheading>
                    <flux:heading size="xl" class="text-2xl! font-bold!"
                        x-data="countUp({ to: {{ $this->stats['expiring'] }} })" x-text="display">
                    </flux:heading>
                    <flux:subheading class="text-xs! mt-1">Within 3 days</flux:subheading>
                </div>
                <div
                    class="w-10 h-10 rounded-full bg-rose-50 dark:bg-rose-500/15 flex items-center justify-center shrink-0">
                    <flux:icon.exclamation-triangle class="size-5 text-rose-500" />
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
            <flux:button icon="arrow-down-tray" variant="ghost" size="sm" wire:click="exportFiltered">
                Export
            </flux:button>
        </div>

        {{-- Filters --}}
        <div
            class="flex flex-wrap items-center gap-3 px-5 py-3 border-b dark:border-zinc-600 border-zinc-200 dark:border-zinc-600">

            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass"
                placeholder="Search reference, name or email..." class="max-w-xs" clearable />

            <div class="flex items-center gap-2 ms-auto flex-wrap">

                {{-- Status filter --}}
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

                @if ($search || $dateFrom || $dateTo || $statusFilter !== 'all')
                    <flux:button wire:click="clearFilters" variant="ghost" size="sm" icon="x-mark">Clear
                    </flux:button>
                @endif

            </div>
        </div>

        {{-- ============================================================== --}}
        {{-- TABLE                                                           --}}
        {{-- ============================================================== --}}
        <flux:table :paginate="$this->quotations">
            <flux:table.columns>

                {{-- Reference --}}
                <flux:table.column sortable :sorted="$sortBy === 'reference'" :direction="$sortDirection"
                    wire:click="sort('reference')" class="ps-5!">
                    Reference
                </flux:table.column>

                {{-- Customer --}}
                <flux:table.column>Customer</flux:table.column>

                {{-- Items --}}
                <flux:table.column>Items</flux:table.column>

                {{-- Total --}}
                <flux:table.column sortable :sorted="$sortBy === 'total_cents'" :direction="$sortDirection"
                    wire:click="sort('total_cents')">
                    Total
                </flux:table.column>

                {{-- Status --}}
                <flux:table.column>Status</flux:table.column>

                {{-- Date --}}
                <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection"
                    wire:click="sort('created_at')">
                    Date
                </flux:table.column>

                {{-- Actions --}}
                <flux:table.column align="end" class="pe-5!">Actions</flux:table.column>

            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->quotations as $quote)
                    @php
                        $firstItem = $quote->items->first();
                        $productName = $firstItem?->product_snapshot['name'] ?? ($firstItem?->product?->name ?? '—');
                    @endphp
                    <flux:table.row :key="$quote->id">

                        {{-- Reference --}}
                        <flux:table.cell class="ps-5!">
                            <a href="{{ route('admin.quotations.show', $quote) }}" wire:navigate
                                class="font-semibold text-zinc-800 dark:text-white hover:text-brand-primary transition-colors">
                                {{ $quote->reference }}
                            </a>
                            @if ($quote->expires_at && $quote->isSent())
                                <div class="mt-0.5">
                                    @if ($quote->expires_at->isPast())
                                        <flux:badge size="sm" color="red" variant="flat">Expired</flux:badge>
                                    @elseif ($quote->expires_at->diffInHours() <= 48)
                                        <flux:badge size="sm" color="amber" variant="flat">
                                            Expires {{ $quote->expires_at->diffForHumans() }}
                                        </flux:badge>
                                    @endif
                                </div>
                            @endif
                        </flux:table.cell>

                        {{-- Customer --}}
                        <flux:table.cell>
                            <flux:heading size="sm" class="font-medium!">{{ $quote->customerName() }}
                            </flux:heading>
                            <flux:subheading class="text-xs!">{{ $quote->customerEmail() }}</flux:subheading>
                        </flux:table.cell>

                        {{-- Items --}}
                        <flux:table.cell>
                            <flux:text class="text-sm truncate max-w-[180px]">{{ $productName }}</flux:text>
                            @if ($quote->items_count > 1)
                                <flux:subheading class="text-xs!">+{{ $quote->items_count - 1 }} more</flux:subheading>
                            @endif
                        </flux:table.cell>

                        {{-- Total --}}
                        <flux:table.cell>
                            <flux:heading size="sm" class="font-semibold!">
                                {{ format_currency($quote->total) }}
                            </flux:heading>
                            @if ($quote->shipping_cents === 0 && !$quote->status->isTerminal())
                                <flux:subheading class="text-xs! text-amber-500">+ shipping TBD</flux:subheading>
                            @endif
                        </flux:table.cell>

                        {{-- Status --}}
                        <flux:table.cell>
                            <flux:badge size="sm" variant="flat" :color="$quote->status->color()"
                                :icon="$quote->status->icon()">
                                {{ $quote->status->label() }}
                            </flux:badge>
                        </flux:table.cell>

                        {{-- Date --}}
                        <flux:table.cell>
                            <flux:text class="text-sm">{{ $quote->created_at->format('M d, Y') }}</flux:text>
                            <flux:subheading class="text-xs!">{{ $quote->created_at->format('h:i A') }}
                            </flux:subheading>
                        </flux:table.cell>

                        {{-- Actions --}}
                        <flux:table.cell align="end" class="pe-5!">
                            <flux:button :href="route('admin.quotations.show', $quote)" wire:navigate variant="ghost"
                                size="sm" icon="eye" icon-variant="outline">
                                View
                            </flux:button>
                        </flux:table.cell>

                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="text-center py-16">
                            <div class="flex flex-col items-center justify-center">
                                <flux:icon.inbox class="size-12 stroke-1 mb-3 text-zinc-400" />
                                <flux:heading size="sm" class="font-medium!">
                                    No quotations found
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
        if (typeof jQuery !== 'undefined' && typeof moment !== 'undefined' && typeof jQuery.fn.daterangepicker !== 'undefined') {
            cb();
        } else {
            setTimeout(() => waitForLibraries(cb), 100);
        }
    }

    function initDateRangePicker() {
        const el = $('.quotations-date-range').first();
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
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
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
</script>
@endscript
