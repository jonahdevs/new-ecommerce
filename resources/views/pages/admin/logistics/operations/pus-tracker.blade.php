<?php

use App\Enums\DeliveryOrderStatus;
use App\Models\DeliveryOrder;
use App\Models\PickupStation;
use App\Settings\RegionalSettings;
use Livewire\Attributes\{Title, Computed, Url};
use Livewire\Component;
use Flux\Flux;

new #[Title('PUS Tracker')] class extends Component {
    #[Url(history: true)]
    public string $filterStation = '';

    #[Url(history: true)]
    public string $filterUrgency = ''; // all | overdue | today | this_week

    public ?int $viewingId = null;
    public ?int $actingId = null;
    public string $pendingAction = ''; // 'collected' | 'returning'

    public function updatedFilterStation(): void {}
    public function updatedFilterUrgency(): void {}

    //  Queries

    #[Computed]
    public function regionalSettings(): RegionalSettings
    {
        return app(RegionalSettings::class);
    }

    #[Computed]
    public function parcels()
    {
        return DeliveryOrder::with(['shippingMethod', 'shippingZone', 'pickupStation', 'logisticsProvider'])
            ->where('status', DeliveryOrderStatus::AT_STATION->value)
            ->when($this->filterStation, fn($q) => $q->where('pickup_station_id', $this->filterStation))
            ->when($this->filterUrgency === 'overdue', fn($q) => $q->where('collection_deadline_at', '<', now()))
            ->when($this->filterUrgency === 'today', fn($q) => $q->whereDate('collection_deadline_at', today()))
            ->when($this->filterUrgency === 'this_week', fn($q) => $q->whereBetween('collection_deadline_at', [now(), now()->endOfWeek()]))
            ->orderByRaw('ISNULL(collection_deadline_at), collection_deadline_at ASC')
            ->get();
    }

    #[Computed]
    public function stations()
    {
        return PickupStation::where('status', 'active')->orderBy('name')->get();
    }

    #[Computed]
    public function viewingOrder(): ?DeliveryOrder
    {
        if (!$this->viewingId) {
            return null;
        }
        return DeliveryOrder::with(['shippingMethod', 'shippingZone', 'pickupStation', 'logisticsProvider'])->find($this->viewingId);
    }

    //  Stats

    #[Computed]
    public function stats(): array
    {
        $base = DeliveryOrder::where('status', DeliveryOrderStatus::AT_STATION->value);

        return [
            'total' => (clone $base)->count(),
            'overdue' => (clone $base)->where('collection_deadline_at', '<', now())->count(),
            'today' => (clone $base)->whereDate('collection_deadline_at', today())->count(),
            'this_week' => (clone $base)->whereBetween('collection_deadline_at', [now(), now()->endOfWeek()])->count(),
        ];
    }

    //  Actions

    public function viewOrder(int $id): void
    {
        $this->viewingId = $id;
        unset($this->viewingOrder);
        Flux::modal('parcel-detail')->show();
    }

    public function confirmAction(int $orderId, string $action): void
    {
        $this->actingId = $orderId;
        $this->pendingAction = $action;
        Flux::modal('action-confirm')->show();
    }

    public function applyAction(): void
    {
        if (!$this->actingId || !$this->pendingAction) {
            return;
        }

        try {
            $order = DeliveryOrder::findOrFail($this->actingId);

            $updates = ['status' => $this->pendingAction];

            if ($this->pendingAction === DeliveryOrderStatus::COLLECTED->value) {
                $updates['delivered_at'] = now();
            }

            $order->update($updates);

            $this->actingId = null;
            $this->pendingAction = '';

            unset($this->parcels, $this->stats);
            Flux::modal('action-confirm')->close();

            $label = $this->pendingAction === DeliveryOrderStatus::COLLECTED->value ? 'Marked as collected.' : 'Marked for return.';

            $this->dispatch('notify', variant: 'success', message: $label);
        } catch (\Throwable $e) {
            logger()->error('PUS Tracker action failed.', [
                'exception' => $e->getMessage(),
                'order_id' => $this->actingId,
                'action' => $this->pendingAction,
                'user_id' => auth()->id(),
            ]);
            $this->dispatch('notify', variant: 'danger', message: 'Action failed. Please try again.');
        }
    }

    //  Helpers

    private function urgencyColor(\Carbon\Carbon|null $deadline): string
    {
        if (!$deadline) {
            return 'zinc';
        }
        if ($deadline->isPast()) {
            return 'red';
        }
        if ($deadline->isToday()) {
            return 'orange';
        }
        if ($deadline->diffInDays(now()) <= 2) {
            return 'yellow';
        }
        return 'green';
    }

    private function urgencyLabel(\Carbon\Carbon|null $deadline): string
    {
        if (!$deadline) {
            return 'No deadline';
        }
        if ($deadline->isPast()) {
            return 'Overdue ' . $deadline->diffForHumans();
        }
        return 'Due ' . $deadline->diffForHumans();
    }
}; ?>

