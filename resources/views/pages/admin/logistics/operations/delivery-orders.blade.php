<?php

use App\Enums\DeliveryOrderStatus;
use App\Models\DeliveryOrder;
use App\Models\LogisticsProvider;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use App\Settings\RegionalSettings;
use Livewire\Attributes\{Title, Computed, Url};
use Livewire\WithPagination;
use Livewire\Component;
use Flux\Flux;

new #[Title('Delivery Orders')] class extends Component {
    use WithPagination;

    //  Filters

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $filterStatus = '';

    #[Url(history: true)]
    public string $filterMethod = '';

    #[Url(history: true)]
    public string $filterZone = '';

    #[Url(history: true)]
    public string $filterProvider = '';

    #[Url(history: true)]
    public string $filterDateFrom = '';

    #[Url(history: true)]
    public string $filterDateTo = '';

    public int $perPage = 10;

    //  Bulk action bridge (set server-side, confirmed via modal)

    public array $pendingBulkIds = [];
    public string $pendingBulkStatus = '';

    //  Single order detail

    public ?int $viewingId = null;
    public string $statusNote = '';
    public string $newStatus = '';
    public bool $confirmingStatus = false;

    //  Lifecycle

    public function updatingSearch(): void
    {
        $this->resetPage();
    }
    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }
    public function updatingFilterMethod(): void
    {
        $this->resetPage();
    }
    public function updatingFilterZone(): void
    {
        $this->resetPage();
    }
    public function updatingFilterProvider(): void
    {
        $this->resetPage();
    }
    public function updatingFilterDateFrom(): void
    {
        $this->resetPage();
    }
    public function updatingFilterDateTo(): void
    {
        $this->resetPage();
    }
    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    //  Computed

    #[Computed]
    public function regionalSettings(): RegionalSettings
    {
        return app(RegionalSettings::class);
    }

    #[Computed]
    public function orders()
    {
        return DeliveryOrder::with(['shippingMethod', 'shippingZone', 'logisticsProvider', 'pickupStation'])
            ->where('is_return', false)
            ->when(
                $this->search,
                fn($q) => $q->where(
                    fn($q) => $q
                        ->where('id', 'like', "%{$this->search}%")
                        ->orWhere('provider_reference', 'like', "%{$this->search}%")
                        ->orWhere('order_id', 'like', "%{$this->search}%"),
                ),
            )
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterMethod, fn($q) => $q->where('shipping_method_id', $this->filterMethod))
            ->when($this->filterZone, fn($q) => $q->where('shipping_zone_id', $this->filterZone))
            ->when($this->filterProvider, fn($q) => $q->where('logistics_provider_id', $this->filterProvider))
            ->when($this->filterDateFrom, fn($q) => $q->whereDate('created_at', '>=', $this->filterDateFrom))
            ->when($this->filterDateTo, fn($q) => $q->whereDate('created_at', '<=', $this->filterDateTo))
            ->latest()
            ->paginate($this->perPage);
    }

    #[Computed]
    public function stats(): array
    {
        $base = DeliveryOrder::where('is_return', false);

        return [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->whereNotIn('status', array_map(fn($s) => $s->value, array_filter(DeliveryOrderStatus::cases(), fn($s) => $s->isTerminal())))->count(),
            'today' => (clone $base)->where('status', DeliveryOrderStatus::DELIVERED->value)->whereDate('delivered_at', today())->count(),
            'attention' => (clone $base)->whereIn('status', [DeliveryOrderStatus::FAILED->value, DeliveryOrderStatus::RETURNING->value, DeliveryOrderStatus::AT_STATION->value])->count(),
        ];
    }

    #[Computed]
    public function statusCounts(): array
    {
        $counts = DeliveryOrder::where('is_return', false)->selectRaw('status, count(*) as count')->groupBy('status')->pluck('count', 'status')->toArray();

        return array_merge(['all' => array_sum($counts)], $counts);
    }

    #[Computed]
    public function viewingOrder(): ?DeliveryOrder
    {
        if (!$this->viewingId) {
            return null;
        }

        return DeliveryOrder::with(['shippingMethod', 'shippingZone', 'logisticsProvider', 'pickupStation', 'shippingRate.shippingZone', 'vehicleRate'])->find($this->viewingId);
    }

    #[Computed]
    public function statuses(): array
    {
        return DeliveryOrderStatus::cases();
    }

    #[Computed]
    public function methods()
    {
        return ShippingMethod::where('status', 'active')->orderBy('name')->get();
    }

    #[Computed]
    public function zones()
    {
        return ShippingZone::where('status', 'active')->orderBy('name')->get();
    }

    #[Computed]
    public function providers()
    {
        return LogisticsProvider::where('status', 'active')->orderBy('name')->get();
    }

    #[Computed]
    public function allowedTransitions(): array
    {
        if (!$this->viewingOrder) {
            return [];
        }

        $current = $this->viewingOrder->status instanceof DeliveryOrderStatus ? $this->viewingOrder->status : DeliveryOrderStatus::from($this->viewingOrder->status);

        return match ($current) {
            DeliveryOrderStatus::PENDING => [DeliveryOrderStatus::PICKED_UP, DeliveryOrderStatus::CANCELLED],
            DeliveryOrderStatus::PICKED_UP => [DeliveryOrderStatus::IN_TRANSIT],
            DeliveryOrderStatus::IN_TRANSIT => [DeliveryOrderStatus::OUT_FOR_DELIVERY, DeliveryOrderStatus::AT_STATION],
            DeliveryOrderStatus::OUT_FOR_DELIVERY => [DeliveryOrderStatus::DELIVERED, DeliveryOrderStatus::FAILED],
            DeliveryOrderStatus::FAILED => [DeliveryOrderStatus::RETURNING, DeliveryOrderStatus::OUT_FOR_DELIVERY],
            DeliveryOrderStatus::AT_STATION => [DeliveryOrderStatus::COLLECTED, DeliveryOrderStatus::RETURNING],
            DeliveryOrderStatus::RETURNING => [DeliveryOrderStatus::RETURNED],
            default => [],
        };
    }

    //  Single order actions

    public function viewOrder(int $id): void
    {
        $this->viewingId = $id;
        $this->newStatus = '';
        $this->statusNote = '';
        $this->confirmingStatus = false;
        unset($this->viewingOrder, $this->allowedTransitions);
        Flux::modal('order-detail')->show();
    }

    public function prepareStatusUpdate(string $status): void
    {
        $this->newStatus = $status;
        $this->confirmingStatus = true;
    }

    public function cancelStatusUpdate(): void
    {
        $this->newStatus = '';
        $this->statusNote = '';
        $this->confirmingStatus = false;
    }

    public function applyStatusUpdate(): void
    {
        if (!$this->viewingId || !$this->newStatus) {
            return;
        }

        try {
            $order = DeliveryOrder::findOrFail($this->viewingId);
            $updates = ['status' => $this->newStatus];

            if ($this->newStatus === DeliveryOrderStatus::DELIVERED->value) {
                $updates['delivered_at'] = now();
            }
            if ($this->newStatus === DeliveryOrderStatus::COLLECTED->value) {
                $updates['delivered_at'] = $updates['delivered_at'] ?? now();
            }

            $order->update($updates);

            $this->confirmingStatus = false;
            $this->statusNote = '';
            $this->newStatus = '';

            unset($this->viewingOrder, $this->allowedTransitions, $this->orders, $this->statusCounts, $this->stats);
            $this->dispatch('notify', title: 'Status Updated', variant: 'success', message: 'Delivery order status updated.');
        } catch (\Throwable $e) {
            logger()->error('Failed to update delivery order status.', [
                'exception' => $e->getMessage(),
                'order_id' => $this->viewingId,
                'user_id' => auth()->id(),
            ]);
            $this->dispatch('notify', title: 'Update Failed', variant: 'danger', message: 'Could not update status. Please try again.');
        }
    }

    //  Bulk action bridge

    /**
     * Called from Alpine with selected IDs.
     * Stores them server-side, then opens confirmation modal.
     */
    public function prepareBulkAction(string $status, array $ids): void
    {
        if (empty($ids)) {
            $this->dispatch('notify', title: 'No Selection', variant: 'warning', message: 'No orders selected.');
            return;
        }

        $this->pendingBulkIds = $ids;
        $this->pendingBulkStatus = $status;
        Flux::modal('bulk-confirm')->show();
    }

    public function applyBulkStatus(): void
    {
        if (empty($this->pendingBulkIds) || !$this->pendingBulkStatus) {
            return;
        }

        try {
            $count = DeliveryOrder::whereIn('id', $this->pendingBulkIds)
                ->where('is_return', false)
                ->update(['status' => $this->pendingBulkStatus]);

            $status = DeliveryOrderStatus::tryFrom($this->pendingBulkStatus)?->label() ?? $this->pendingBulkStatus;

            $this->pendingBulkIds = [];
            $this->pendingBulkStatus = '';

            unset($this->orders, $this->statusCounts, $this->stats);
            Flux::modal('bulk-confirm')->close();

            $this->dispatch('notify', title: 'Bulk Update Complete', variant: 'success', message: "{$count} orders marked as {$status}.");
            $this->dispatch('delivery-bulk-done'); // Alpine listens → clears selection
        } catch (\Throwable $e) {
            logger()->error('Bulk delivery status update failed.', [
                'exception' => $e->getMessage(),
                'ids' => $this->pendingBulkIds,
                'user_id' => auth()->id(),
            ]);
            $this->dispatch('notify', title: 'Bulk Update Failed', variant: 'danger', message: 'Bulk update failed. Please try again.');
        }
    }

    //  Misc ─

    public function setDateRange(string $from, string $to): void
    {
        $this->filterDateFrom = $from;
        $this->filterDateTo = $to;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->filterStatus = '';
        $this->filterMethod = '';
        $this->filterZone = '';
        $this->filterProvider = '';
        $this->filterDateFrom = '';
        $this->filterDateTo = '';
        $this->resetPage();
    }

    public function rendered(): void
    {
        $this->dispatch('delivery-orders-refreshed', ids: $this->orders->pluck('id')->toArray());
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
    toggleAll() {
        this.selected = this.allSelected ? [] : [...this.allIds];
    },
    toggle(id) {
        this.selected.includes(id) ?
            this.selected = this.selected.filter(i => i !== id) :
            this.selected.push(id);
    },
    isSelected(id) {
        return this.selected.includes(id);
    },
    clearSelection() {
        this.selected = [];
    },
    runBulkAction(status) {
        if (this.selected.length === 0) return;
        $wire.prepareBulkAction(status, this.selected);
    },

    columns: JSON.parse(localStorage.getItem('delivery_orders_columns') ?? 'null') ?? {
        method: true,
        zone: true,
        provider: true,
        estimated: true,
    },
    toggleColumn(col) {
        this.columns[col] = !this.columns[col];
        localStorage.setItem('delivery_orders_columns', JSON.stringify(this.columns));
    },
}"
    @delivery-orders-refreshed.window="
        allIds = [...$event.detail.ids];
        selected = [];
    "
    @delivery-bulk-done.window="clearSelection()">

    {{-- Breadcrumb --}}
    <x-admin.logistics.layout heading="Delivery Orders" subheading="Track and manage all forward deliveries.">

        <x-slot:actions>
            <flux:icon.loading wire:loading wire:target="setDateRange" class="size-3.5 text-zinc-400" />
            <div class="relative" wire:ignore>
                <input type="text" readonly
                    class="delivery-orders-date-range w-56 pl-8 pr-3 py-2 text-sm border border-zinc-200 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 cursor-pointer focus:outline-none focus:ring-2 focus:ring-zinc-300 hover:border-zinc-400 transition-colors"
                    placeholder="All dates" />
                <flux:icon.calendar-days class="size-4 absolute left-2.5 top-1/2 -translate-y-1/2 text-zinc-400 pointer-events-none" />
            </div>
        </x-slot:actions>

        {{-- Stats cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

            <flux:card class="p-4 border-l-4 border-l-blue-500 dark:border-l-blue-500 rounded-l-none!">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:subheading class="text-xs! uppercase tracking-wide mb-1">Total Deliveries
                        </flux:subheading>
                        <flux:heading size="xl" class="text-2xl! font-bold!">
                            {{ number_format($this->stats['total']) }}
                        </flux:heading>
                        <flux:subheading class="text-xs! mt-1">All time</flux:subheading>
                    </div>
                    <div
                        class="w-10 h-10 rounded-full bg-blue-50 dark:bg-blue-900 flex items-center justify-center shrink-0">
                        <flux:icon.truck class="size-5 text-blue-500" />
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-purple-500 dark:border-l-purple-500 rounded-l-none!">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:subheading class="text-xs! uppercase tracking-wide mb-1">Active</flux:subheading>
                        <flux:heading size="xl" class="text-2xl! font-bold! text-purple-600">
                            {{ number_format($this->stats['active']) }}
                        </flux:heading>
                        <flux:subheading class="text-xs! mt-1">In progress</flux:subheading>
                    </div>
                    <div
                        class="w-10 h-10 rounded-full bg-purple-50 dark:bg-purple-900 flex items-center justify-center shrink-0">
                        <flux:icon.arrow-path class="size-5 text-purple-500" />
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-emerald-500 dark:border-l-emerald-500 rounded-l-none!">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:subheading class="text-xs! uppercase tracking-wide mb-1">Delivered Today
                        </flux:subheading>
                        <flux:heading size="xl" class="text-2xl! font-bold! text-emerald-600">
                            {{ number_format($this->stats['today']) }}
                        </flux:heading>
                        <flux:subheading class="text-xs! mt-1">{{ now()->format('M j, Y') }}</flux:subheading>
                    </div>
                    <div
                        class="w-10 h-10 rounded-full bg-emerald-50 dark:bg-emerald-900 flex items-center justify-center shrink-0">
                        <flux:icon.check-circle class="size-5 text-emerald-500" />
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-red-500 dark:border-l-red-500 rounded-l-none!">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:subheading class="text-xs! uppercase tracking-wide mb-1">Needs Attention
                        </flux:subheading>
                        <flux:heading size="xl" class="text-2xl! font-bold! text-red-600">
                            {{ number_format($this->stats['attention']) }}
                        </flux:heading>
                        <flux:subheading class="text-xs! mt-1">Failed / Returning / At Station</flux:subheading>
                    </div>
                    <div
                        class="w-10 h-10 rounded-full bg-red-50 dark:bg-red-900 flex items-center justify-center shrink-0">
                        <flux:icon.exclamation-triangle class="size-5 text-red-500" />
                    </div>
                </div>
            </flux:card>

        </div>

        {{-- Main card --}}
        <flux:card class="p-0">

            {{-- Toolbar --}}
            <div class="flex flex-wrap items-center gap-3 px-5 py-3 border-b border-zinc-200 dark:border-zinc-600">

                <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass"
                    placeholder="Search by order ID, reference..." class="max-w-xs" clearable />

                <div class="flex items-center gap-2 ms-auto">

                    {{-- Per page --}}
                    <flux:select wire:model.live="perPage" class="w-24">
                        <flux:select.option value="10">10</flux:select.option>
                        <flux:select.option value="25">25</flux:select.option>
                        <flux:select.option value="50">50</flux:select.option>
                        <flux:select.option value="100">100</flux:select.option>
                    </flux:select>

                    {{-- Advanced filters dropdown --}}
                    <flux:dropdown>
                        <flux:button icon="funnel" icon-variant="outline" variant="ghost" size="sm">
                            Filters
                            @if ($filterStatus || $filterMethod || $filterZone || $filterProvider)
                                <flux:badge size="sm" class="ms-2" color="blue">
                                    {{ collect([$filterStatus, $filterMethod, $filterZone, $filterProvider])->filter()->count() }}
                                </flux:badge>
                            @endif
                        </flux:button>

                        <flux:menu class="min-w-80">
                            <div>
                                <div class="flex items-center justify-between px-4 py-2 border-b dark:border-zinc-600">
                                    <flux:heading size="sm">Filter Options</flux:heading>
                                    @if ($filterStatus || $filterMethod || $filterZone || $filterProvider)
                                        <flux:button variant="ghost" size="xs" wire:click="clearFilters"
                                            class="cursor-pointer">
                                            Reset
                                        </flux:button>
                                    @endif
                                </div>

                                <div class="space-y-3 p-5">
                                    <flux:field>
                                        <flux:label>Status</flux:label>
                                        <flux:select wire:model.live="filterStatus" placeholder="All Statuses">
                                            @foreach ($this->statuses as $s)
                                                <flux:select.option value="{{ $s->value }}">
                                                    {{ $s->label() }} ({{ $this->statusCounts[$s->value] ?? 0 }})
                                                </flux:select.option>
                                            @endforeach
                                        </flux:select>
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>Shipping Method</flux:label>
                                        <flux:select wire:model.live="filterMethod" placeholder="All Methods">
                                            @foreach ($this->methods as $method)
                                                <flux:select.option value="{{ $method->id }}">{{ $method->name }}
                                                </flux:select.option>
                                            @endforeach
                                        </flux:select>
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>Shipping Zone</flux:label>
                                        <flux:select wire:model.live="filterZone" placeholder="All Zones">
                                            @foreach ($this->zones as $zone)
                                                <flux:select.option value="{{ $zone->id }}">{{ $zone->name }}
                                                </flux:select.option>
                                            @endforeach
                                        </flux:select>
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>Provider</flux:label>
                                        <flux:select wire:model.live="filterProvider" placeholder="All Providers">
                                            @foreach ($this->providers as $provider)
                                                <flux:select.option value="{{ $provider->id }}">{{ $provider->name }}
                                                </flux:select.option>
                                            @endforeach
                                        </flux:select>
                                    </flux:field>
                                </div>
                            </div>
                        </flux:menu>
                    </flux:dropdown>

                    {{-- Column visibility --}}
                    <flux:dropdown>
                        <flux:button icon="view-columns" icon-variant="outline" variant="ghost" size="sm">Columns
                        </flux:button>
                        <flux:menu>
                            @foreach (['method' => 'Method', 'zone' => 'Zone', 'provider' => 'Provider', 'estimated' => 'Estimated'] as $col => $colLabel)
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

                    {{-- Clear --}}
                    @if ($search || $filterStatus || $filterMethod || $filterZone || $filterProvider || $filterDateFrom || $filterDateTo)
                        <flux:button wire:click="clearFilters" variant="ghost" size="sm" icon="x-mark">Clear</flux:button>
                    @endif

                </div>
            </div>

            {{-- Active filter tags --}}
            @if ($filterStatus || $filterMethod || $filterZone || $filterProvider || $filterDateFrom || $filterDateTo)
                <div class="flex flex-wrap gap-2 px-5 py-2 border-b border-zinc-200 dark:border-zinc-600">
                    <span class="text-xs font-semibold text-zinc-400 uppercase tracking-wider self-center me-1">Active:</span>

                    @if ($filterStatus)
                        <flux:badge size="sm" variant="flat" closable wire:click="$set('filterStatus', '')">
                            Status: {{ DeliveryOrderStatus::tryFrom($filterStatus)?->label() }}
                        </flux:badge>
                    @endif

                    @if ($filterMethod)
                        <flux:badge size="sm" variant="flat" closable wire:click="$set('filterMethod', '')">
                            Method: {{ $this->methods->find($filterMethod)?->name }}
                        </flux:badge>
                    @endif

                    @if ($filterZone)
                        <flux:badge size="sm" variant="flat" closable wire:click="$set('filterZone', '')">
                            Zone: {{ $this->zones->find($filterZone)?->name }}
                        </flux:badge>
                    @endif

                    @if ($filterProvider)
                        <flux:badge size="sm" variant="flat" closable wire:click="$set('filterProvider', '')">
                            Provider: {{ $this->providers->find($filterProvider)?->name }}
                        </flux:badge>
                    @endif

                    @if ($filterDateFrom || $filterDateTo)
                        <flux:badge size="sm" variant="flat" closable wire:click="setDateRange('', '')">
                            {{ $filterDateFrom ? \Carbon\Carbon::parse($filterDateFrom)->format('M d, Y') : '…' }}
                            –
                            {{ $filterDateTo ? \Carbon\Carbon::parse($filterDateTo)->format('M d, Y') : '…' }}
                        </flux:badge>
                    @endif
                </div>
            @endif

            {{-- Bulk action bar --}}
            <div x-cloak x-show="selected.length > 0" x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2"
                class="flex flex-wrap items-center gap-2 px-5 py-2.5 bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-600">
                <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 me-1">
                    <span x-text="selected.length"></span> selected
                </span>

                <flux:button size="sm" variant="ghost" icon="hand-raised" icon-variant="outline"
                    @click="runBulkAction('{{ DeliveryOrderStatus::PICKED_UP->value }}')">
                    Mark Picked Up
                </flux:button>

                <flux:button size="sm" variant="ghost" icon="arrow-path" icon-variant="outline"
                    @click="runBulkAction('{{ DeliveryOrderStatus::IN_TRANSIT->value }}')">
                    In Transit
                </flux:button>

                <flux:button size="sm" variant="ghost" icon="truck" icon-variant="outline"
                    @click="runBulkAction('{{ DeliveryOrderStatus::OUT_FOR_DELIVERY->value }}')">
                    Out for Delivery
                </flux:button>

                <flux:button size="sm" variant="ghost" icon="check-circle" icon-variant="outline"
                    @click="runBulkAction('{{ DeliveryOrderStatus::DELIVERED->value }}')">
                    Mark Delivered
                </flux:button>

                {{-- Cancel — danger, far right --}}
                <flux:button size="sm" variant="ghost" icon="x-circle" icon-variant="outline"
                    class="text-red-500! ms-auto"
                    @click="runBulkAction('{{ DeliveryOrderStatus::CANCELLED->value }}')">
                    Cancel
                </flux:button>

                <flux:button size="sm" variant="ghost" icon="x-mark" icon-variant="outline"
                    @click="clearSelection()">
                    Clear
                </flux:button>
            </div>

            {{-- Table --}}
            <flux:table :paginate="$this->orders">
                <flux:table.columns>

                    <flux:table.column class="w-10 ps-4!">
                        <flux:checkbox x-ref="selectAll"
                            x-effect="$refs.selectAll.querySelector('input').indeterminate = someSelected"
                            ::checked="allSelected" @change="toggleAll()" />
                    </flux:table.column>

                    <flux:table.column>Order</flux:table.column>

                    <flux:table.column x-show="columns.method">Method</flux:table.column>

                    <flux:table.column x-show="columns.zone">Zone</flux:table.column>

                    <flux:table.column x-show="columns.provider">Provider</flux:table.column>

                    <flux:table.column>Cost</flux:table.column>

                    <flux:table.column x-show="columns.estimated">Estimated</flux:table.column>

                    <flux:table.column>Status</flux:table.column>

                    <flux:table.column align="end" class="pe-4!">Actions</flux:table.column>

                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->orders as $order)
                        <flux:table.row :key="$order->id"
                            x-bind:class="isSelected({{ $order->id }}) ? 'bg-blue-50 dark:bg-blue-900/20' : ''">

                            <flux:table.cell class="ps-4! w-10">
                                <flux:checkbox ::checked="isSelected({{ $order->id }})"
                                    @change="toggle({{ $order->id }})" />
                            </flux:table.cell>

                            {{-- Order --}}
                            <flux:table.cell>
                                <div class="font-semibold text-sm text-zinc-800 dark:text-zinc-200">
                                    #{{ $order->order_id }}
                                </div>
                                @if ($order->provider_reference)
                                    <code class="text-xs text-zinc-400">{{ $order->provider_reference }}</code>
                                @endif
                                <div class="text-xs text-zinc-400 mt-0.5">
                                    {{ $order->created_at->format('M d, Y') }}
                                </div>
                            </flux:table.cell>

                            {{-- Method --}}
                            <flux:table.cell x-show="columns.method">
                                <span class="text-sm">{{ $order->shippingMethod->name }}</span>
                            </flux:table.cell>

                            {{-- Zone --}}
                            <flux:table.cell x-show="columns.zone">
                                <span class="text-sm">{{ $order->shippingZone->name }}</span>
                            </flux:table.cell>

                            {{-- Provider --}}
                            <flux:table.cell x-show="columns.provider">
                                @if ($order->logisticsProvider)
                                    <flux:badge size="sm" variant="outline" color="zinc">
                                        {{ $order->logisticsProvider->name }}
                                    </flux:badge>
                                @else
                                    <span class="text-zinc-400">—</span>
                                @endif
                            </flux:table.cell>

                            {{-- Cost --}}
                            <flux:table.cell>
                                <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                                    {{ format_currency($order->shipping_cost) }}
                                </span>
                            </flux:table.cell>

                            {{-- Estimated --}}
                            <flux:table.cell x-show="columns.estimated">
                                @if ($order->estimated_delivery_at)
                                    <div class="text-sm">
                                        {{ $order->estimated_delivery_at->format('M d, Y') }}
                                    </div>
                                    @if ($order->estimated_delivery_at->isPast() && $order->status->isActive())
                                        <flux:badge size="sm" color="red" class="mt-0.5">Overdue</flux:badge>
                                    @endif
                                @else
                                    <span class="text-zinc-400">—</span>
                                @endif
                            </flux:table.cell>

                            {{-- Status --}}
                            <flux:table.cell>
                                <flux:badge size="sm" variant="flat" :color="$order->status->color()">
                                    {{ $order->status->label() }}
                                </flux:badge>
                                @if ($order->isOverdueCollection())
                                    <div class="text-xs text-red-500 mt-0.5 font-medium">Collection overdue</div>
                                @endif
                            </flux:table.cell>

                            {{-- Actions --}}
                            <flux:table.cell align="end" class="pe-4!">
                                <flux:dropdown align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal"
                                        class="cursor-pointer" />
                                    <flux:menu>
                                        <flux:menu.item icon="eye" icon-variant="outline"
                                            wire:click="viewOrder({{ $order->id }})">
                                            View Details
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </flux:table.cell>

                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="9" class="py-16 text-center">
                                <div class="flex flex-col items-center gap-3 text-zinc-400">
                                    <flux:icon.clipboard-document-list class="size-12 stroke-1" />
                                    <div>
                                        <flux:text class="font-medium text-zinc-500">No delivery orders found
                                        </flux:text>
                                        <flux:text class="text-xs mt-0.5">
                                            @if ($search || $filterStatus || $filterMethod || $filterZone || $filterProvider || $filterDateFrom || $filterDateTo)
                                                No orders match your current filters.
                                            @else
                                                Delivery orders will appear here once customers place orders.
                                            @endif
                                        </flux:text>
                                    </div>
                                    @if ($search || $filterStatus || $filterMethod || $filterZone || $filterProvider || $filterDateFrom || $filterDateTo)
                                        <flux:button variant="ghost" size="sm" wire:click="clearFilters">
                                            Clear filters
                                        </flux:button>
                                    @endif
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

        </flux:card>

        {{-- Order Detail Flyout --}}
        <flux:modal name="order-detail" class="md:w-xl space-y-0" variant="flyout">
            @if ($this->viewingOrder)
                @php
                    $order = $this->viewingOrder;
                    $status =
                        $order->status instanceof DeliveryOrderStatus
                            ? $order->status
                            : DeliveryOrderStatus::from($order->status);
                    $breakdown = $order->cost_breakdown ?? [];
                @endphp

                <div class="flex items-start justify-between pb-4 border-b border-zinc-100 dark:border-zinc-800">
                    <div>
                        <flux:heading size="lg">Delivery #{{ $order->order_id }}</flux:heading>
                        @if ($order->provider_reference)
                            <code class="text-xs text-zinc-400">Ref: {{ $order->provider_reference }}</code>
                        @endif
                    </div>
                    <flux:badge :color="$status->color()" variant="flat">{{ $status->label() }}</flux:badge>
                </div>

                <div class="py-4 space-y-6">

                    {{-- Summary grid --}}
                    <div class="grid grid-cols-3 gap-4 text-sm">
                        <div>
                            <p class="text-zinc-400 text-xs mb-0.5">Method</p>
                            <p class="font-medium">{{ $order->shippingMethod->name }}</p>
                        </div>
                        <div>
                            <p class="text-zinc-400 text-xs mb-0.5">Zone</p>
                            <p class="font-medium">{{ $order->shippingZone->name }}</p>
                        </div>
                        <div>
                            <p class="text-zinc-400 text-xs mb-0.5">Provider</p>
                            <p class="font-medium">{{ $order->logisticsProvider->name }}</p>
                        </div>
                        <div>
                            <p class="text-zinc-400 text-xs mb-0.5">Shipping Cost</p>
                            <p class="font-semibold text-base">{{ format_currency($order->shipping_cost) }}</p>
                        </div>
                        <div>
                            <p class="text-zinc-400 text-xs mb-0.5">Weight</p>
                            <p class="font-medium">
                                {{ $order->package_weight_kg ? $order->package_weight_kg . ' ' . $this->regionalSettings->weight_unit : '—' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-zinc-400 text-xs mb-0.5">Created</p>
                            <p class="font-medium">{{ $order->created_at->format('M d, Y H:i') }}</p>
                        </div>
                    </div>

                    {{-- Delivery dates --}}
                    @if ($order->estimated_delivery_at || $order->delivered_at)
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            @if ($order->estimated_delivery_at)
                                <div>
                                    <p class="text-zinc-400 text-xs mb-0.5">Estimated Delivery</p>
                                    <p class="font-medium">{{ $order->estimated_delivery_at->format('M d, Y H:i') }}
                                    </p>
                                </div>
                            @endif
                            @if ($order->delivered_at)
                                <div>
                                    <p class="text-zinc-400 text-xs mb-0.5">Delivered At</p>
                                    <p class="font-medium text-emerald-600">
                                        {{ $order->delivered_at->format('M d, Y H:i') }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Pickup station info --}}
                    @if ($order->pickupStation)
                        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-3 text-sm space-y-1">
                            <p class="font-medium text-orange-700 dark:text-orange-300">Pickup Station</p>
                            <p>{{ $order->pickupStation->name }}</p>
                            @if ($order->collection_deadline_at)
                                <p class="text-orange-600 dark:text-orange-400 text-xs">
                                    Collection deadline: {{ $order->collection_deadline_at->format('M d, Y') }}
                                    @if ($order->collection_deadline_at->isPast())
                                        <span class="font-semibold">(Overdue)</span>
                                    @elseif ($order->collection_deadline_at->diffInDays(now()) <= 2)
                                        <span
                                            class="font-semibold">({{ $order->collection_deadline_at->diffForHumans() }})</span>
                                    @endif
                                </p>
                            @endif
                        </div>
                    @endif

                    {{-- Cost breakdown --}}
                    @if (!empty($breakdown))
                        <div>
                            <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Cost Breakdown</p>
                            <div
                                class="bg-zinc-50 dark:bg-zinc-800/60 rounded-lg divide-y divide-zinc-100 dark:divide-zinc-700 text-sm">
                                @foreach ($breakdown as $key => $value)
                                    @if (!in_array($key, ['model', 'total']))
                                        <div class="flex justify-between px-3 py-2">
                                            <span class="text-zinc-500 capitalize">
                                                {{ str_replace('_', ' ', $key) }}
                                            </span>
                                            <span class="font-medium">
                                                {{ is_numeric($value) ? format_currency($value) : $value }}
                                            </span>
                                        </div>
                                    @endif
                                @endforeach
                                <div class="flex justify-between px-3 py-2 font-semibold">
                                    <span>Total</span>
                                    <span>{{ format_currency($breakdown['total'] ?? $order->shipping_cost) }}</span>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Status update --}}
                    @if (!$status->isTerminal() && count($this->allowedTransitions))
                        <div class="border-t border-zinc-100 dark:border-zinc-800 pt-4">
                            <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-3">Update Status</p>

                            @if (!$confirmingStatus)
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($this->allowedTransitions as $transition)
                                        <flux:button variant="outline" size="sm"
                                            wire:click="prepareStatusUpdate('{{ $transition->value }}')">
                                            → {{ $transition->label() }}
                                        </flux:button>
                                    @endforeach
                                </div>
                            @else
                                <div class="bg-zinc-50 dark:bg-zinc-800/60 rounded-lg p-4 space-y-3">
                                    <p class="text-sm">
                                        Mark as <strong>{{ DeliveryOrderStatus::from($newStatus)->label() }}</strong>?
                                    </p>
                                    <flux:textarea wire:model="statusNote"
                                        placeholder="Optional note (internal only)..." rows="2" />
                                    <div class="flex gap-2">
                                        <flux:button variant="ghost" size="sm" wire:click="cancelStatusUpdate">
                                            Cancel
                                        </flux:button>
                                        <flux:button variant="primary" size="sm" wire:click="applyStatusUpdate">
                                            Confirm Update
                                        </flux:button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @elseif ($status->isTerminal())
                        <div class="border-t border-zinc-100 dark:border-zinc-800 pt-4">
                            <p class="text-sm text-zinc-400">
                                This order is in a terminal state and cannot be updated further.
                            </p>
                        </div>
                    @endif

                </div>
            @endif
        </flux:modal>

        {{-- Bulk confirm modal --}}
        <flux:modal name="bulk-confirm" class="md:w-96 space-y-6">
            <flux:heading size="lg">Confirm Bulk Update</flux:heading>
            <flux:subheading>
                <span x-text="selected.length"></span> order(s) will be marked as
                <strong>{{ $pendingBulkStatus ? DeliveryOrderStatus::tryFrom($pendingBulkStatus)?->label() : '' }}</strong>.
                This cannot be undone.
            </flux:subheading>
            <div class="flex gap-3">
                <flux:modal.close class="flex-1">
                    <flux:button variant="ghost" class="w-full">Cancel</flux:button>
                </flux:modal.close>
                <flux:button wire:click="applyBulkStatus" variant="primary" class="flex-1">
                    Confirm
                </flux:button>
            </div>
        </flux:modal>

    </x-admin.logistics.layout>

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
        const el = $('.delivery-orders-date-range').first();
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

        if ($wire.filterDateFrom && $wire.filterDateTo) {
            el.val(moment($wire.filterDateFrom).format('MMM DD, YYYY') + ' – ' + moment($wire.filterDateTo).format('MMM DD, YYYY'));
        }
    }

    waitForLibraries(() => initDateRangePicker());
</script>
@endscript
