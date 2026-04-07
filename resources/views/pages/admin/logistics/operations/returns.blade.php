<?php

use App\Enums\DeliveryOrderStatus;
use App\Models\DeliveryOrder;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use Livewire\Attributes\{Title, Computed, Url};
use Livewire\WithPagination;
use Livewire\Component;
use Flux\Flux;

new #[Title('Returns')] class extends Component {
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
    public string $filterDateFrom = '';

    #[Url(history: true)]
    public string $filterDateTo = '';

    //  Order detail

    public ?int $viewingId = null;
    public string $newStatus = '';
    public string $statusNote = '';
    public bool $confirmingStatus = false;

    //  Lifecycle

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }
    public function updatedFilterMethod(): void
    {
        $this->resetPage();
    }
    public function updatedFilterZone(): void
    {
        $this->resetPage();
    }
    public function updatedFilterDateFrom(): void
    {
        $this->resetPage();
    }
    public function updatedFilterDateTo(): void
    {
        $this->resetPage();
    }

    // ── Queries ─

    #[Computed]
    public function returns()
    {
        return DeliveryOrder::with(['shippingMethod', 'shippingZone', 'logisticsProvider', 'pickupStation'])
            ->where('is_return', true)
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
            ->when($this->filterDateFrom, fn($q) => $q->whereDate('created_at', '>=', $this->filterDateFrom))
            ->when($this->filterDateTo, fn($q) => $q->whereDate('created_at', '<=', $this->filterDateTo))
            ->latest()
            ->paginate(10);
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
        // Returns only cycle through relevant statuses
        return [DeliveryOrderStatus::PENDING, DeliveryOrderStatus::PICKED_UP, DeliveryOrderStatus::IN_TRANSIT, DeliveryOrderStatus::RETURNING, DeliveryOrderStatus::RETURNED, DeliveryOrderStatus::CANCELLED];
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
    public function allowedTransitions(): array
    {
        if (!$this->viewingOrder) {
            return [];
        }

        $current = $this->viewingOrder->status instanceof DeliveryOrderStatus ? $this->viewingOrder->status : DeliveryOrderStatus::from($this->viewingOrder->status);

        // Returns flow: pending → picked_up → in_transit → returning → returned
        return match ($current) {
            DeliveryOrderStatus::PENDING => [DeliveryOrderStatus::PICKED_UP, DeliveryOrderStatus::CANCELLED],
            DeliveryOrderStatus::PICKED_UP => [DeliveryOrderStatus::IN_TRANSIT],
            DeliveryOrderStatus::IN_TRANSIT => [DeliveryOrderStatus::RETURNING],
            DeliveryOrderStatus::RETURNING => [DeliveryOrderStatus::RETURNED],
            default => [],
        };
    }

    //  Actions

    public function viewOrder(int $id): void
    {
        $this->viewingId = $id;
        $this->newStatus = '';
        $this->statusNote = '';
        $this->confirmingStatus = false;
        unset($this->viewingOrder, $this->allowedTransitions);
        Flux::modal('return-detail')->show();
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
            $order->update(['status' => $this->newStatus]);

            $this->confirmingStatus = false;
            $this->statusNote = '';
            $this->newStatus = '';

            unset($this->viewingOrder, $this->allowedTransitions, $this->returns);
            $this->dispatch('notify', title: 'Status Updated', variant: 'success', message: 'Return status updated.');
        } catch (\Throwable $e) {
            $this->dispatch('notify', title: 'Update Failed', variant: 'danger', message: 'Could not update status. Please try again.');
        }
    }

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
        $this->filterDateFrom = '';
        $this->filterDateTo = '';
        $this->resetPage();
    }
}; ?>