<x-admin.logistics.layout heading="PUS Tracker"
    subheading="Parcels waiting at pickup stations. Sorted by collection deadline — most urgent first.">

    {{-- Stats bar --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <flux:card class="p-4 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors"
            wire:click="$set('filterUrgency', '')">
            <p class="text-xs text-zinc-400 mb-1">Total at Stations</p>
            <p class="text-2xl font-bold">{{ $this->stats['total'] }}</p>
        </flux:card>

        <flux:card
            class="p-4 cursor-pointer hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors border-red-100 dark:border-red-900"
            wire:click="$set('filterUrgency', 'overdue')">
            <p class="text-xs text-red-400 mb-1">Overdue</p>
            <p class="text-2xl font-bold text-red-600">{{ $this->stats['overdue'] }}</p>
        </flux:card>


        <flux:card
            class="p-4 cursor-pointer hover:bg-orange-50 dark:hover:bg-orange-900/20 transition-colors border-orange-100 dark:border-orange-900"
            wire:click="$set('filterUrgency', 'today')">
            <p class="text-xs text-orange-400 mb-1">Deadline Today</p>
            <p class="text-2xl font-bold text-orange-600">{{ $this->stats['today'] }}</p>
        </flux:card>

        <flux:card
            class="p-4 cursor-pointer hover:bg-yellow-50 dark:hover:bg-yellow-900/20 transition-colors border-yellow-100 dark:border-yellow-900"
            wire:click="$set('filterUrgency', 'this_week')">
            <p class="text-xs text-yellow-600 mb-1">Due This Week</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $this->stats['this_week'] }}</p>
        </flux:card>
    </div>


    <flux:card class="p-0 **:data-flux-columns:bg-zinc-50 dark:**:data-flux-columns:bg-zinc-800">
        {{-- Filters --}}
        <div class="flex flex-col justify-end md:flex-row gap-3 px-5 py-3 border-b dark:border-zinc-600">
            <flux:select wire:model.live="filterStation" placeholder="All Stations" clearable class="md:w-56">
                @foreach ($this->stations as $station)
                    <flux:select.option value="{{ $station->id }}">{{ $station->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="filterUrgency" placeholder="All Urgency" clearable class="md:w-44">
                <flux:select.option value="overdue">Overdue</flux:select.option>
                <flux:select.option value="today">Due Today</flux:select.option>
                <flux:select.option value="this_week">Due This Week</flux:select.option>
            </flux:select>

            @if ($filterStation || $filterUrgency)
                <flux:button variant="ghost" size="sm"
                    wire:click="$set('filterStation', ''); $set('filterUrgency', '')"
                    class="cursor-pointer self-center">
                    Clear filters
                </flux:button>
            @endif
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column class="ps-4!">Order</flux:table.column>
                <flux:table.column>Station</flux:table.column>
                <flux:table.column>Arrived</flux:table.column>
                <flux:table.column>Collection Deadline</flux:table.column>
                <flux:table.column>Cost</flux:table.column>
                <flux:table.column align="end" class="pe-4!">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->parcels as $parcel)
                    @php
                        $deadline = $parcel->collection_deadline_at;
                        $urgencyColor = $deadline
                            ? ($deadline->isPast()
                                ? 'red'
                                : ($deadline->isToday()
                                    ? 'orange'
                                    : ($deadline->diffInDays(now()) <= 2
                                        ? 'yellow'
                                        : 'green')))
                            : 'zinc';
                        $urgencyLabel = $deadline
                            ? ($deadline->isPast()
                                ? 'Overdue ' . $deadline->diffForHumans()
                                : 'Due ' . $deadline->diffForHumans())
                            : 'No deadline set';
                    @endphp
                    <flux:table.row :key="$parcel->id"
                        class="{{ $deadline?->isPast() ? 'bg-red-50/50 dark:bg-red-900/10' : '' }}">

                        <flux:table.cell class="ps-4!">
                            <div class="font-semibold text-sm">#{{ $parcel->order_id }}</div>
                            @if ($parcel->provider_reference)
                                <code class="text-xs text-zinc-400">{{ $parcel->provider_reference }}</code>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="text-sm font-medium">{{ $parcel->pickupStation->name }}</div>
                            <div class="text-xs text-zinc-400">{{ $parcel->pickupStation->county->name ?? '' }}</div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <span class="text-sm text-zinc-500">
                                {{ $parcel->updated_at->format('d M Y') }}
                            </span>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge :color="$urgencyColor" variant="flat" size="sm">
                                {{ $urgencyLabel }}
                            </flux:badge>
                            @if ($deadline)
                                <div class="text-xs text-zinc-400 mt-0.5">
                                    {{ $deadline->format('d M Y') }}
                                </div>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            <span class="text-sm font-medium">{{ format_currency($parcel->shipping_cost) }}</span>
                        </flux:table.cell>

                        <flux:table.cell align="end" class="pe-4!">
                            {{-- View detail --}}
                            <flux:button variant="ghost" size="sm" icon="eye" icon-variant="outline"
                                class="cursor-pointer" wire:click="viewOrder({{ $parcel->id }})" />

                            {{-- Mark collected --}}
                            <flux:button variant="ghost" size="sm" icon="check-circle" icon-variant="outline"
                                class="cursor-pointer text-green-600!"
                                wire:click="confirmAction({{ $parcel->id }}, 'collected')"
                                tooltip="Mark as Collected" />

                            {{-- Mark returning --}}
                            <flux:button variant="ghost" size="sm" icon="arrow-uturn-left" icon-variant="outline"
                                class="cursor-pointer text-red-500!"
                                wire:click="confirmAction({{ $parcel->id }}, 'returning')"
                                tooltip="Mark for Return" />
                        </flux:table.cell>
                    </flux:table.row>

                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="py-12 text-center">
                            <div class="flex flex-col items-center gap-3 text-zinc-400">
                                <flux:icon.building-storefront class="w-10 h-10 opacity-40" />
                                <div>
                                    <p class="text-sm font-medium text-zinc-600 dark:text-zinc-300">
                                        @if ($filterStation || $filterUrgency)
                                            No parcels match your filters
                                        @else
                                            No parcels awaiting collection
                                        @endif
                                    </p>
                                    <p class="text-xs mt-0.5">
                                        @if ($filterStation || $filterUrgency)
                                            Try adjusting your filters.
                                        @else
                                            Parcels arrive here once they reach a pickup station.
                                        @endif
                                    </p>
                                </div>
                                @if ($filterStation || $filterUrgency)
                                    <flux:button variant="ghost" size="sm"
                                        wire:click="$set('filterStation', ''); $set('filterUrgency', '')">
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

    {{-- Parcel detail flyout --}}
    <flux:modal name="parcel-detail" class="md:w-lg" variant="flyout">
        @if ($this->viewingOrder)
            @php
                $order = $this->viewingOrder;
                $deadline = $order->collection_deadline_at;
                $breakdown = $order->cost_breakdown ?? [];
            @endphp

            <div class="pb-4 border-b dark:border-zinc-600 border-zinc-100 dark:border-zinc-800">
                <flux:heading size="lg">Parcel #{{ $order->order_id }}</flux:heading>
                <p class="text-sm text-zinc-500 mt-1">{{ $order->pickupStation->name }}</p>
            </div>

            <div class="py-4 space-y-5">
                {{-- Deadline alert --}}
                @if ($deadline)
                    <flux:callout
                        variant="{{ $deadline->isPast() ? 'danger' : ($deadline->isToday() ? 'warning' : 'info') }}"
                        icon="{{ $deadline->isPast() ? 'exclamation-triangle' : 'clock' }}">
                        <flux:callout.heading>
                            {{ $deadline->isPast() ? 'Collection Overdue' : 'Collection Deadline' }}
                        </flux:callout.heading>
                        <flux:callout.text>
                            {{ $deadline->format('l, d M Y') }} &middot; {{ $deadline->diffForHumans() }}
                        </flux:callout.text>
                    </flux:callout>
                @endif

                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-zinc-400 text-xs mb-0.5">Shipping Method</p>
                        <p class="font-medium">{{ $order->shippingMethod->name }}</p>
                    </div>
                    <div>
                        <p class="text-zinc-400 text-xs mb-0.5">Zone</p>
                        <p class="font-medium">{{ $order->shippingZone->name }}</p>
                    </div>
                    <div>
                        <p class="text-zinc-400 text-xs mb-0.5">Shipping Cost</p>
                        <p class="font-semibold">{{ format_currency($order->shipping_cost) }}</p>
                    </div>
                    <div>
                        <p class="text-zinc-400 text-xs mb-0.5">Holding Days</p>
                        <p class="font-medium">{{ $order->pickupStation->holding_days }} days</p>
                    </div>
                    @if ($order->package_weight_kg)
                        <div>
                            <p class="text-zinc-400 text-xs mb-0.5">Package Weight</p>
                            <p class="font-medium">{{ $order->package_weight_kg }}
                                {{ $this->regionalSettings->weight_unit }}</p>
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
                                        <span
                                            class="text-zinc-500 capitalize">{{ str_replace('_', ' ', $key) }}</span>
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

                {{-- Quick actions --}}
                <div class="border-t border-zinc-100 dark:border-zinc-800 pt-4">
                    <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-3">Actions</p>
                    <div class="flex gap-3">
                        <flux:button variant="primary" icon="check-circle" class="flex-1 cursor-pointer"
                            wire:click="confirmAction({{ $order->id }}, 'collected')">
                            Mark Collected
                        </flux:button>
                        <flux:button variant="danger" icon="arrow-uturn-left" class="flex-1 cursor-pointer"
                            wire:click="confirmAction({{ $order->id }}, 'returning')">
                            Return to Sender
                        </flux:button>
                    </div>
                </div>
            </div>
        @endif
    </flux:modal>

    {{-- Action confirmation --}}
    <flux:modal name="action-confirm" class="md:w-88 space-y-6">
        <flux:heading size="lg" class="mb-2">
            {{ $pendingAction === 'collected' ? 'Confirm Collection' : 'Confirm Return' }}
        </flux:heading>
        <flux:subheading>
            @if ($pendingAction === 'collected')
                Mark this parcel as collected by the customer. This cannot be undone.
            @else
                Mark this parcel for return to sender. It will move to <strong>Returning</strong> status.
            @endif
        </flux:subheading>
        <div class="flex gap-3">
            <flux:modal.close class="flex-1">
                <flux:button variant="ghost" class="w-full cursor-pointer">Cancel</flux:button>
            </flux:modal.close>
            <flux:button wire:click="applyAction"
                variant="{{ $pendingAction === 'collected' ? 'primary' : 'danger' }}" class="flex-1 cursor-pointer">
                {{ $pendingAction === 'collected' ? 'Confirm' : 'Return Parcel' }}
            </flux:button>
        </div>
    </flux:modal>

</x-admin.logistics.layout>
