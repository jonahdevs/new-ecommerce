<?php

use App\Enums\DeliveryOrderStatus;
use App\Models\DeliveryOrder;
use App\Models\LogisticsProvider;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
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

    //  Bulk actions

    public array $selected = [];
    public bool $selectAll = false;
    public string $bulkStatus = '';

    //  Single order detail

    public ?int $viewingId = null;
    public string $statusNote = '';
    public string $newStatus = '';
    public bool $confirmingStatus = false;

    //  Lifecycle

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->selected = [];
    }
    public function updatedFilterStatus(): void
    {
        $this->resetPage();
        $this->selected = [];
    }
    public function updatedFilterMethod(): void
    {
        $this->resetPage();
        $this->selected = [];
    }
    public function updatedFilterZone(): void
    {
        $this->resetPage();
        $this->selected = [];
    }
    public function updatedFilterProvider(): void
    {
        $this->resetPage();
        $this->selected = [];
    }
    public function updatedFilterDateFrom(): void
    {
        $this->resetPage();
        $this->selected = [];
    }
    public function updatedFilterDateTo(): void
    {
        $this->resetPage();
        $this->selected = [];
    }

    public function updatedSelectAll(bool $value): void
    {
        $this->selected = $value ? $this->orders->pluck('id')->map(fn($id) => (string) $id)->toArray() : [];
    }

    //  Queries

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
            ->paginate(20);
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

    /**
     * Valid next statuses from any given current status.
     * Prevents illegal transitions (e.g. delivered → pending).
     */
    #[Computed]
    public function allowedTransitions(): array
    {
        if (!$this->viewingOrder) {
            return [];
        }

        $current = $this->viewingOrder->status instanceof DeliveryOrderStatus ? $this->viewingOrder->status : DeliveryOrderStatus::from($this->viewingOrder->status);

        return match ($current) {
            DeliveryOrderStatus::PENDING => [DeliveryOrderStatus::PICKEDUP, DeliveryOrderStatus::CANCELLED],
            DeliveryOrderStatus::PICKEDUP => [DeliveryOrderStatus::INTRANSIT],
            DeliveryOrderStatus::INTRANSIT => [DeliveryOrderStatus::OUTFORDELIVERY, DeliveryOrderStatus::ATSTATION],
            DeliveryOrderStatus::OUTFORDELIVERY => [DeliveryOrderStatus::DELIVERED, DeliveryOrderStatus::FAILED],
            DeliveryOrderStatus::FAILED => [DeliveryOrderStatus::RETURNING, DeliveryOrderStatus::OUTFORDELIVERY],
            DeliveryOrderStatus::ATSTATION => [DeliveryOrderStatus::COLLECTED, DeliveryOrderStatus::RETURNING],
            DeliveryOrderStatus::RETURNING => [DeliveryOrderStatus::RETURNED],
            default => [],
        };
    }

    // ── Single order actions ──────

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

            unset($this->viewingOrder, $this->allowedTransitions, $this->orders);
            $this->dispatch('notify', variant: 'success', message: 'Order status updated.');
        } catch (\Throwable $e) {
            logger()->error('Failed to update delivery order status.', [
                'exception' => $e->getMessage(),
                'order_id' => $this->viewingId,
                'status' => $this->newStatus,
                'user_id' => auth()->id(),
            ]);
            $this->dispatch('notify', variant: 'danger', message: 'Could not update status. Please try again.');
        }
    }

    // ── Bulk actions

    public function applyBulkStatus(): void
    {
        if (empty($this->selected) || !$this->bulkStatus) {
            $this->dispatch('notify', variant: 'warning', message: 'Select orders and a status first.');
            return;
        }

        try {
            $count = DeliveryOrder::whereIn('id', $this->selected)
                ->where('is_return', false)
                ->update(['status' => $this->bulkStatus]);

            $this->selected = [];
            $this->selectAll = false;
            $this->bulkStatus = '';

            unset($this->orders);
            Flux::modal('bulk-confirm')->close();
            $this->dispatch('notify', variant: 'success', message: "{$count} orders updated.");
        } catch (\Throwable $e) {
            logger()->error('Bulk status update failed.', [
                'exception' => $e->getMessage(),
                'ids' => $this->selected,
                'status' => $this->bulkStatus,
                'user_id' => auth()->id(),
            ]);
            $this->dispatch('notify', variant: 'danger', message: 'Bulk update failed. Please try again.');
        }
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
}; ?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate />
        <flux:breadcrumbs.item>Logistics</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Delivery Orders</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    {{-- Header & Bulk Actions --}}
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-6">
        <div class="flex-1">
            <flux:heading size="xl" class="mb-2">Delivery Orders</flux:heading>
            <flux:subheading>Track and manage all forward deliveries.</flux:subheading>
        </div>

        @if (count($this->selected))
            <div class="flex items-center gap-3 animate-in fade-in slide-in-from-top-1">
                <span class="text-sm font-medium text-zinc-500">{{ count($this->selected) }} selected</span>
                <flux:select wire:model.live="bulkStatus" placeholder="Update status..." class="w-48" small>
                    <flux:select.option value="picked_up">Picked Up</flux:select.option>
                    <flux:select.option value="in_transit">In Transit</flux:select.option>
                    <flux:select.option value="out_for_delivery">Out for Delivery</flux:select.option>
                    <flux:select.option value="delivered">Delivered</flux:select.option>
                </flux:select>
                <flux:button variant="primary" size="sm" wire:click="$flux.modal('bulk-confirm').show()"
                    :disabled="!$bulkStatus">
                    Apply
                </flux:button>
                <flux:button variant="ghost" size="sm" wire:click="$set('selected', []); $set('selectAll', false)">
                    Cancel
                </flux:button>
            </div>
        @endif
    </div>

    <flux:card class="p-0 **:data-flux-columns:bg-zinc-50">
        <div>
            <div class="flex flex-col md:flex-row md:justify-between border-b px-5 py-2 gap-3">
                {{-- 1. Main Search --}}
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by Order, Reference, or ID..."
                    class="max-w-md" icon="magnifying-glass" clearable />


                {{-- 2. The "Big Two" Filters (Status & Method) --}}
                <div class="flex gap-2">
                    <flux:select wire:model.live="filterStatus" placeholder="All Statuses" class="w-44" clearable
                        small>
                        @foreach ($this->statuses as $status)
                            <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    {{-- 3. Everything Else in one "More Filters" Dropdown --}}
                    <flux:dropdown>
                        <flux:button icon="adjustments-horizontal" variant="ghost">
                            Filters
                            @if ($filterMethod || $filterZone || $filterProvider || $filterDateFrom)
                                <span class="ml-2 w-2 h-2 rounded-full bg-indigo-500"></span>
                            @endif
                        </flux:button>

                        <flux:menu class="min-w-72">
                            <div class="p-4 space-y-4">

                                <flux:heading size="sm">Advanced Filters</flux:heading>

                                <flux:select wire:model.live="filterMethod" label="Shipping Method"
                                    placeholder="All Methods" small>
                                    @foreach ($this->methods as $method)
                                        <flux:select.option value="{{ $method->id }}">{{ $method->name }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>

                                <flux:select wire:model.live="filterZone" label="Shipping Zone" placeholder="All Zones"
                                    small>
                                    @foreach ($this->zones as $zone)
                                        <flux:select.option value="{{ $zone->id }}">{{ $zone->name }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>

                                <flux:select wire:model.live="filterProvider" label="Provider"
                                    placeholder="All Providers" small>
                                    @foreach ($this->providers as $provider)
                                        <flux:select.option value="{{ $provider->id }}">{{ $provider->name }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                                <div class="space-y-2">
                                    <flux:label>Date Range</flux:label>
                                    <div class="flex items-center gap-2">
                                        <flux:input wire:model.live="filterDateFrom" type="date" size="sm" />
                                        <span class="text-zinc-400">-</span>
                                        <flux:input wire:model.live="filterDateTo" type="date" size="sm" />
                                    </div>
                                </div>
                            </div>

                            <flux:menu.separator />

                            <flux:button variant="ghost" size="sm" wire:click="clearFilters"
                                class="w-full cursor-pointer">
                                Reset All Filters
                            </flux:button>
                        </flux:menu>
                    </flux:dropdown>
                </div>
            </div>

            {{-- 4. Active Filter Tags (Optional but helpful) --}}
            @if ($filterMethod || $filterZone || $filterProvider || $filterDateFrom)
                <div class="flex flex-wrap gap-2 animate-in fade-in zoom-in-95 duration-200 px-5 py-2 border-b">
                    <span
                        class="text-xs font-semibold text-zinc-400 uppercase tracking-wider self-center mr-1">Active:</span>
                    @if ($filterMethod)
                        <flux:badge size="sm" closable wire:click="$set('filterMethod', '')" variant="flat">Method:
                            {{ $this->methods->find($filterMethod)?->name }}</flux:badge>
                    @endif
                    {{-- Add similar badges for Zone, Provider, etc. --}}
                </div>
            @endif
        </div>

        <flux:table :paginate="$this->orders">
            <flux:table.columns>
                {{-- Checkbox column --}}
                <flux:table.column class="ps-4! w-10">
                    <flux:checkbox wire:model.live="selectAll" />
                </flux:table.column>
                <flux:table.column>Order</flux:table.column>
                <flux:table.column>Method</flux:table.column>
                <flux:table.column>Zone</flux:table.column>
                <flux:table.column>Cost</flux:table.column>
                <flux:table.column>Estimated</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end" class="pe-4!">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->orders as $order)
                    @php
                        $status =
                            $order->status instanceof \App\Enums\DeliveryOrderStatus
                                ? $order->status
                                : \App\Enums\DeliveryOrderStatus::from($order->status);
                    @endphp
                    <flux:table.row :key="$order->id">
                        <flux:table.cell class="ps-4! w-10">
                            <flux:checkbox wire:model.live="selected" value="{{ $order->id }}" />
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="font-semibold text-sm">#{{ $order->order_id }}</div>
                            @if ($order->provider_reference)
                                <code class="text-xs text-zinc-400">{{ $order->provider_reference }}</code>
                            @endif
                            <div class="text-xs text-zinc-400 mt-0.5">{{ $order->created_at->format('d M Y') }}</div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <span class="text-sm">{{ $order->shippingMethod->name }}</span>
                        </flux:table.cell>

                        <flux:table.cell>
                            <span class="text-sm">{{ $order->shippingZone->name }}</span>
                        </flux:table.cell>

                        <flux:table.cell>
                            <span class="text-sm font-medium">KES {{ number_format($order->shipping_cost, 0) }}</span>
                        </flux:table.cell>

                        <flux:table.cell>
                            <span class="text-xs text-zinc-500">
                                {{ $order->estimated_delivery_at?->format('d M') ?? '—' }}
                            </span>
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
                        <flux:table.cell colspan="8" class="py-12 text-center">
                            <div class="flex flex-col items-center gap-3 text-zinc-400">
                                <flux:icon.clipboard-document-list class="w-10 h-10 opacity-40" />
                                <div>
                                    <p class="text-sm font-medium text-zinc-600 dark:text-zinc-300">No delivery orders
                                        found</p>
                                    <p class="text-xs mt-0.5">
                                        @if ($search || $filterStatus || $filterMethod || $filterZone || $filterProvider || $filterDateFrom || $filterDateTo)
                                            No orders match your current filters.
                                        @else
                                            Delivery orders will appear here once customers place orders.
                                        @endif
                                    </p>
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

    {{--  Order Detail Slide-over  --}}
    <flux:modal name="order-detail" class="md:w-xl space-y-0" variant="flyout">
        @if ($this->viewingOrder)
            @php
                $order = $this->viewingOrder;
                $status =
                    $order->status instanceof \App\Enums\DeliveryOrderStatus
                        ? $order->status
                        : \App\Enums\DeliveryOrderStatus::from($order->status);
                $breakdown = $order->cost_breakdown ?? [];
            @endphp

            <div class="flex items-start justify-between pb-4 border-b border-zinc-100 dark:border-zinc-800">
                <div>
                    <flux:heading size="lg">Order #{{ $order->order_id }}</flux:heading>
                    @if ($order->provider_reference)
                        <code class="text-xs text-zinc-400">Ref: {{ $order->provider_reference }}</code>
                    @endif
                </div>
                <flux:badge :color="$status->color()" variant="flat">{{ $status->label() }}</flux:badge>
            </div>

            <div class="py-4 space-y-6">

                {{-- Summary row --}}
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
                        <p class="font-semibold text-base">KES {{ number_format($order->shipping_cost, 0) }}</p>
                    </div>
                    <div>
                        <p class="text-zinc-400 text-xs mb-0.5">Weight</p>
                        <p class="font-medium">
                            {{ $order->package_weight_kg ? $order->package_weight_kg . ' kg' : '—' }}</p>
                    </div>
                    <div>
                        <p class="text-zinc-400 text-xs mb-0.5">Created</p>
                        <p class="font-medium">{{ $order->created_at->format('d M Y, H:i') }}</p>
                    </div>
                </div>

                {{-- Dates --}}
                @if ($order->estimated_delivery_at || $order->delivered_at)
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        @if ($order->estimated_delivery_at)
                            <div>
                                <p class="text-zinc-400 text-xs mb-0.5">Estimated Delivery</p>
                                <p class="font-medium">{{ $order->estimated_delivery_at->format('d M Y, H:i') }}</p>
                            </div>
                        @endif
                        @if ($order->delivered_at)
                            <div>
                                <p class="text-zinc-400 text-xs mb-0.5">Delivered At</p>
                                <p class="font-medium text-green-600">{{ $order->delivered_at->format('d M Y, H:i') }}
                                </p>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- PUS info --}}
                @if ($order->pickupStation)
                    <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-3 text-sm space-y-1">
                        <p class="font-medium text-orange-700 dark:text-orange-300">Pickup Station</p>
                        <p>{{ $order->pickupStation->name }}</p>
                        @if ($order->collection_deadline_at)
                            <p class="text-orange-600 dark:text-orange-400 text-xs">
                                Collection deadline: {{ $order->collection_deadline_at->format('d M Y') }}
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
                                        <span
                                            class="text-zinc-500 capitalize">{{ str_replace('_', ' ', $key) }}</span>
                                        <span class="font-medium">
                                            {{ is_numeric($value) ? 'KES ' . number_format($value, 0) : $value }}
                                        </span>
                                    </div>
                                @endif
                            @endforeach
                            <div class="flex justify-between px-3 py-2 font-semibold">
                                <span>Total</span>
                                <span>KES {{ number_format($breakdown['total'] ?? $order->shipping_cost, 0) }}</span>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Status update section --}}
                @if (!$status->isTerminal() && count($this->allowedTransitions))
                    <div class="border-t border-zinc-100 dark:border-zinc-800 pt-4">
                        <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-3">Update Status</p>

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
                            {{-- Confirm panel --}}
                            <div class="bg-zinc-50 dark:bg-zinc-800/60 rounded-lg p-4 space-y-3">
                                <p class="text-sm">
                                    Mark as
                                    <strong>{{ \App\Enums\DeliveryOrderStatus::from($newStatus)->label() }}</strong>?
                                </p>
                                <flux:textarea wire:model="statusNote" placeholder="Optional note (internal only)..."
                                    rows="2" />
                                <div class="flex gap-2">
                                    <flux:button variant="ghost" size="sm" wire:click="cancelStatusUpdate"
                                        class="cursor-pointer">
                                        Cancel
                                    </flux:button>
                                    <flux:button variant="primary" size="sm" wire:click="applyStatusUpdate"
                                        class="cursor-pointer">
                                        Confirm Update
                                    </flux:button>
                                </div>
                            </div>
                        @endif
                    </div>
                @elseif ($status->isTerminal())
                    <div class="border-t border-zinc-100 dark:border-zinc-800 pt-4">
                        <p class="text-sm text-zinc-400">This order is in a terminal state and cannot be updated
                            further.</p>
                    </div>
                @endif
            </div>
        @endif
    </flux:modal>

    {{--  Bulk Status Confirm  --}}
    <flux:modal name="bulk-confirm" class="md:w-88 space-y-6">
        <flux:heading size="lg">Apply Bulk Update?</flux:heading>
        <flux:subheading>
            {{ count($this->selected) }} orders will be marked as
            <strong>{{ $bulkStatus ? \App\Enums\DeliveryOrderStatus::tryFrom($bulkStatus)?->label() : '' }}</strong>.
            This cannot be undone.
        </flux:subheading>
        <div class="flex gap-3">
            <flux:modal.close class="flex-1">
                <flux:button variant="ghost" class="w-full cursor-pointer">Cancel</flux:button>
            </flux:modal.close>
            <flux:button wire:click="applyBulkStatus" variant="primary" class="flex-1 cursor-pointer">
                Confirm
            </flux:button>
        </div>
    </flux:modal>
</div>

<style>
    [data-flux-pagination] {
        padding-inline: 1rem;
        padding-bottom: 1rem;
    }
</style>
