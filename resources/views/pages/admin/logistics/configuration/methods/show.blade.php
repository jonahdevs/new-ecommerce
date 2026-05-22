<?php

use App\Models\DeliveryOrder;
use App\Models\FreeShippingRule;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public ShippingMethod $method;

    public function mount(ShippingMethod $shippingMethod): void
    {
        $this->method = $shippingMethod->load('logisticsProvider');
    }

    public function rendering($view): void
    {
        $view->title($this->method->name);
    }

    #[Computed]
    public function ratesByZone()
    {
        return ShippingRate::with('shippingZone')
            ->where('shipping_method_id', $this->method->id)
            ->where('status', 'active')
            ->get()
            ->groupBy('shipping_zone_id');
    }

    #[Computed]
    public function freeRules()
    {
        return FreeShippingRule::with('shippingZone')
            ->where('shipping_method_id', $this->method->id)
            ->get();
    }

    #[Computed]
    public function recentOrders()
    {
        return DeliveryOrder::with('shippingZone')
            ->where('shipping_method_id', $this->method->id)
            ->latest()
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function counts(): array
    {
        return [
            'rate_rows' => ShippingRate::where('shipping_method_id', $this->method->id)->where('status', 'active')->count(),
            'orders_mtd' => DeliveryOrder::where('shipping_method_id', $this->method->id)->where('created_at', '>=', now()->startOfMonth())->count(),
        ];
    }
}; ?>

<div>
    <div class="mb-4">
        <flux:button :href="route('admin.logistics.configuration.methods.index')" wire:navigate
            variant="ghost" size="sm" icon="arrow-left">All Methods</flux:button>
    </div>

    <flux:card class="p-6 mb-6">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <div class="flex items-center gap-3 mb-1 flex-wrap">
                    <flux:heading size="xl">{{ $method->name }}</flux:heading>
                    <flux:badge size="sm" variant="outline">{{ $method->type }}</flux:badge>
                    <flux:badge size="sm" :color="$method->status === 'active' ? 'green' : 'zinc'" variant="flat">{{ ucfirst($method->status) }}</flux:badge>
                </div>
                <flux:subheading>{{ $method->logisticsProvider?->name ?? '—' }} · code: <code class="text-xs">{{ $method->code }}</code></flux:subheading>
                @if ($method->description)
                    <p class="text-sm text-zinc-500 mt-2 max-w-2xl">{{ $method->description }}</p>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700">
            <div>
                <p class="text-xs text-zinc-400 uppercase tracking-wide">Rate rows (active)</p>
                <p class="text-2xl font-bold tabular-nums mt-1">{{ $this->counts['rate_rows'] }}</p>
            </div>
            <div>
                <p class="text-xs text-zinc-400 uppercase tracking-wide">Orders (MTD)</p>
                <p class="text-2xl font-bold tabular-nums mt-1">{{ $this->counts['orders_mtd'] }}</p>
            </div>
            <div>
                <p class="text-xs text-zinc-400 uppercase tracking-wide">Delivery unit</p>
                <p class="text-sm font-medium mt-1 capitalize">{{ $method->delivery_time_unit }}</p>
            </div>
            <div>
                <p class="text-xs text-zinc-400 uppercase tracking-wide">Supports returns</p>
                <p class="text-sm font-medium mt-1">{{ $method->supports_returns ? 'Yes' : 'No' }}</p>
            </div>
        </div>
    </flux:card>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <flux:card class="p-0">
            <div class="px-5 py-3 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                <flux:heading size="sm">Rates by zone</flux:heading>
                <flux:button size="xs" variant="ghost" icon="arrow-top-right-on-square"
                    :href="route('admin.logistics.pricing.matrix', ['selectedMethodId' => $method->id])" wire:navigate>
                    Open matrix
                </flux:button>
            </div>
            @if ($this->ratesByZone->isEmpty())
                <div class="px-5 py-8 text-center text-sm text-zinc-400">No rates configured for this method.</div>
            @else
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($this->ratesByZone as $zoneId => $rates)
                        @php $zone = $rates->first()->shippingZone; @endphp
                        <div class="px-5 py-3">
                            <div class="flex items-center justify-between mb-2">
                                <a href="{{ route('admin.logistics.configuration.zones.show', $zoneId) }}" wire:navigate
                                    class="font-medium text-zinc-800 dark:text-zinc-200 hover:underline">{{ $zone?->name }}</a>
                                <span class="text-xs text-zinc-400">{{ $rates->count() }} tier{{ $rates->count() === 1 ? '' : 's' }}</span>
                            </div>
                            <div class="space-y-0.5">
                                @foreach ($rates->sortBy('min_weight') as $rate)
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-zinc-500">{{ $rate->weight_label }}</span>
                                        <span class="font-semibold tabular-nums">{{ format_currency($rate->price) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </flux:card>

        <div class="space-y-6">
            <flux:card class="p-0">
                <div class="px-5 py-3 border-b border-zinc-200 dark:border-zinc-700">
                    <flux:heading size="sm">Free-shipping rules</flux:heading>
                </div>
                @if ($this->freeRules->isEmpty())
                    <div class="px-5 py-6 text-center text-sm text-zinc-400">No free-shipping rules for this method.</div>
                @else
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800 text-sm">
                        @foreach ($this->freeRules as $rule)
                            <div class="px-5 py-3">
                                <p class="font-medium">{{ $rule->name }}</p>
                                <p class="text-xs text-zinc-500 mt-0.5">
                                    Min order: {{ format_currency($rule->min_order_amount) }}
                                    @if ($rule->shippingZone)
                                        · {{ $rule->shippingZone->name }}
                                    @endif
                                </p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </flux:card>

            <flux:card class="p-0">
                <div class="px-5 py-3 border-b border-zinc-200 dark:border-zinc-700">
                    <flux:heading size="sm">Recent orders</flux:heading>
                </div>
                @if ($this->recentOrders->isEmpty())
                    <div class="px-5 py-6 text-center text-sm text-zinc-400">No orders yet.</div>
                @else
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800 text-sm">
                        @foreach ($this->recentOrders as $order)
                            <a href="{{ route('admin.logistics.delivery-orders.show', $order) }}" wire:navigate
                                class="flex items-center justify-between px-5 py-2.5 hover:bg-zinc-50 dark:hover:bg-zinc-800/60">
                                <div>
                                    <span class="font-semibold">#{{ $order->order_id }}</span>
                                    <p class="text-[11px] text-zinc-400 mt-0.5">{{ $order->shippingZone?->name }} · {{ $order->created_at->diffForHumans() }}</p>
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
