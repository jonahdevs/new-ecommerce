<?php

use App\Models\ShippingRateAddon;
use Livewire\Component;

new class extends Component {
    public ShippingRateAddon $addon;

    public function mount(ShippingRateAddon $shippingRateAddon): void
    {
        $this->addon = $shippingRateAddon->load([
            'shippingRate.shippingZone',
            'shippingRate.shippingMethod',
            'pickupStation',
        ]);
    }

    public function rendering($view): void
    {
        $view->title('Surcharge — '.$this->addon->label ?? '#'.$this->addon->id);
    }
}; ?>

<div>
    <div class="mb-4">
        <flux:button :href="route('admin.logistics.pricing.surcharges.index')" wire:navigate
            variant="ghost" size="sm" icon="arrow-left">All Surcharges</flux:button>
    </div>

    <flux:card class="p-6 mb-6">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <div class="flex items-center gap-3 flex-wrap mb-1">
                    <flux:heading size="xl">{{ $addon->label ?? 'Surcharge #'.$addon->id }}</flux:heading>
                    <flux:badge size="sm" variant="outline">{{ $addon->addon_type }}</flux:badge>
                    <flux:badge size="sm" :color="$addon->status === 'active' ? 'green' : 'zinc'" variant="flat">{{ ucfirst($addon->status) }}</flux:badge>
                </div>
                <flux:subheading>
                    Applies on top of {{ $addon->shippingRate?->shippingMethod?->name ?? 'method' }} ·
                    {{ $addon->shippingRate?->shippingZone?->name ?? 'zone' }} ·
                    {{ $addon->shippingRate?->weight_label ?? 'tier' }}
                </flux:subheading>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700">
            <div>
                <p class="text-xs text-zinc-400 uppercase tracking-wide">Amount</p>
                <p class="text-2xl font-bold tabular-nums mt-1">{{ format_currency($addon->addon_amount) }}</p>
            </div>
            <div>
                <p class="text-xs text-zinc-400 uppercase tracking-wide">Parent rate</p>
                <p class="text-sm font-semibold mt-1 tabular-nums">{{ format_currency($addon->shippingRate?->price ?? 0) }}</p>
            </div>
            <div>
                <p class="text-xs text-zinc-400 uppercase tracking-wide">Effective total</p>
                <p class="text-sm font-bold tabular-nums mt-1">{{ format_currency(($addon->shippingRate?->price ?? 0) + $addon->addon_amount) }}</p>
            </div>
        </div>
    </flux:card>

    <flux:card class="p-5">
        <flux:heading size="sm" class="mb-2">Scope</flux:heading>
        <flux:text class="text-sm text-zinc-500">
            This surcharge stacks on top of the base rate
            (<strong>{{ format_currency($addon->shippingRate?->price ?? 0) }}</strong>)
            for {{ $addon->shippingRate?->shippingZone?->name ?? '—' }} · {{ $addon->shippingRate?->shippingMethod?->name ?? '—' }}
            · {{ $addon->shippingRate?->weight_label ?? '—' }}.
            @if ($addon->pickupStation)
                It applies <strong>only</strong> when the customer collects from {{ $addon->pickupStation->name }}.
            @else
                It applies to <strong>all stations</strong> for this rate.
            @endif
        </flux:text>
    </flux:card>
</div>
