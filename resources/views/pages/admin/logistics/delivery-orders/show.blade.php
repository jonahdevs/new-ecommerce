<?php

use App\Enums\DeliveryOrderStatus;
use App\Models\DeliveryOrder;
use App\Settings\RegionalSettings;
use Livewire\Attributes\{Computed, Title};
use Livewire\Component;

new #[Title('Delivery Order')] class extends Component {
    public DeliveryOrder $deliveryOrder;

    public string $newStatus = '';

    public string $statusNote = '';

    public bool $confirmingStatus = false;

    public function mount(DeliveryOrder $deliveryOrder): void
    {
        $this->deliveryOrder = $deliveryOrder->load([
            'order.user',
            'shippingMethod',
            'shippingZone',
            'logisticsProvider',
            'pickupStation',
            'shippingRate',
            'vehicleRate',
        ]);
    }

    #[Computed]
    public function allowedTransitions(): array
    {
        return match ($this->deliveryOrder->status) {
            DeliveryOrderStatus::PENDING          => [DeliveryOrderStatus::PICKED_UP, DeliveryOrderStatus::CANCELLED],
            DeliveryOrderStatus::PICKED_UP        => [DeliveryOrderStatus::IN_TRANSIT],
            DeliveryOrderStatus::IN_TRANSIT       => [DeliveryOrderStatus::OUT_FOR_DELIVERY, DeliveryOrderStatus::AT_STATION],
            DeliveryOrderStatus::OUT_FOR_DELIVERY => [DeliveryOrderStatus::DELIVERED, DeliveryOrderStatus::FAILED],
            DeliveryOrderStatus::FAILED           => [DeliveryOrderStatus::RETURNING, DeliveryOrderStatus::OUT_FOR_DELIVERY],
            DeliveryOrderStatus::AT_STATION       => [DeliveryOrderStatus::COLLECTED, DeliveryOrderStatus::RETURNING],
            DeliveryOrderStatus::RETURNING        => [DeliveryOrderStatus::RETURNED],
            default                               => [],
        };
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
        if (! $this->newStatus) {
            return;
        }

        try {
            $updates = ['status' => $this->newStatus];

            if ($this->newStatus === DeliveryOrderStatus::DELIVERED->value) {
                $updates['delivered_at'] = now();
            }

            if ($this->newStatus === DeliveryOrderStatus::COLLECTED->value) {
                $updates['delivered_at'] ??= now();
            }

            $this->deliveryOrder->update($updates);
            $this->deliveryOrder->refresh();

            $this->confirmingStatus = false;
            $this->statusNote = '';
            $this->newStatus = '';
            unset($this->allowedTransitions);

            $this->dispatch('notify', title: 'Status Updated', variant: 'success', message: 'Delivery order status updated.');
        } catch (\Throwable $e) {
            logger()->error('Failed to update delivery order status.', [
                'exception' => $e->getMessage(),
                'id'        => $this->deliveryOrder->id,
                'user_id'   => auth()->id(),
            ]);
            $this->dispatch('notify', title: 'Update Failed', variant: 'danger', message: 'Could not update status. Please try again.');
        }
    }
};
?>

