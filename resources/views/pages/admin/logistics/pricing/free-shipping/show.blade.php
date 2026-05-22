<?php

use App\Enums\FreeShippingRuleStatus;
use App\Models\FreeShippingRule;
use Livewire\Component;

new class extends Component {
    public FreeShippingRule $rule;

    public function mount(FreeShippingRule $freeShippingRule): void
    {
        $this->rule = $freeShippingRule->load(['shippingZone', 'shippingMethod']);
    }

    public function rendering($view): void
    {
        $view->title($this->rule->name);
    }
}; ?>

<div>
    <div class="mb-4">
        <flux:button :href="route('admin.logistics.pricing.free-shipping.index')" wire:navigate
            variant="ghost" size="sm" icon="arrow-left">All Rules</flux:button>
    </div>

    @php
        $status = $rule->status instanceof FreeShippingRuleStatus
            ? $rule->status
            : FreeShippingRuleStatus::from($rule->status);
    @endphp

    <flux:card class="p-6 mb-6">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <div class="flex items-center gap-3 flex-wrap mb-1">
                    <flux:heading size="xl">{{ $rule->name }}</flux:heading>
                    <flux:badge size="sm" :color="$status->color()" variant="flat">{{ $status->label() }}</flux:badge>
                </div>
                <flux:subheading>
                    @if ($rule->shippingZone)
                        Zone: {{ $rule->shippingZone->name }}
                    @else
                        Applies to all zones
                    @endif
                    @if ($rule->shippingMethod)
                        · Method: {{ $rule->shippingMethod->name }}
                    @else
                        · All methods
                    @endif
                </flux:subheading>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700">
            <div>
                <p class="text-xs text-zinc-400 uppercase tracking-wide">Minimum order</p>
                <p class="text-2xl font-bold tabular-nums mt-1">{{ format_currency($rule->min_order_amount) }}</p>
            </div>
            <div>
                <p class="text-xs text-zinc-400 uppercase tracking-wide">Max weight</p>
                <p class="text-2xl font-bold tabular-nums mt-1">{{ $rule->max_weight ? $rule->max_weight.' kg' : '∞' }}</p>
            </div>
            <div>
                <p class="text-xs text-zinc-400 uppercase tracking-wide">Starts</p>
                <p class="text-sm font-medium mt-1">{{ $rule->starts_at?->format('d M Y') ?? 'Immediate' }}</p>
            </div>
            <div>
                <p class="text-xs text-zinc-400 uppercase tracking-wide">Ends</p>
                <p class="text-sm font-medium mt-1">{{ $rule->ends_at?->format('d M Y') ?? 'Never' }}</p>
            </div>
        </div>
    </flux:card>

    <flux:card class="p-5">
        <flux:heading size="sm" class="mb-2">How this rule fires</flux:heading>
        <flux:text class="text-sm text-zinc-500">
            At checkout, when a customer's cart meets the minimum order
            @if ($rule->max_weight)
                of {{ format_currency($rule->min_order_amount) }} <strong>and</strong> weight is under {{ $rule->max_weight }} kg,
            @else
                of {{ format_currency($rule->min_order_amount) }},
            @endif
            their shipping cost for
            @if ($rule->shippingMethod)
                <strong>{{ $rule->shippingMethod->name }}</strong>
            @else
                any method
            @endif
            in
            @if ($rule->shippingZone)
                <strong>{{ $rule->shippingZone->name }}</strong>
            @else
                any zone
            @endif
            is waived to KES 0. The base rate is still computed and displayed as a strike-through.
        </flux:text>
    </flux:card>
</div>