<x-admin.logistics.layout heading="Returns"
    subheading="Reverse logistics — parcels being returned from customer back to sender. Returns are charged at the same rate as forward delivery.">


    <flux:card class="p-0 **:data-flux-columns:bg-zinc-50 dark:**:data-flux-columns:bg-zinc-800">
        {{-- Filters --}}
        <div class="flex items-center gap-3 border-b dark:border-zinc-600 px-5 py-3">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Order ID, reference..."
                icon="magnifying-glass" clearable class="max-w-xs" />

            <div class="flex items-center gap-2 ms-auto">

                {{-- Date range picker --}}
                <div class="relative" wire:ignore>
                    <input type="text" readonly
                        class="returns-date-range w-56 pl-8 pr-3 py-2 text-sm border border-zinc-200 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 cursor-pointer focus:outline-none focus:ring-2 focus:ring-zinc-300 hover:border-zinc-400 transition-colors"
                        placeholder="All dates" />
                    <flux:icon.calendar-days class="size-4 absolute left-2.5 top-1/2 -translate-y-1/2 text-zinc-400 pointer-events-none" />
                </div>

                <flux:icon.loading wire:loading wire:target="setDateRange" class="size-3.5 text-zinc-400" />

                <flux:select wire:model.live="filterStatus" placeholder="All Statuses" clearable>
                    @foreach ($this->statuses as $status)
                        <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:dropdown>
                    <flux:button icon="funnel" icon-variant="outline" variant="ghost" size="sm">
                        Filters
                        @if ($filterMethod || $filterZone)
                            <flux:badge size="sm" class="ms-2" color="blue">
                                {{ collect([$filterMethod, $filterZone])->filter()->count() }}
                            </flux:badge>
                        @endif
                    </flux:button>

                    <flux:menu class="min-w-80">
                        <div>
                            <div class="flex items-center justify-between border-b dark:border-zinc-600 px-4 py-2">
                                <flux:heading size="sm">Filter Options</flux:heading>
                                @if ($filterMethod || $filterZone)
                                    <flux:button variant="ghost" size="xs" wire:click="clearFilters"
                                        class="cursor-pointer">
                                        Reset
                                    </flux:button>
                                @endif
                            </div>

                            <div class="space-y-3 p-5">
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
                            </div>
                        </div>
                    </flux:menu>
                </flux:dropdown>

                @if ($search || $filterStatus || $filterMethod || $filterZone || $filterDateFrom || $filterDateTo)
                    <flux:button wire:click="clearFilters" variant="ghost" size="sm" icon="x-mark">Clear</flux:button>
                @endif
            </div>
        </div>

        {{-- Active filter tags --}}
        @if ($filterStatus || $filterMethod || $filterZone || $filterDateFrom || $filterDateTo)
            <div class="flex flex-wrap gap-2 px-5 py-2 border-b border-zinc-200 dark:border-zinc-600">
                <span class="text-xs font-semibold text-zinc-400 uppercase tracking-wider self-center me-1">Active:</span>

                @if ($filterStatus)
                    <flux:badge size="sm" variant="flat" closable wire:click="$set('filterStatus', '')">
                        Status: {{ \App\Enums\DeliveryOrderStatus::tryFrom($filterStatus)?->label() }}
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

                @if ($filterDateFrom || $filterDateTo)
                    <flux:badge size="sm" variant="flat" closable wire:click="setDateRange('', '')">
                        {{ $filterDateFrom ? \Carbon\Carbon::parse($filterDateFrom)->format('M d, Y') : '…' }}
                        –
                        {{ $filterDateTo ? \Carbon\Carbon::parse($filterDateTo)->format('M d, Y') : '…' }}
                    </flux:badge>
                @endif
            </div>
        @endif

        <flux:table :paginate="$this->returns">
            <flux:table.columns>
                <flux:table.column class="ps-4!">Order</flux:table.column>
                <flux:table.column>Method</flux:table.column>
                <flux:table.column>Zone</flux:table.column>
                <flux:table.column>Cost</flux:table.column>
                <flux:table.column>Raised</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end" class="pe-4!">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->returns as $order)
                    @php
                        $status =
                            $order->status instanceof \App\Enums\DeliveryOrderStatus
                                ? $order->status
                                : \App\Enums\DeliveryOrderStatus::from($order->status);
                    @endphp
                    <flux:table.row :key="$order->id">
                        <flux:table.cell class="ps-4!">
                            <flux:heading size="sm" class="font-semibold!">#{{ $order->order_id }}</flux:heading>
                            @if ($order->provider_reference)
                                <flux:subheading class="text-xs! font-mono">{{ $order->provider_reference }}
                                </flux:subheading>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:text class="text-sm">{{ $order->shippingMethod->name }}</flux:text>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:text class="text-sm">{{ $order->shippingZone->name }}</flux:text>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:heading size="sm" class="font-medium!">
                                {{ format_currency($order->shipping_cost) }}</flux:heading>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:subheading class="text-xs!">{{ $order->created_at->format('d M Y') }}
                            </flux:subheading>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge :color="$status->color()" variant="flat" size="sm">
                                {{ $status->label() }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell align="end" class="pe-4!">
                            <flux:button variant="ghost" size="sm" icon="eye" icon-variant="outline"
                                class="cursor-pointer" wire:click="viewOrder({{ $order->id }})" />
                        </flux:table.cell>
                    </flux:table.row>

                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="py-12 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <flux:icon.arrow-uturn-left class="w-10 h-10 opacity-40 text-zinc-400" />
                                <div>
                                    <flux:heading size="sm" class="font-medium!">No return orders found
                                    </flux:heading>
                                    <flux:subheading class="text-xs! mt-0.5">
                                        @if ($search || $filterStatus || $filterMethod || $filterZone || $filterDateFrom || $filterDateTo)
                                            No returns match your current filters.
                                        @else
                                            Return shipments will appear here when raised.
                                        @endif
                                    </flux:subheading>
                                </div>
                                @if ($search || $filterStatus || $filterMethod || $filterZone || $filterDateFrom || $filterDateTo)
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

    {{-- Return Detail Slide-over --}}
    <flux:modal name="return-detail" class="md:w-xl" variant="flyout">
        @if ($this->viewingOrder)
            @php
                $order = $this->viewingOrder;
                $status =
                    $order->status instanceof \App\Enums\DeliveryOrderStatus
                        ? $order->status
                        : \App\Enums\DeliveryOrderStatus::from($order->status);
                $breakdown = $order->cost_breakdown ?? [];
            @endphp

            <div
                class="flex items-start justify-between pb-4 border-b dark:border-zinc-600 border-zinc-100 dark:border-zinc-800">
                <div>
                    <flux:heading size="lg">Return #{{ $order->order_id }}</flux:heading>
                    <flux:badge color="orange" variant="flat" size="sm" class="mt-1">Return Shipment</flux:badge>
                </div>
                <flux:badge :color="$status->color()" variant="flat">{{ $status->label() }}</flux:badge>
            </div>

            <div class="py-4 space-y-6">
                <div class="grid grid-cols-3 gap-4 text-sm">
                    <div>
                        <flux:subheading class="text-xs! mb-0.5">Method</flux:subheading>
                        <flux:heading size="sm" class="font-medium!">{{ $order->shippingMethod->name }}
                        </flux:heading>
                    </div>
                    <div>
                        <flux:subheading class="text-xs! mb-0.5">Zone</flux:subheading>
                        <flux:heading size="sm" class="font-medium!">{{ $order->shippingZone->name }}
                        </flux:heading>
                    </div>
                    <div>
                        <flux:subheading class="text-xs! mb-0.5">Cost</flux:subheading>
                        <flux:heading size="sm" class="font-semibold!">
                            {{ format_currency($order->shipping_cost) }}</flux:heading>
                    </div>
                    <div>
                        <flux:subheading class="text-xs! mb-0.5">Created</flux:subheading>
                        <flux:heading size="sm" class="font-medium!">
                            {{ $order->created_at->format('d M Y, H:i') }}</flux:heading>
                    </div>
                    @if ($order->delivered_at)
                        <div>
                            <flux:subheading class="text-xs! mb-0.5">Returned At</flux:subheading>
                            <flux:heading size="sm" class="font-medium! text-green-600">
                                {{ $order->delivered_at->format('d M Y, H:i') }}</flux:heading>
                        </div>
                    @endif
                </div>

                {{-- Cost breakdown --}}
                @if (!empty($breakdown))
                    <div>
                        <flux:heading size="sm" class="font-medium! mb-2">Cost Breakdown</flux:heading>
                        <div
                            class="bg-zinc-50 dark:bg-zinc-800/60 rounded-lg divide-y divide-zinc-100 dark:divide-zinc-700 text-sm">
                            @foreach ($breakdown as $key => $value)
                                @if (!in_array($key, ['model', 'total']))
                                    <div class="flex justify-between px-3 py-2">
                                        <flux:subheading class="capitalize">{{ str_replace('_', ' ', $key) }}
                                        </flux:subheading>
                                        <flux:heading size="sm" class="font-medium!">
                                            {{ is_numeric($value) ? format_currency($value) : $value }}
                                        </flux:heading>
                                    </div>
                                @endif
                            @endforeach
                            <div class="flex justify-between px-3 py-2">
                                <flux:heading size="sm" class="font-semibold!">Total</flux:heading>
                                <flux:heading size="sm" class="font-semibold!">
                                    {{ format_currency($breakdown['total'] ?? $order->shipping_cost) }}</flux:heading>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Status update --}}
                @if (!$status->isTerminal() && count($this->allowedTransitions))
                    <div class="border-t border-zinc-100 dark:border-zinc-800 pt-4">
                        <flux:heading size="sm" class="font-medium! mb-3">Update Status</flux:heading>

                        @if (!$confirmingStatus)
                            <div class="flex flex-wrap gap-2">
                                @foreach ($this->allowedTransitions as $transition)
                                    <flux:button variant="outline" size="sm"
                                        wire:click="prepareStatusUpdate('{{ $transition->value }}')"
                                        class="cursor-pointer">
                                        → {{ $transition->label() }}
                                    </flux:button>
                                @endforeach
                            </div>
                        @else
                            <div class="bg-zinc-50 dark:bg-zinc-800/60 rounded-lg p-4 space-y-3">
                                <flux:text class="text-sm">
                                    Mark as
                                    <strong>{{ \App\Enums\DeliveryOrderStatus::from($newStatus)->label() }}</strong>?
                                </flux:text>
                                <flux:textarea wire:model="statusNote" placeholder="Optional note..."
                                    rows="2" />
                                <div class="flex gap-2">
                                    <flux:button variant="ghost" size="sm" wire:click="cancelStatusUpdate"
                                        class="cursor-pointer">
                                        Cancel
                                    </flux:button>
                                    <flux:button variant="primary" size="sm" wire:click="applyStatusUpdate"
                                        class="cursor-pointer">
                                        Confirm
                                    </flux:button>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @endif
    </flux:modal>

</x-admin.logistics.layout>

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
        const el = $('.returns-date-range').first();
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
