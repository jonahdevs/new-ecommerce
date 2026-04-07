<?php

use App\Enums\ShippingRateStatus;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Livewire\Forms\Admin\FlatRateCellForm;
use App\Livewire\Forms\Admin\FlatRateTierForm;
use App\Settings\RegionalSettings;
use Livewire\Attributes\{Title, Computed, Url};
use Livewire\Component;
use Flux\Flux;

new #[Title('Flat Rates')] class extends Component {
    public FlatRateCellForm $cellForm;
    public FlatRateTierForm $tierForm;

    #[Url(history: true)]
    public string $selectedMethodId = '';

    public bool $showHistory = false;

    #[Computed]
    public function regionalSettings(): RegionalSettings
    {
        return app(RegionalSettings::class);
    }

    #[Computed]
    public function flatMethods()
    {
        return ShippingMethod::whereIn('type', ['flat', 'pus'])
            ->where('status', 'active')
            ->with('logisticsProvider')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function zones()
    {
        return ShippingZone::where('status', 'active')->orderBy('name')->get();
    }

    /**
     * Build the matrix data structure:
     * [
     *   'zones' => Collection<ShippingZone>,
     *   'rows'  => [
     *     [
     *       'key'          => '0-5',
     *       'min_weight'   => 0,
     *       'max_weight'   => 5,
     *       'weight_label' => 'Small (0–5 Kgs)',
     *       'cells'        => [ zone_id => ShippingRate|null, ... ],
     *       'history'      => [ zone_id => Collection<ShippingRate>, ... ],
     *     ],
     *     ...
     *   ]
     * ]
     */
    #[Computed]
    public function matrix(): array
    {
        if (!$this->selectedMethodId) {
            return [];
        }

        $zones = $this->zones;

        // Fetch all rates (active + expired) for the selected method
        $allRates = ShippingRate::where('shipping_method_id', $this->selectedMethodId)
            ->whereIn('status', [ShippingRateStatus::ACTIVE->value, ShippingRateStatus::EXPIRED->value])
            ->with(['shippingZone'])
            ->orderByDesc('created_at')
            ->get();

        $activeRates = $allRates->where('status', ShippingRateStatus::ACTIVE->value);
        $expiredRates = $allRates->where('status', ShippingRateStatus::EXPIRED->value);

        // Derive unique weight tiers from active rates, sorted by min_weight
        $tiers = $activeRates->unique(fn($r) => $r->min_weight . '-' . ($r->max_weight ?? 'max'))->sortBy('min_weight')->values();

        $rows = [];
        foreach ($tiers as $tier) {
            $tierKey = $tier->min_weight . '-' . ($tier->max_weight ?? 'max');

            $cells = [];
            $history = [];

            foreach ($zones as $zone) {
                // Active cell
                $cells[$zone->id] = $activeRates->first(fn($r) => $r->shipping_zone_id == $zone->id && (float) $r->min_weight === (float) $tier->min_weight && $r->max_weight == $tier->max_weight);

                // Historical (expired) rates for this cell, newest first
                $history[$zone->id] = $expiredRates->filter(fn($r) => $r->shipping_zone_id == $zone->id && (float) $r->min_weight === (float) $tier->min_weight && $r->max_weight == $tier->max_weight)->values();
            }

            $rows[] = [
                'key' => $tierKey,
                'min_weight' => $tier->min_weight,
                'max_weight' => $tier->max_weight,
                'weight_label' => $tier->weight_label,
                'days_min' => $tier->estimated_days_min,
                'days_max' => $tier->estimated_days_max,
                'cells' => $cells,
                'history' => $history,
            ];
        }

        return ['zones' => $zones, 'rows' => $rows];
    }

    //  Cell edit

    public function editCell(int $rateId): void
    {
        $rate = ShippingRate::findOrFail($rateId);
        $this->cellForm->setRate($rate);
        Flux::modal('cell-modal')->show();
    }

    public function saveCell(): void
    {
        try {
            $this->cellForm->update();
            Flux::modal('cell-modal')->close();
            $this->dispatch('notify', title: 'Rate Updated', variant: 'success', message: 'Rate updated. Previous rate archived.');
            unset($this->matrix);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            logger()->error('Failed to update flat rate cell.', [
                'exception' => $e->getMessage(),
                'rate_id' => $this->cellForm->rate?->id,
                'user_id' => auth()->id(),
            ]);
            $this->dispatch('notify', title: 'Update Failed', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }

    //  Add weight tier

    public function openAddTier(): void
    {
        $this->tierForm->reset();
        $this->tierForm->shipping_method_id = $this->selectedMethodId;

        // Pre-populate prices array keyed by zone_id so wire:model binds correctly
        $this->tierForm->prices = $this->zones->mapWithKeys(fn($z) => [$z->id => ''])->toArray();

        Flux::modal('tier-modal')->show();
    }

    public function saveTier(): void
    {
        try {
            $this->tierForm->store();
            Flux::modal('tier-modal')->close();
            $this->dispatch('notify', title: 'Tier Added', variant: 'success', message: 'Weight tier added.');
            unset($this->matrix);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            logger()->error('Failed to save flat rate tier.', [
                'exception' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
            $this->dispatch('notify', title: 'Save Failed', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }

    // Expire a rate directly

    public function expireRate(int $rateId): void
    {
        try {
            ShippingRate::findOrFail($rateId)->update(['status' => ShippingRateStatus::EXPIRED->value]);
            $this->dispatch('notify', title: 'Rate Expired', variant: 'warning', message: 'Rate expired.');
            unset($this->matrix);
        } catch (\Throwable $e) {
            $this->dispatch('notify', title: 'Expire Failed', variant: 'danger', message: 'Could not expire this rate.');
        }
    }
}; ?>

<x-admin.logistics.layout heading="Flat Rates"
    subheading="Weight-bracket pricing per shipping zone. Editing a rate archives the old one — history is always preserved.">

    <x-slot:actions>
        @if ($selectedMethodId)
            <flux:switch wire:model.live="showHistory" label="Show history" />
            <flux:button variant="primary" icon="plus" wire:click="openAddTier" class="cursor-pointer">
                Add Weight Tier
            </flux:button>
        @endif
    </x-slot:actions>

    <flux:card class="p-0">

        {{-- Filters --}}
        <div class="flex items-center gap-3 px-5 py-3 border-b dark:border-zinc-600 border-zinc-200">
            <flux:select wire:model.live="selectedMethodId" placeholder="Select a shipping method..." class="max-w-sm">
                @foreach ($this->flatMethods as $method)
                    <flux:select.option value="{{ $method->id }}">
                        {{ $method->name }}
                        <span class="text-zinc-400">({{ $method->logisticsProvider->name }})</span>
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>

        @if (!$selectedMethodId)
            <div class="py-16">
                <div class="flex flex-col items-center gap-3 text-zinc-400">
                    <flux:icon.table-cells class="w-10 h-10 opacity-40" />
                    <div class="text-center">
                        <flux:heading size="sm">Select a method to view its rate matrix</flux:heading>
                        <flux:subheading class="mt-0.5">Flat rates and PUS methods are shown above.</flux:subheading>
                    </div>
                </div>
            </div>
        @elseif (empty($this->matrix['rows']))
            <div class="py-16">
                <div class="flex flex-col items-center gap-3 text-zinc-400">
                    <flux:icon.table-cells class="w-10 h-10 opacity-40" />
                    <div class="text-center">
                        <flux:heading size="sm">No rates configured yet</flux:heading>
                        <flux:subheading class="mt-0.5">Add a weight tier to start building the rate matrix.</flux:subheading>
                    </div>
                    <flux:button variant="primary" size="sm" icon="plus" wire:click="openAddTier">
                        Add Weight Tier
                    </flux:button>
                </div>
            </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-800">
                        <th class="text-left font-medium text-zinc-500 px-4 py-3 w-48">Weight Tier</th>
                        <th class="text-left font-medium text-zinc-500 px-4 py-3 w-32">Delivery</th>
                        @foreach ($this->matrix['zones'] as $zone)
                            <th class="text-right font-medium text-zinc-500 px-4 py-3">{{ $zone->name }}</th>
                        @endforeach
                        <th class="w-10"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($this->matrix['rows'] as $row)
                        {{-- Active rate row --}}
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 group">
                            <td class="px-4 py-3">
                                <span
                                    class="font-medium">{{ $row['weight_label'] ?? "{$row['min_weight']} – " . ($row['max_weight'] ? "{$row['max_weight']} Kg" : 'No limit') }}</span>
                            </td>
                            <td class="px-4 py-3 text-zinc-500 text-xs">
                                @if ($row['days_min'] && $row['days_max'])
                                    {{ $row['days_min'] }}–{{ $row['days_max'] }}
                                @elseif ($row['days_min'])
                                    {{ $row['days_min'] }}+
                                @else
                                    —
                                @endif
                            </td>
                            @foreach ($this->matrix['zones'] as $zone)
                                @php $cell = $row['cells'][$zone->id] ?? null; @endphp
                                <td class="px-4 py-3 text-right">
                                    @if ($cell)
                                        <button wire:click="editCell({{ $cell->id }})"
                                            class="group/cell inline-flex items-center gap-1.5 font-semibold text-zinc-800 dark:text-zinc-100 hover:text-brand-secondary transition-colors cursor-pointer">
                                            {{ format_currency($cell->price) }}
                                            <flux:icon.pencil-square
                                                class="w-3.5 h-3.5 opacity-0 group-hover/cell:opacity-60 transition-opacity" />
                                        </button>
                                    @else
                                        <button wire:click="openAddTier"
                                            class="text-xs text-zinc-300 hover:text-zinc-500 cursor-pointer transition-colors">
                                            + Add
                                        </button>
                                    @endif
                                </td>
                            @endforeach
                            <td class="px-2 py-3">
                                {{-- placeholder for row actions if needed --}}
                            </td>
                        </tr>

                        {{-- History rows (collapsed by default) --}}
                        @if ($showHistory)
                            @php
                                $hasHistory = collect($row['history'])->flatten()->isNotEmpty();
                            @endphp
                            @if ($hasHistory)
                                {{-- Find max history depth across all zones for this tier --}}
                                @php
                                    $maxHistory = collect($row['history'])->max(fn($h) => $h->count());
                                @endphp
                                @for ($i = 0; $i < $maxHistory; $i++)
                                    <tr class="bg-zinc-50/60 dark:bg-zinc-900/40">
                                        <td class="px-4 py-2 text-xs text-zinc-400 italic pl-8">
                                            @if ($i === 0)
                                                History
                                            @endif
                                        </td>
                                        <td class="px-4 py-2"></td>
                                        @foreach ($this->matrix['zones'] as $zone)
                                            @php $histRate = $row['history'][$zone->id][$i] ?? null; @endphp
                                            <td class="px-4 py-2 text-right">
                                                @if ($histRate)
                                                    <span class="text-xs text-zinc-400 line-through">
                                                        {{ format_currency($histRate->price) }}
                                                    </span>
                                                    <span class="text-xs text-zinc-300 ml-1">
                                                        {{ $histRate->created_at->format('d M Y') }}
                                                    </span>
                                                @endif
                                            </td>
                                        @endforeach
                                        <td></td>
                                    </tr>
                                @endfor
                            @endif
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

    </flux:card>

    {{-- Cell edit modal --}}
    <flux:modal name="cell-modal" class="md:w-96 space-y-6">
        <div>
            <flux:heading size="lg">Edit Rate</flux:heading>
            @if ($cellForm->rate)
                <flux:subheading class="mt-1">
                    {{ $cellForm->rate->weight_label }}
                    &middot; {{ $cellForm->rate->shippingZone?->name }}
                </flux:subheading>
            @endif
        </div>

        <form wire:submit="saveCell" class="space-y-4">
            <flux:input wire:model="cellForm.price" label="Price ({{ get_currency_symbol() }})" type="number"
                min="0" step="0.01" placeholder="e.g. 800" />

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="cellForm.estimated_days_min" label="Min Delivery" type="number" min="1"
                    placeholder="1" />
                <flux:input wire:model="cellForm.estimated_days_max" label="Max Delivery" type="number" min="1"
                    placeholder="3" />
            </div>

            <flux:callout variant="warning" icon="archive-box">
                <flux:callout.heading>Rate versioning</flux:callout.heading>
                <flux:callout.text>
                    The current rate will be archived and a new one created. This preserves the history for all existing
                    orders.
                </flux:callout.text>
            </flux:callout>

            <div class="flex">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost" class="cursor-pointer">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" class="ml-2 cursor-pointer">Update Rate</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Add weight tier modal --}}
    <flux:modal name="tier-modal" class="md:w-lg space-y-6">
        <flux:heading size="lg">Add Weight Tier</flux:heading>

        <form wire:submit="saveTier" class="space-y-4">

            <div class="grid grid-cols-3 gap-4">
                <flux:input wire:model="tierForm.min_weight"
                    label="Min Weight ({{ $this->regionalSettings->weight_unit }})" type="number" min="0"
                    step="0.01" placeholder="0" />
                <flux:input wire:model="tierForm.max_weight"
                    label="Max Weight ({{ $this->regionalSettings->weight_unit }})" type="number" min="0"
                    step="0.01" placeholder="Leave blank for XL" />
                <flux:input wire:model="tierForm.weight_label" label="Label"
                    placeholder="e.g. Small (0–5 {{ $this->regionalSettings->weight_unit }})" class="col-span-3" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="tierForm.estimated_days_min" label="Min Delivery Time" type="number"
                    min="1" placeholder="1" />
                <flux:input wire:model="tierForm.estimated_days_max" label="Max Delivery Time" type="number"
                    min="1" placeholder="3" />
            </div>

            <div class="border-t border-zinc-100 dark:border-zinc-800 pt-4">
                <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-3">Prices per zone
                    ({{ get_currency_symbol() }})</p>
                <div class="space-y-3">
                    @foreach ($this->zones as $zone)
                        <flux:input wire:model="tierForm.prices.{{ $zone->id }}" :label="$zone->name"
                            type="number" min="0" step="0.01" placeholder="e.g. 800" />
                    @endforeach
                </div>
            </div>

            <div class="flex">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost" class="cursor-pointer">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" class="ml-2 cursor-pointer">Save Tier</flux:button>
            </div>
        </form>
    </flux:modal>

</x-admin.logistics.layout>
