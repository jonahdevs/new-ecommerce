<?php

use App\Models\DeliveryOrder;
use App\Models\LogisticsProvider;
use App\Models\PickupStation;
use App\Models\ShippingMethod;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public LogisticsProvider $provider;

    public function mount(LogisticsProvider $logisticsProvider): void
    {
        $this->provider = $logisticsProvider;
    }

    public function rendering($view): void
    {
        $view->title($this->provider->name);
    }

    #[Computed]
    public function methods()
    {
        return ShippingMethod::where('logistics_provider_id', $this->provider->id)->orderBy('sort_order')->get();
    }

    #[Computed]
    public function stations()
    {
        return PickupStation::where('logistics_provider_id', $this->provider->id)->orderBy('name')->get();
    }

    #[Computed]
    public function recentOrders()
    {
        return DeliveryOrder::with('shippingMethod')
            ->where('logistics_provider_id', $this->provider->id)
            ->latest()
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function counts(): array
    {
        return [
            'methods' => ShippingMethod::where('logistics_provider_id', $this->provider->id)->count(),
            'stations' => PickupStation::where('logistics_provider_id', $this->provider->id)->count(),
            'orders_mtd' => DeliveryOrder::where('logistics_provider_id', $this->provider->id)->where('created_at', '>=', now()->startOfMonth())->count(),
        ];
    }
}; ?>

<div>
    <div class="mb-4">
        <flux:button :href="route('admin.logistics.configuration.providers.index')" wire:navigate
            variant="ghost" size="sm" icon="arrow-left">All Providers</flux:button>
    </div>

    <flux:card class="p-6 mb-6">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <div class="flex items-center gap-3 flex-wrap mb-1">
                    <flux:heading size="xl">{{ $provider->name }}</flux:heading>
                    <flux:badge size="sm" variant="outline">{{ $provider->type }}</flux:badge>
                    <flux:badge size="sm" :color="$provider->status === 'active' ? 'green' : 'zinc'" variant="flat">{{ ucfirst($provider->status) }}</flux:badge>
                </div>
                <code class="text-xs text-zinc-500">{{ $provider->code }}</code>
                @if ($provider->description)
                    <p class="text-sm text-zinc-500 mt-2 max-w-2xl">{{ $provider->description }}</p>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-3 gap-4 mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700">
            <div>
                <p class="text-xs text-zinc-400 uppercase tracking-wide">Methods</p>
                <p class="text-2xl font-bold tabular-nums mt-1">{{ $this->counts['methods'] }}</p>
            </div>
            <div>
                <p class="text-xs text-zinc-400 uppercase tracking-wide">Pickup stations</p>
                <p class="text-2xl font-bold tabular-nums mt-1">{{ $this->counts['stations'] }}</p>
            </div>
            <div>
                <p class="text-xs text-zinc-400 uppercase tracking-wide">Orders (MTD)</p>
                <p class="text-2xl font-bold tabular-nums mt-1">{{ $this->counts['orders_mtd'] }}</p>
            </div>
        </div>
    </flux:card>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <flux:card class="p-0">
            <div class="px-5 py-3 border-b border-zinc-200 dark:border-zinc-700">
                <flux:heading size="sm">Methods operated</flux:heading>
            </div>
            @if ($this->methods->isEmpty())
                <div class="px-5 py-6 text-center text-sm text-zinc-400">No methods.</div>
            @else
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($this->methods as $method)
                        <a href="{{ route('admin.logistics.configuration.methods.show', $method) }}" wire:navigate
                            class="flex items-center justify-between px-5 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-800/60">
                            <div>
                                <span class="font-medium">{{ $method->name }}</span>
                                <p class="text-xs text-zinc-500 mt-0.5">{{ $method->type }} · {{ $method->code }}</p>
                            </div>
                            <flux:badge size="sm" :color="$method->status === 'active' ? 'green' : 'zinc'" variant="flat">{{ ucfirst($method->status) }}</flux:badge>
                        </a>
                    @endforeach
                </div>
            @endif
        </flux:card>

        <flux:card class="p-0">
            <div class="px-5 py-3 border-b border-zinc-200 dark:border-zinc-700">
                <flux:heading size="sm">Pickup stations</flux:heading>
            </div>
            @if ($this->stations->isEmpty())
                <div class="px-5 py-6 text-center text-sm text-zinc-400">No stations.</div>
            @else
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($this->stations as $station)
                        <a href="{{ route('admin.logistics.configuration.pickup-stations.show', $station) }}" wire:navigate
                            class="flex items-center justify-between px-5 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-800/60">
                            <div>
                                <span class="font-medium">{{ $station->name }}</span>
                                <p class="text-xs text-zinc-500 mt-0.5">{{ $station->address }}</p>
                            </div>
                            @if ($station->is_primary)
                                <flux:badge size="sm" color="blue" variant="flat">Primary</flux:badge>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
        </flux:card>
    </div>

    <flux:card class="p-0 mt-6">
        <div class="px-5 py-3 border-b border-zinc-200 dark:border-zinc-700">
            <flux:heading size="sm">Recent orders</flux:heading>
        </div>
        @if ($this->recentOrders->isEmpty())
            <div class="px-5 py-6 text-center text-sm text-zinc-400">No orders.</div>
        @else
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800 text-sm">
                @foreach ($this->recentOrders as $order)
                    <a href="{{ route('admin.logistics.delivery-orders.show', $order) }}" wire:navigate
                        class="flex items-center justify-between px-5 py-2.5 hover:bg-zinc-50 dark:hover:bg-zinc-800/60">
                        <div>
                            <span class="font-semibold">#{{ $order->order_id }}</span>
                            <p class="text-[11px] text-zinc-400 mt-0.5">{{ $order->shippingMethod?->name }} · {{ $order->created_at->diffForHumans() }}</p>
                        </div>
                        <span class="text-xs font-semibold tabular-nums">{{ format_currency($order->shipping_cost) }}</span>
                    </a>
                @endforeach
            </div>
        @endif
    </flux:card>
</div>
