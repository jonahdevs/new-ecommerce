<?php

use App\Enums\DeliveryOrderStatus;
use App\Models\DeliveryOrder;
use App\Models\PickupStation;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public PickupStation $station;

    public function mount(PickupStation $pickupStation): void
    {
        $this->station = $pickupStation->load(['county', 'subCounty', 'logisticsProvider']);
    }

    public function rendering($view): void
    {
        $view->title($this->station->name);
    }

    #[Computed]
    public function counts(): array
    {
        $base = DeliveryOrder::where('pickup_station_id', $this->station->id);

        return [
            'at_station' => (clone $base)->where('status', DeliveryOrderStatus::AT_STATION->value)->count(),
            'overdue' => (clone $base)->where('status', DeliveryOrderStatus::AT_STATION->value)
                ->where('collection_deadline_at', '<', now())->count(),
            'collected_mtd' => (clone $base)->where('status', DeliveryOrderStatus::COLLECTED->value)
                ->where('updated_at', '>=', now()->startOfMonth())->count(),
            'lifetime' => (clone $base)->count(),
        ];
    }

    #[Computed]
    public function atStation()
    {
        return DeliveryOrder::with('shippingMethod')
            ->where('pickup_station_id', $this->station->id)
            ->where('status', DeliveryOrderStatus::AT_STATION->value)
            ->orderByRaw('ISNULL(collection_deadline_at), collection_deadline_at ASC')
            ->limit(15)
            ->get();
    }
}; ?>

<div>
    <div class="mb-4">
        <flux:button :href="route('admin.logistics.configuration.pickup-stations.index')" wire:navigate
            variant="ghost" size="sm" icon="arrow-left">All Stations</flux:button>
    </div>

    <flux:card class="p-6 mb-6">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <div class="flex items-center gap-3 flex-wrap mb-1">
                    <flux:heading size="xl">{{ $station->name }}</flux:heading>
                    @if ($station->is_primary)
                        <flux:badge color="blue" size="sm">Primary</flux:badge>
                    @endif
                    @php $stationStatus = $station->status instanceof \App\Enums\PickupStationStatus ? $station->status : \App\Enums\PickupStationStatus::from($station->status); @endphp
                    <flux:badge size="sm" :color="$stationStatus->color()" variant="flat">{{ $stationStatus->label() }}</flux:badge>
                </div>
                <flux:subheading>{{ $station->subCounty?->name }} · {{ $station->county?->name }} · {{ $station->logisticsProvider?->name }}</flux:subheading>
                <p class="text-sm text-zinc-500 mt-2">{{ $station->address }}</p>
                @if ($station->phone)
                    <p class="text-xs text-zinc-500 mt-1">📞 {{ $station->phone }}</p>
                @endif
                @if ($station->operating_hours)
                    <p class="text-xs text-zinc-500 mt-1">⏰ {{ $station->operating_hours }}</p>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700">
            <div>
                <p class="text-xs text-zinc-400 uppercase tracking-wide">At station</p>
                <p class="text-2xl font-bold tabular-nums mt-1">{{ $this->counts['at_station'] }}</p>
            </div>
            <div>
                <p class="text-xs text-zinc-400 uppercase tracking-wide text-red-500">Overdue</p>
                <p class="text-2xl font-bold tabular-nums mt-1 @if($this->counts['overdue'] > 0) text-red-500 @endif">{{ $this->counts['overdue'] }}</p>
            </div>
            <div>
                <p class="text-xs text-zinc-400 uppercase tracking-wide">Collected MTD</p>
                <p class="text-2xl font-bold tabular-nums mt-1">{{ $this->counts['collected_mtd'] }}</p>
            </div>
            <div>
                <p class="text-xs text-zinc-400 uppercase tracking-wide">Lifetime</p>
                <p class="text-2xl font-bold tabular-nums mt-1">{{ $this->counts['lifetime'] }}</p>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-800 text-sm">
            <div>
                <p class="text-xs text-zinc-400">Holding days</p>
                <p class="font-medium">{{ $station->holding_days }}</p>
            </div>
            @if ($station->latitude && $station->longitude)
                <div>
                    <p class="text-xs text-zinc-400">Coordinates</p>
                    <p class="font-mono text-xs">{{ number_format($station->latitude, 4) }}, {{ number_format($station->longitude, 4) }}</p>
                </div>
            @endif
            <div>
                <p class="text-xs text-zinc-400">Code</p>
                <code class="text-xs">{{ $station->code }}</code>
            </div>
        </div>
    </flux:card>

    <flux:card class="p-0">
        <div class="px-5 py-3 border-b border-zinc-200 dark:border-zinc-700">
            <flux:heading size="sm">Awaiting collection</flux:heading>
            <flux:subheading class="text-xs">Earliest deadlines first.</flux:subheading>
        </div>

        @if ($this->atStation->isEmpty())
            <div class="px-5 py-10 text-center">
                <flux:icon.check-circle class="size-8 mx-auto mb-2 text-emerald-500" />
                <p class="text-sm text-zinc-500">Nothing waiting collection.</p>
            </div>
        @else
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800 text-sm">
                @foreach ($this->atStation as $order)
                    @php
                        $deadline = $order->collection_deadline_at;
                        $isOverdue = $deadline?->isPast();
                        $isToday = $deadline?->isToday();
                    @endphp
                    <a href="{{ route('admin.logistics.delivery-orders.show', $order) }}" wire:navigate
                        class="flex items-center justify-between px-5 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-800/60">
                        <div>
                            <span class="font-semibold">#{{ $order->order_id }}</span>
                            <p class="text-[11px] text-zinc-400 mt-0.5">{{ $order->shippingMethod?->name }}</p>
                        </div>
                        @if ($deadline)
                            <span @class([
                                'text-xs font-semibold',
                                'text-red-500' => $isOverdue,
                                'text-orange-500' => !$isOverdue && $isToday,
                                'text-zinc-500' => !$isOverdue && !$isToday,
                            ])>
                                {{ $isOverdue ? 'Overdue · '.$deadline->diffForHumans() : ($isToday ? 'Due today' : $deadline->format('d M Y')) }}
                            </span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </flux:card>
</div>
