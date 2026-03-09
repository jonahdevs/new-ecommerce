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
            $this->dispatch('notify', variant: 'success', message: 'Return status updated.');
        } catch (\Throwable $e) {
            logger()->error('Failed to update return status.', [
                'exception' => $e->getMessage(),
                'order_id' => $this->viewingId,
                'status' => $this->newStatus,
                'user_id' => auth()->id(),
            ]);
            $this->dispatch('notify', variant: 'danger', message: 'Could not update status. Please try again.');
        }
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

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate />
        <flux:breadcrumbs.item>Logistics</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Returns</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between mb-8">
        <div>
            <flux:heading size="xl" class="mb-2">Returns</flux:heading>
            <flux:subheading>Reverse logistics — parcels being returned from customer back to sender. Cossim charges
                returns at the same rate as forward delivery.</flux:subheading>
        </div>
    </div>


    <flux:card class="p-0 **:data-flux-columns:bg-zinc-50">
        {{-- Filters --}}
        <div class="flex flex-col md:flex-row md:justify-between border-b px-5 py-2 gap-3">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Order ID, reference..."
                icon="magnifying-glass" clearable class="col-span-2 md:col-span-1" class="max-w-md" />

            <div class="flex items-center gap-2">
                <flux:select wire:model.live="filterStatus" placeholder="All Statuses" clearable>
                    @foreach ($this->statuses as $status)
                        <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:dropdown>
                    <flux:button icon="adjustments-horizontal" variant="ghost">
                        Filters
                        @if ($search || $filterStatus || $filterMethod || $filterZone || $filterDateFrom || $filterDateTo)
                            <span class="ml-2 w-2 h-2 rounded-full bg-indigo-500"></span>
                        @endif
                    </flux:button>

                    <flux:menu class="min-w-72">
                        <div class="p-4 space-y-4">
                            <flux:heading size="sm">Advanced Filters</flux:heading>

                            <flux:select wire:model.live="filterMethod" placeholder="All Methods" clearable>
                                @foreach ($this->methods as $method)
                                    <flux:select.option value="{{ $method->id }}">{{ $method->name }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>

                            <flux:select wire:model.live="filterZone" placeholder="All Zones" clearable>
                                @foreach ($this->zones as $zone)
                                    <flux:select.option value="{{ $zone->id }}">{{ $zone->name }}
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

                            <flux:menu.separator />
                            <flux:button variant="ghost" size="sm" wire:click="clearFilters"
                                class="cursor-pointer w-full">
                                Clear filters
                            </flux:button>
                        </div>
                    </flux:menu>
                </flux:dropdown>
            </div>
        </div>
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
                            <div class="font-semibold text-sm">#{{ $order->order_id }}</div>
                            @if ($order->provider_reference)
                                <code class="text-xs text-zinc-400">{{ $order->provider_reference }}</code>
                            @endif
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
                            <span class="text-xs text-zinc-500">{{ $order->created_at->format('d M Y') }}</span>
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
                            <div class="flex flex-col items-center gap-3 text-zinc-400">
                                <flux:icon.arrow-uturn-left class="w-10 h-10 opacity-40" />
                                <div>
                                    <p class="text-sm font-medium text-zinc-600 dark:text-zinc-300">No return orders
                                        found</p>
                                    <p class="text-xs mt-0.5">
                                        @if ($search || $filterStatus || $filterMethod || $filterZone || $filterDateFrom || $filterDateTo)
                                            No returns match your current filters.
                                        @else
                                            Return shipments will appear here when raised.
                                        @endif
                                    </p>
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

            <div class="flex items-start justify-between pb-4 border-b border-zinc-100 dark:border-zinc-800">
                <div>
                    <flux:heading size="lg">Return #{{ $order->order_id }}</flux:heading>
                    <flux:badge color="orange" variant="flat" size="sm" class="mt-1">Return Shipment</flux:badge>
                </div>
                <flux:badge :color="$status->color()" variant="flat">{{ $status->label() }}</flux:badge>
            </div>

            <div class="py-4 space-y-6">
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
                        <p class="text-zinc-400 text-xs mb-0.5">Cost</p>
                        <p class="font-semibold">KES {{ number_format($order->shipping_cost, 0) }}</p>
                    </div>
                    <div>
                        <p class="text-zinc-400 text-xs mb-0.5">Created</p>
                        <p class="font-medium">{{ $order->created_at->format('d M Y, H:i') }}</p>
                    </div>
                    @if ($order->delivered_at)
                        <div>
                            <p class="text-zinc-400 text-xs mb-0.5">Returned At</p>
                            <p class="font-medium text-green-600">{{ $order->delivered_at->format('d M Y, H:i') }}</p>
                        </div>
                    @endif
                </div>

                {{-- Cost breakdown --}}
                @if (!empty($breakdown))
                    <div>
                        <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Cost Breakdown</p>
                        <div
                            class="bg-zinc-50 dark:bg-zinc-800/60 rounded-lg divide-y divide-zinc-100 dark:divide-zinc-700 text-sm">
                            @foreach ($breakdown as $key => $value)
                                @if (!in_array($key, ['model', 'total']))
                                    <div class="flex justify-between px-3 py-2">
                                        <span class="text-zinc-500 capitalize">{{ str_replace('_', ' ', $key) }}</span>
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

                {{-- Status update --}}
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
                            <div class="bg-zinc-50 dark:bg-zinc-800/60 rounded-lg p-4 space-y-3">
                                <p class="text-sm">
                                    Mark as
                                    <strong>{{ \App\Enums\DeliveryOrderStatus::from($newStatus)->label() }}</strong>?
                                </p>
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
</div>

<style>
    [data-flux-pagination] {
        padding-inline: 1rem;
        padding-bottom: 1rem;
    }
</style>
