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

            $this->dispatch('notify', title: 'Action Complete', variant: 'success', message: $label);
        } catch (\Throwable $e) {
            logger()->error('PUS Tracker action failed.', [
                'exception' => $e->getMessage(),
                'order_id' => $this->actingId,
                'action' => $this->pendingAction,
                'user_id' => auth()->id(),
            ]);
            $this->dispatch('notify', title: 'Action Failed', variant: 'danger', message: 'Action failed. Please try again.');
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
            <flux:subheading class="text-xs! uppercase tracking-wide mb-1">Total at Stations</flux:subheading>
            <flux:heading size="xl" class="font-bold!">{{ $this->stats['total'] }}</flux:heading>
        </flux:card>

        <flux:card
            class="p-4 cursor-pointer hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors border-red-100 dark:border-red-900"
            wire:click="$set('filterUrgency', 'overdue')">
            <flux:subheading class="text-xs! uppercase tracking-wide text-red-400 mb-1">Overdue</flux:subheading>
            <flux:heading size="xl" class="font-bold! text-red-600">{{ $this->stats['overdue'] }}</flux:heading>
        </flux:card>


        <flux:card
            class="p-4 cursor-pointer hover:bg-orange-50 dark:hover:bg-orange-900/20 transition-colors border-orange-100 dark:border-orange-900"
            wire:click="$set('filterUrgency', 'today')">
            <flux:subheading class="text-xs! uppercase tracking-wide text-orange-400 mb-1">Deadline Today
            </flux:subheading>
            <flux:heading size="xl" class="font-bold! text-orange-600">{{ $this->stats['today'] }}</flux:heading>
        </flux:card>

        <flux:card
            class="p-4 cursor-pointer hover:bg-yellow-50 dark:hover:bg-yellow-900/20 transition-colors border-yellow-100 dark:border-yellow-900"
            wire:click="$set('filterUrgency', 'this_week')">
            <flux:subheading class="text-xs! uppercase tracking-wide text-yellow-600 mb-1">Due This Week
            </flux:subheading>
            <flux:heading size="xl" class="font-bold! text-yellow-600">{{ $this->stats['this_week'] }}
            </flux:heading>
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
                            <flux:heading size="sm" class="font-semibold!">#{{ $parcel->order_id }}</flux:heading>
                            @if ($parcel->provider_reference)
                                <flux:subheading class="text-xs! font-mono">{{ $parcel->provider_reference }}
                                </flux:subheading>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:heading size="sm" class="font-medium!">{{ $parcel->pickupStation->name }}
                            </flux:heading>
                            <flux:subheading class="text-xs!">{{ $parcel->pickupStation->county->name ?? '' }}
                            </flux:subheading>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:text class="text-sm">
                                {{ $parcel->updated_at->format('d M Y') }}
                            </flux:text>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge :color="$urgencyColor" variant="flat" size="sm">
                                {{ $urgencyLabel }}
                            </flux:badge>
                            @if ($deadline)
                                <flux:subheading class="text-xs! mt-0.5">
                                    {{ $deadline->format('d M Y') }}
                                </flux:subheading>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:heading size="sm" class="font-medium!">
                                {{ format_currency($parcel->shipping_cost) }}</flux:heading>
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
                            <div class="flex flex-col items-center gap-3">
                                <flux:icon.building-storefront class="w-10 h-10 opacity-40 text-zinc-400" />
                                <div>
                                    <flux:heading size="sm" class="font-medium!">
                                        @if ($filterStation || $filterUrgency)
                                            No parcels match your filters
                                        @else
                                            No parcels awaiting collection
                                        @endif
                                    </flux:heading>
                                    <flux:subheading class="text-xs! mt-0.5">
                                        @if ($filterStation || $filterUrgency)
                                            Try adjusting your filters.
                                        @else
                                            Parcels arrive here once they reach a pickup station.
                                        @endif
                                    </flux:subheading>
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
                <flux:subheading class="mt-1">{{ $order->pickupStation->name }}</flux:subheading>
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
                        <flux:subheading class="text-xs! mb-0.5">Shipping Method</flux:subheading>
                        <flux:heading size="sm" class="font-medium!">{{ $order->shippingMethod->name }}
                        </flux:heading>
                    </div>
                    <div>
                        <flux:subheading class="text-xs! mb-0.5">Zone</flux:subheading>
                        <flux:heading size="sm" class="font-medium!">{{ $order->shippingZone->name }}
                        </flux:heading>
                    </div>
                    <div>
                        <flux:subheading class="text-xs! mb-0.5">Shipping Cost</flux:subheading>
                        <flux:heading size="sm" class="font-semibold!">
                            {{ format_currency($order->shipping_cost) }}</flux:heading>
                    </div>
                    <div>
                        <flux:subheading class="text-xs! mb-0.5">Holding Days</flux:subheading>
                        <flux:heading size="sm" class="font-medium!">{{ $order->pickupStation->holding_days }}
                            days</flux:heading>
                    </div>
                    @if ($order->package_weight_kg)
                        <div>
                            <flux:subheading class="text-xs! mb-0.5">Package Weight</flux:subheading>
                            <flux:heading size="sm" class="font-medium!">{{ $order->package_weight_kg }}
                                {{ $this->regionalSettings->weight_unit }}</flux:heading>
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

                {{-- Quick actions --}}
                <div class="border-t border-zinc-100 dark:border-zinc-800 pt-4">
                    <flux:heading size="sm" class="font-medium! mb-3">Actions</flux:heading>
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
