<?php

use App\Enums\ShippingZoneStatus;
use App\Models\Address;
use App\Models\County;
use App\Models\DeliveryOrder;
use App\Models\FreeShippingRule;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\SubCounty;
use App\Models\Town;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public ShippingZone $zone;

    public function mount(ShippingZone $shippingZone): void
    {
        $this->zone = $shippingZone;
    }

    public function rendering($view): void
    {
        $view->title($this->zone->name);
    }

    // ─── Coverage counts ──────────────────────────────────────────────────

    #[Computed]
    public function counts(): array
    {
        return [
            'counties'           => County::where('shipping_zone_id', $this->zone->id)->count(),
            'sub_county_overrides' => SubCounty::where('shipping_zone_id', $this->zone->id)->count(),
            'town_overrides'     => Town::where('shipping_zone_id', $this->zone->id)->count(),
            'addresses'          => Address::where('shipping_zone_id', $this->zone->id)->count(),
            'orders_this_month'  => DeliveryOrder::where('shipping_zone_id', $this->zone->id)
                ->where('created_at', '>=', now()->startOfMonth())
                ->count(),
        ];
    }

    // ─── Coverage lists ───────────────────────────────────────────────────

    #[Computed]
    public function counties()
    {
        return County::where('shipping_zone_id', $this->zone->id)
            ->withCount('subCounties')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function subCountyOverrides()
    {
        return SubCounty::with('county')
            ->where('shipping_zone_id', $this->zone->id)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function townOverrides()
    {
        return Town::with(['subCounty', 'county'])
            ->where('shipping_zone_id', $this->zone->id)
            ->orderBy('name')
            ->get();
    }

    // ─── Rates ────────────────────────────────────────────────────────────

    #[Computed]
    public function rates()
    {
        return ShippingRate::with('shippingMethod')
            ->where('shipping_zone_id', $this->zone->id)
            ->orderBy('shipping_method_id')
            ->orderBy('min_weight')
            ->get();
    }

    #[Computed]
    public function methodsWithoutRates()
    {
        $hasRatesFor = ShippingRate::where('shipping_zone_id', $this->zone->id)
            ->where('status', 'active')
            ->pluck('shipping_method_id')
            ->unique();

        return ShippingMethod::where('status', 'active')
            ->whereNotIn('id', $hasRatesFor)
            ->get();
    }

    // ─── Free shipping ────────────────────────────────────────────────────

    #[Computed]
    public function freeShippingRules()
    {
        return FreeShippingRule::with('shippingMethod')
            ->where('shipping_zone_id', $this->zone->id)
            ->orderBy('name')
            ->get();
    }

    // ─── Recent activity ──────────────────────────────────────────────────

    #[Computed]
    public function recentOrders()
    {
        return DeliveryOrder::with('shippingMethod')
            ->where('shipping_zone_id', $this->zone->id)
            ->latest()
            ->limit(10)
            ->get();
    }
}; ?>

<div>
    @php
        $status = $zone->status instanceof ShippingZoneStatus
            ? $zone->status
            : ShippingZoneStatus::from($zone->status);
    @endphp

    {{-- ─── Breadcrumb / back ─────────────────────────────────────────── --}}
    <div class="mb-4">
        <flux:button :href="route('admin.logistics.configuration.zones.index')" wire:navigate
            variant="ghost" size="sm" icon="arrow-left">
            All Zones
        </flux:button>
    </div>

    {{-- ─── Header card ───────────────────────────────────────────────── --}}
    <flux:card class="p-6 mb-6">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-3 flex-wrap mb-2">
                    <flux:heading size="xl">{{ $zone->name }}</flux:heading>
                    <flux:badge :color="$status->color()" variant="flat" size="sm">{{ $status->label() }}</flux:badge>
                    @if ($zone->is_delivery_available)
                        <flux:badge color="green" variant="flat" size="sm">Delivery Available</flux:badge>
                    @else
                        <flux:badge color="zinc" variant="flat" size="sm">Not Deliverable</flux:badge>
                    @endif
                    @if ($zone->code)
                        <code class="text-xs bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 rounded">{{ $zone->code }}</code>
                    @endif
                </div>

                @if ($zone->description)
                    <flux:subheading class="max-w-3xl">{{ $zone->description }}</flux:subheading>
                @endif
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <flux:button :href="route('admin.logistics.configuration.zones.index')" wire:navigate
                    variant="outline" size="sm" icon="pencil-square">
                    Edit
                </flux:button>
            </div>
        </div>

        {{-- ─── Counts strip ──────────────────────────────────────────── --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700">
            <div>
                <p class="text-xs font-medium text-zinc-400 uppercase tracking-wide">Counties</p>
                <p class="text-2xl font-bold text-zinc-900 dark:text-white tabular-nums mt-1">{{ $this->counts['counties'] }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-zinc-400 uppercase tracking-wide">Sub-county overrides</p>
                <p class="text-2xl font-bold text-zinc-900 dark:text-white tabular-nums mt-1">{{ $this->counts['sub_county_overrides'] }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-zinc-400 uppercase tracking-wide">Town overrides</p>
                <p class="text-2xl font-bold text-zinc-900 dark:text-white tabular-nums mt-1">{{ $this->counts['town_overrides'] }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-zinc-400 uppercase tracking-wide">Addresses</p>
                <p class="text-2xl font-bold text-zinc-900 dark:text-white tabular-nums mt-1">{{ $this->counts['addresses'] }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-zinc-400 uppercase tracking-wide">Orders (MTD)</p>
                <p class="text-2xl font-bold text-zinc-900 dark:text-white tabular-nums mt-1">{{ $this->counts['orders_this_month'] }}</p>
            </div>
        </div>
    </flux:card>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ─── COVERAGE (left, 2 cols) ───────────────────────────────── --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Counties --}}
            <flux:card class="p-0">
                <div class="px-5 py-3 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                    <div>
                        <flux:heading size="sm">Counties</flux:heading>
                        <flux:subheading class="text-xs">Counties whose default zone is {{ $zone->name }}.</flux:subheading>
                    </div>
                    <flux:button size="xs" variant="ghost" icon="arrow-top-right-on-square"
                        :href="route('admin.logistics.configuration.locations.counties.index', ['filterZone' => $zone->id])"
                        wire:navigate>
                        Manage
                    </flux:button>
                </div>

                @if ($this->counties->isEmpty())
                    <div class="px-5 py-8 text-center text-sm text-zinc-400">
                        No counties default to this zone.
                    </div>
                @else
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($this->counties as $county)
                            <div class="flex items-center justify-between px-5 py-3">
                                <div>
                                    <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $county->name }}</span>
                                    @if ($county->code)
                                        <code class="ml-2 text-[11px] text-zinc-400">{{ $county->code }}</code>
                                    @endif
                                </div>
                                <span class="text-xs text-zinc-500">{{ $county->sub_counties_count }} sub-counties</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </flux:card>

            {{-- Sub-county overrides --}}
            <flux:card class="p-0">
                <div class="px-5 py-3 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                    <div>
                        <flux:heading size="sm">Sub-county overrides</flux:heading>
                        <flux:subheading class="text-xs">Sub-counties whose zone is explicitly {{ $zone->name }} (overriding their parent county).</flux:subheading>
                    </div>
                    <flux:button size="xs" variant="ghost" icon="arrow-top-right-on-square"
                        :href="route('admin.logistics.configuration.locations.sub-counties.index', ['filterZone' => 'overridden'])"
                        wire:navigate>
                        Manage
                    </flux:button>
                </div>

                @if ($this->subCountyOverrides->isEmpty())
                    <div class="px-5 py-8 text-center text-sm text-zinc-400">
                        No sub-counties override into this zone.
                    </div>
                @else
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($this->subCountyOverrides as $sc)
                            <div class="flex items-center justify-between px-5 py-3">
                                <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $sc->name }}</span>
                                <span class="text-xs text-zinc-500">{{ $sc->county?->name }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </flux:card>

            {{-- Town overrides --}}
            <flux:card class="p-0">
                <div class="px-5 py-3 border-b border-zinc-200 dark:border-zinc-700">
                    <flux:heading size="sm">Town (ADM3) overrides</flux:heading>
                    <flux:subheading class="text-xs">Ward-level overrides — most-specific tier in the resolution chain.</flux:subheading>
                </div>

                @if ($this->townOverrides->isEmpty())
                    <div class="px-5 py-8 text-center text-sm text-zinc-400">
                        No town-level overrides for this zone.
                    </div>
                @else
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($this->townOverrides as $town)
                            <div class="flex items-center justify-between px-5 py-3">
                                <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $town->name }}</span>
                                <span class="text-xs text-zinc-500">{{ $town->subCounty?->name }} · {{ $town->county?->name }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </flux:card>

        </div>

        {{-- ─── PRICING + ACTIVITY (right, 1 col) ─────────────────────── --}}
        <div class="space-y-6">

            {{-- Rates --}}
            <flux:card class="p-0">
                <div class="px-5 py-3 border-b border-zinc-200 dark:border-zinc-700">
                    <flux:heading size="sm">Rates</flux:heading>
                    <flux:subheading class="text-xs">Weight-bracket prices for each method in this zone.</flux:subheading>
                </div>

                @if ($this->rates->isEmpty())
                    <div class="px-5 py-8 text-center">
                        <p class="text-sm text-zinc-400 mb-1">No rates configured.</p>
                        <p class="text-xs text-zinc-400">Checkout will hide methods without rates in this zone.</p>
                    </div>
                @else
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800 text-sm">
                        @foreach ($this->rates->groupBy('shipping_method_id') as $methodId => $rows)
                            @php $method = $rows->first()->shippingMethod; @endphp
                            <div class="px-5 py-3">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $method?->name ?? 'Method '.$methodId }}</span>
                                    <flux:badge size="sm" variant="outline">{{ $method?->type ?? '—' }}</flux:badge>
                                </div>
                                <div class="space-y-1">
                                    @foreach ($rows as $rate)
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-zinc-500">{{ $rate->weight_label ?: $rate->min_weight.'–'.($rate->max_weight ?? '∞').' Kg' }}</span>
                                            <span class="font-semibold tabular-nums text-zinc-700 dark:text-zinc-300">
                                                {{ format_currency($rate->price) }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($this->methodsWithoutRates->isNotEmpty())
                    <div class="px-5 py-3 border-t border-zinc-200 dark:border-zinc-700 bg-amber-50 dark:bg-amber-950/30">
                        <p class="text-xs text-amber-700 dark:text-amber-400">
                            <strong>No rates set for:</strong>
                            {{ $this->methodsWithoutRates->pluck('name')->join(', ') }}
                        </p>
                    </div>
                @endif
            </flux:card>

            {{-- Free shipping rules --}}
            <flux:card class="p-0">
                <div class="px-5 py-3 border-b border-zinc-200 dark:border-zinc-700">
                    <flux:heading size="sm">Free shipping</flux:heading>
                </div>

                @if ($this->freeShippingRules->isEmpty())
                    <div class="px-5 py-6 text-center text-sm text-zinc-400">
                        No free-shipping rules for this zone.
                    </div>
                @else
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800 text-sm">
                        @foreach ($this->freeShippingRules as $rule)
                            <div class="px-5 py-3">
                                <p class="font-medium text-zinc-800 dark:text-zinc-200">{{ $rule->name }}</p>
                                <p class="text-xs text-zinc-500 mt-0.5">
                                    Min order: {{ format_currency($rule->min_order_amount) }}
                                    @if ($rule->shippingMethod)
                                        · {{ $rule->shippingMethod->name }}
                                    @endif
                                </p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </flux:card>

            {{-- Recent activity --}}
            <flux:card class="p-0">
                <div class="px-5 py-3 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                    <flux:heading size="sm">Recent orders</flux:heading>
                    <flux:button size="xs" variant="ghost" icon="arrow-top-right-on-square"
                        :href="route('admin.logistics.overview', ['filterZone' => $zone->id])" wire:navigate>
                        All
                    </flux:button>
                </div>

                @if ($this->recentOrders->isEmpty())
                    <div class="px-5 py-6 text-center text-sm text-zinc-400">
                        No delivery orders yet.
                    </div>
                @else
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800 text-sm">
                        @foreach ($this->recentOrders as $order)
                            <a href="{{ route('admin.logistics.delivery-orders.show', $order) }}" wire:navigate
                                class="flex items-center justify-between px-5 py-2.5 hover:bg-zinc-50 dark:hover:bg-zinc-800/60">
                                <div>
                                    <span class="font-semibold text-zinc-800 dark:text-zinc-200">#{{ $order->order_id }}</span>
                                    <p class="text-[11px] text-zinc-400 mt-0.5">{{ $order->shippingMethod?->name }} · {{ $order->created_at->diffForHumans() }}</p>
                                </div>
                                <span class="text-xs font-semibold tabular-nums">{{ format_currency($order->shipping_cost) }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </flux:card>

        </div>

    </div>

</div>