<div>
    {{-- ================================================================== --}}
    {{-- PAGE HEADER                                                         --}}
    {{-- ================================================================== --}}
    <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 mb-6">
        <div>
            @push('breadcrumbs')
                <flux:breadcrumbs>
                    <flux:breadcrumbs.item :href="route('admin.logistics.overview')" wire:navigate>
                        Logistics
                    </flux:breadcrumbs.item>
                    <flux:breadcrumbs.item>Delivery #{{ $deliveryOrder->order_id }}</flux:breadcrumbs.item>
                </flux:breadcrumbs>
            @endpush

            <div class="flex items-center gap-3 flex-wrap">
                <flux:heading size="xl" class="font-bold! tracking-tight">
                    Delivery #{{ $deliveryOrder->order_id }}
                </flux:heading>
                <flux:badge color="zinc" variant="solid" size="sm" class="uppercase text-[10px] tracking-widest font-bold">
                    {{ $deliveryOrder->status->label() }}
                </flux:badge>
                @if ($deliveryOrder->is_return)
                    <flux:badge color="orange" variant="outline" size="sm">Return</flux:badge>
                @endif
                @if ($deliveryOrder->pickupStation)
                    <flux:badge color="blue" variant="outline" size="sm">Pickup Station</flux:badge>
                @endif
            </div>

            <flux:subheading class="mt-1">
                Created {{ $deliveryOrder->created_at->format('M d, Y') }} at {{ $deliveryOrder->created_at->format('g:i A') }}
            </flux:subheading>
        </div>

        <flux:button variant="ghost" icon="arrow-left" size="sm"
            :href="route('admin.logistics.overview')" wire:navigate class="shrink-0">
            Back to Overview
        </flux:button>
    </div>

    {{-- ================================================================== --}}
    {{-- MAIN LAYOUT — 4-col grid                                           --}}
    {{-- ================================================================== --}}
    <div class="grid grid-cols-4 gap-5">

        {{-- ── Left: Main content (3 cols) ── --}}
        <div class="col-span-3 space-y-5">

            {{-- ============================================================ --}}
            {{-- DELIVERY DETAILS                                              --}}
            {{-- ============================================================ --}}
            <flux:card class="p-0">
                <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                    <flux:heading size="lg" class="font-semibold!">Delivery Details</flux:heading>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-3 gap-x-8 gap-y-6 text-sm">
                        <div>
                            <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider mb-1">Shipping Method</p>
                            <p class="font-medium text-zinc-800 dark:text-zinc-100">
                                {{ $deliveryOrder->shippingMethod?->name ?? '—' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider mb-1">Shipping Zone</p>
                            <p class="font-medium text-zinc-800 dark:text-zinc-100">
                                {{ $deliveryOrder->shippingZone?->name ?? '—' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider mb-1">Provider</p>
                            <p class="font-medium text-zinc-800 dark:text-zinc-100">
                                {{ $deliveryOrder->logisticsProvider?->name ?? '—' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider mb-1">Shipping Cost</p>
                            <p class="text-lg font-bold text-zinc-800 dark:text-zinc-100">
                                {{ format_currency($deliveryOrder->shipping_cost) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider mb-1">Package Weight</p>
                            <p class="font-medium text-zinc-800 dark:text-zinc-100">
                                @php $weightUnit = app(RegionalSettings::class)->weight_unit; @endphp
                                {{ $deliveryOrder->package_weight_kg ? $deliveryOrder->package_weight_kg . ' ' . $weightUnit : '—' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider mb-1">Distance</p>
                            <p class="font-medium text-zinc-800 dark:text-zinc-100">
                                {{ $deliveryOrder->distance_km ? $deliveryOrder->distance_km . ' km' : '—' }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 pt-6 border-t border-zinc-100 dark:border-zinc-800 grid grid-cols-3 gap-x-8 gap-y-6 text-sm">
                        <div>
                            <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider mb-1">Provider Reference</p>
                            <p class="font-mono text-xs font-medium text-zinc-800 dark:text-zinc-100 break-all">
                                {{ $deliveryOrder->provider_reference ?? '—' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider mb-1">Estimated Delivery</p>
                            @if ($deliveryOrder->estimated_delivery_at)
                                <p class="font-medium text-zinc-800 dark:text-zinc-100">
                                    {{ $deliveryOrder->estimated_delivery_at->format('M d, Y') }}
                                </p>
                                @if ($deliveryOrder->estimated_delivery_at->isPast() && $deliveryOrder->status->isActive())
                                    <p class="text-xs text-red-500 mt-0.5">Overdue by {{ $deliveryOrder->estimated_delivery_at->diffForHumans() }}</p>
                                @endif
                            @else
                                <p class="font-medium text-zinc-800 dark:text-zinc-100">—</p>
                            @endif
                        </div>
                        <div>
                            <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider mb-1">Delivered At</p>
                            @if ($deliveryOrder->delivered_at)
                                <p class="font-medium text-emerald-600 dark:text-emerald-400">
                                    {{ $deliveryOrder->delivered_at->format('M d, Y H:i') }}
                                </p>
                            @else
                                <p class="font-medium text-zinc-800 dark:text-zinc-100">—</p>
                            @endif
                        </div>
                    </div>
                </div>
            </flux:card>

            {{-- ============================================================ --}}
            {{-- PICKUP STATION (conditional)                                  --}}
            {{-- ============================================================ --}}
            @if ($deliveryOrder->pickupStation)
                <flux:card class="p-0">
                    <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                        <flux:heading size="lg" class="font-semibold!">Pickup Station</flux:heading>
                        @if ($deliveryOrder->isOverdueCollection())
                            <flux:badge color="red" variant="solid" size="sm">Collection Overdue</flux:badge>
                        @elseif ($deliveryOrder->status === DeliveryOrderStatus::COLLECTED)
                            <flux:badge color="green" variant="solid" size="sm">Collected</flux:badge>
                        @elseif ($deliveryOrder->status === DeliveryOrderStatus::AT_STATION)
                            <flux:badge color="orange" variant="solid" size="sm">Awaiting Collection</flux:badge>
                        @endif
                    </div>
                    <div class="p-6 grid grid-cols-3 gap-x-8 gap-y-6 text-sm">
                        <div>
                            <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider mb-1">Station Name</p>
                            <p class="font-medium text-zinc-800 dark:text-zinc-100">
                                {{ $deliveryOrder->pickupStation->name }}
                            </p>
                        </div>
                        @if ($deliveryOrder->pickupStation->location ?? null)
                            <div>
                                <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider mb-1">Location</p>
                                <p class="font-medium text-zinc-800 dark:text-zinc-100">
                                    {{ $deliveryOrder->pickupStation->location }}
                                </p>
                            </div>
                        @endif
                        @if ($deliveryOrder->collection_deadline_at)
                            <div>
                                <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider mb-1">Collection Deadline</p>
                                <p class="font-medium {{ $deliveryOrder->collection_deadline_at->isPast() ? 'text-red-500' : 'text-zinc-800 dark:text-zinc-100' }}">
                                    {{ $deliveryOrder->collection_deadline_at->format('M d, Y') }}
                                </p>
                                <p class="text-xs text-zinc-400 mt-0.5">{{ $deliveryOrder->collection_deadline_at->diffForHumans() }}</p>
                            </div>
                        @endif
                    </div>
                </flux:card>
            @endif

            {{-- ============================================================ --}}
            {{-- COST BREAKDOWN (conditional)                                  --}}
            {{-- ============================================================ --}}
            @if (!empty($deliveryOrder->cost_breakdown))
                @php $breakdown = $deliveryOrder->cost_breakdown; @endphp
                <flux:card class="p-0">
                    <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                        <flux:heading size="lg" class="font-semibold!">Cost Breakdown</flux:heading>
                    </div>
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($breakdown as $key => $value)
                            @if (! in_array($key, ['model', 'total']))
                                <div class="flex justify-between px-6 py-3 text-sm">
                                    <span class="text-zinc-500 capitalize">{{ str_replace('_', ' ', $key) }}</span>
                                    <span class="font-medium text-zinc-800 dark:text-zinc-100">
                                        {{ is_numeric($value) ? format_currency($value) : $value }}
                                    </span>
                                </div>
                            @endif
                        @endforeach
                        <div class="flex justify-between px-6 py-3 text-sm font-semibold bg-zinc-50 dark:bg-zinc-800/30">
                            <span>Total</span>
                            <span>{{ format_currency($breakdown['total'] ?? $deliveryOrder->shipping_cost) }}</span>
                        </div>
                    </div>
                </flux:card>
            @endif

            {{-- ============================================================ --}}
            {{-- LINKED ORDER                                                   --}}
            {{-- ============================================================ --}}
            @if ($deliveryOrder->order)
                <flux:card class="p-0">
                    <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                        <flux:heading size="lg" class="font-semibold!">Linked Order</flux:heading>
                    </div>
                    <div class="p-6 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-lg bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center shrink-0">
                                <flux:icon.shopping-bag class="size-5 text-zinc-400" />
                            </div>
                            <div>
                                <p class="font-semibold text-zinc-800 dark:text-zinc-100 text-sm">
                                    {{ $deliveryOrder->order->reference }}
                                </p>
                                <p class="text-xs text-zinc-400 mt-0.5">
                                    {{ $deliveryOrder->order->user?->name ?? 'Guest' }}
                                    &nbsp;·&nbsp;
                                    {{ $deliveryOrder->order->status->label() }}
                                </p>
                            </div>
                        </div>
                        <flux:button variant="ghost" size="sm" icon="arrow-top-right-on-square"
                            :href="route('admin.orders.show', $deliveryOrder->order)" wire:navigate>
                            View Order
                        </flux:button>
                    </div>
                </flux:card>
            @endif

        </div>

        {{-- ── Right: Sidebar (1 col) ── --}}
        <div class="col-span-1 space-y-4">

            {{-- ============================================================ --}}
            {{-- STATUS & ACTIONS                                               --}}
            {{-- ============================================================ --}}
            <flux:card class="p-0">
                <div class="px-5 py-4 border-b border-zinc-200 dark:border-zinc-700">
                    <flux:heading size="sm" class="font-semibold!">Status</flux:heading>
                </div>
                <div class="p-5 space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-zinc-400 uppercase tracking-wider">Current</span>
                        <flux:badge color="zinc" variant="flat">
                            {{ $deliveryOrder->status->label() }}
                        </flux:badge>
                    </div>

                    @if (! $deliveryOrder->isTerminal() && count($this->allowedTransitions))
                        <div class="pt-3 border-t border-zinc-100 dark:border-zinc-800">
                            @if (! $confirmingStatus)
                                <p class="text-xs text-zinc-400 mb-2">Advance to</p>
                                <div class="flex flex-col gap-2">
                                    @foreach ($this->allowedTransitions as $transition)
                                        <flux:button variant="outline" size="sm"
                                            class="w-full cursor-pointer justify-start"
                                            wire:click="prepareStatusUpdate('{{ $transition->value }}')">
                                            → {{ $transition->label() }}
                                        </flux:button>
                                    @endforeach
                                </div>
                            @else
                                <div class="space-y-3">
                                    <p class="text-sm text-zinc-700 dark:text-zinc-300">
                                        Mark as <strong>{{ DeliveryOrderStatus::from($newStatus)->label() }}</strong>?
                                    </p>
                                    <flux:textarea wire:model="statusNote"
                                        placeholder="Optional note (internal only)..." rows="2" />
                                    <div class="flex gap-2">
                                        <flux:button variant="ghost" size="sm" wire:click="cancelStatusUpdate"
                                            class="flex-1 cursor-pointer">Cancel</flux:button>
                                        <flux:button variant="primary" size="sm" wire:click="applyStatusUpdate"
                                            class="flex-1 cursor-pointer">Confirm</flux:button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @elseif ($deliveryOrder->isTerminal())
                        <p class="text-xs text-zinc-400 border-t border-zinc-100 dark:border-zinc-800 pt-3">
                            This delivery is in a terminal state and cannot be updated further.
                        </p>
                    @endif
                </div>
            </flux:card>

            {{-- ============================================================ --}}
            {{-- TIMELINE                                                       --}}
            {{-- ============================================================ --}}
            <flux:card class="p-0">
                <div class="px-5 py-4 border-b border-zinc-200 dark:border-zinc-700">
                    <flux:heading size="sm" class="font-semibold!">Pipeline</flux:heading>
                </div>
                <div class="p-5">
                    @php
                        $isPus = $deliveryOrder->pickupStation !== null;
                        $isReturn = $deliveryOrder->is_return;
                        $current = $deliveryOrder->status;

                        if ($isReturn) {
                            $stages = [
                                DeliveryOrderStatus::PENDING,
                                DeliveryOrderStatus::PICKED_UP,
                                DeliveryOrderStatus::IN_TRANSIT,
                                DeliveryOrderStatus::RETURNING,
                                DeliveryOrderStatus::RETURNED,
                            ];
                        } elseif ($isPus) {
                            $stages = [
                                DeliveryOrderStatus::PENDING,
                                DeliveryOrderStatus::PICKED_UP,
                                DeliveryOrderStatus::IN_TRANSIT,
                                DeliveryOrderStatus::AT_STATION,
                                DeliveryOrderStatus::COLLECTED,
                            ];
                        } else {
                            $stages = [
                                DeliveryOrderStatus::PENDING,
                                DeliveryOrderStatus::PICKED_UP,
                                DeliveryOrderStatus::IN_TRANSIT,
                                DeliveryOrderStatus::OUT_FOR_DELIVERY,
                                DeliveryOrderStatus::DELIVERED,
                            ];
                        }

                        $currentIdx = array_search($current, $stages);
                        $isFailed = in_array($current, [DeliveryOrderStatus::FAILED, DeliveryOrderStatus::CANCELLED]);
                    @endphp

                    @if ($isFailed)
                        <div class="flex items-center gap-2 text-sm text-red-500 font-medium mb-4">
                            <flux:icon.x-circle class="size-4 shrink-0" />
                            {{ $current->label() }}
                        </div>
                    @endif

                    <ol class="space-y-0">
                        @foreach ($stages as $i => $stage)
                            @php
                                $done = $currentIdx !== false && $i < $currentIdx;
                                $active = $current === $stage;
                                $upcoming = $currentIdx === false || $i > $currentIdx;
                                $isLast = $i === count($stages) - 1;
                            @endphp
                            <li class="flex gap-3 {{ $isLast ? '' : 'pb-4' }}">
                                {{-- Spine --}}
                                <div class="flex flex-col items-center shrink-0">
                                    <div @class([
                                        'w-5 h-5 rounded-full flex items-center justify-center shrink-0 z-10',
                                        'bg-zinc-800 dark:bg-zinc-100' => $done,
                                        'bg-zinc-800 dark:bg-zinc-100 ring-4 ring-zinc-200 dark:ring-zinc-700' => $active,
                                        'bg-zinc-200 dark:bg-zinc-700' => $upcoming && !$isFailed,
                                        'bg-zinc-200 dark:bg-zinc-700' => $isFailed,
                                    ])>
                                        @if ($done)
                                            <flux:icon.check class="size-2.5 text-white dark:text-zinc-900" />
                                        @endif
                                    </div>
                                    @if (! $isLast)
                                        <div @class([
                                            'w-px flex-1 mt-1',
                                            'bg-zinc-800 dark:bg-zinc-100' => $done,
                                            'bg-zinc-200 dark:bg-zinc-700' => ! $done,
                                        ])></div>
                                    @endif
                                </div>
                                {{-- Label --}}
                                <div class="pb-1">
                                    <p @class([
                                        'text-sm',
                                        'font-semibold text-zinc-800 dark:text-zinc-100' => $active,
                                        'text-zinc-500 dark:text-zinc-400' => $done,
                                        'text-zinc-300 dark:text-zinc-600' => $upcoming && ! $isFailed,
                                    ])>
                                        {{ $stage->label() }}
                                    </p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </flux:card>

        </div>

    </div>
</div>
