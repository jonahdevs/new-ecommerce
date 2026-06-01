<?php

use App\Enums\DeliveryPromotionEffect;
use App\Enums\DeliveryPromotionScope;
use App\Models\DeliveryPromotion;
use App\Models\DeliveryZone;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Delivery zones — Admin')] class extends Component
{
    // ─── Zone form ─────────────────────────────────────────────────────────
    public bool $showZoneModal = false;

    public ?int $editingZoneId = null;

    public string $name = '';

    public string $county = 'Nairobi';

    public bool $is_active = true;

    public int $sort_order = 0;

    public int $priority = 0;

    public ?float $center_lat = null;

    public ?float $center_lng = null;

    public int $radius_meters = 5000;

    public float $base_fee = 0;

    public ?float $free_over = null;

    public string $eta_label = '';

    // ─── Promotion form ──────────────────────────────────────────────────────
    public bool $showPromoModal = false;

    public ?int $editingPromoId = null;

    public string $pName = '';

    public bool $pIsActive = true;

    public int $pPriority = 0;

    public string $pScope = 'global';

    public ?int $pZoneId = null;

    public string $pEffect = 'free';

    public ?float $pValue = null;

    public ?int $pPercent = null;

    public float $pMinSubtotal = 0;

    public ?string $pStartsAt = null;

    public ?string $pEndsAt = null;

    #[Computed]
    public function zones(): Collection
    {
        return DeliveryZone::query()
            ->withCount('promotions')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function promotions(): Collection
    {
        return DeliveryPromotion::query()
            ->with('zone')
            ->orderByDesc('priority')
            ->orderBy('name')
            ->get();
    }

    // ─── Zone actions ──────────────────────────────────────────────────────
    public function zoneRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'county' => ['required', 'string', 'max:100'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
            'priority' => ['integer', 'min:0'],
            'center_lat' => ['required', 'numeric', 'between:-90,90'],
            'center_lng' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'min:100', 'max:200000'],
            'base_fee' => ['required', 'numeric', 'min:0'],
            'free_over' => ['nullable', 'numeric', 'min:0'],
            'eta_label' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function openCreateZone(): void
    {
        $this->resetValidation();
        $this->reset(['editingZoneId', 'name', 'county', 'is_active', 'sort_order', 'priority', 'center_lat', 'center_lng', 'radius_meters', 'base_fee', 'free_over', 'eta_label']);
        $this->county = 'Nairobi';
        $this->is_active = true;
        $this->radius_meters = 5000;
        $this->showZoneModal = true;
    }

    public function openEditZone(int $id): void
    {
        $this->resetValidation();
        $zone = DeliveryZone::findOrFail($id);
        $this->editingZoneId = $zone->id;
        $this->name = $zone->name;
        $this->county = $zone->county;
        $this->is_active = $zone->is_active;
        $this->sort_order = $zone->sort_order;
        $this->priority = $zone->priority;
        $this->center_lat = $zone->center_lat;
        $this->center_lng = $zone->center_lng;
        $this->radius_meters = $zone->radius_meters;
        $this->base_fee = $zone->base_fee_cents / 100;
        $this->free_over = $zone->free_over_cents !== null ? $zone->free_over_cents / 100 : null;
        $this->eta_label = $zone->eta_label ?? '';
        $this->showZoneModal = true;
    }

    public function saveZone(): void
    {
        $data = $this->validate($this->zoneRules());

        $payload = [
            'name' => $data['name'],
            'county' => $data['county'],
            'is_active' => $data['is_active'],
            'sort_order' => $data['sort_order'],
            'priority' => $data['priority'],
            'center_lat' => $data['center_lat'],
            'center_lng' => $data['center_lng'],
            'radius_meters' => $data['radius_meters'],
            'base_fee_cents' => (int) round($data['base_fee'] * 100),
            'free_over_cents' => $data['free_over'] !== null ? (int) round($data['free_over'] * 100) : null,
            'eta_label' => $data['eta_label'] !== '' ? $data['eta_label'] : null,
        ];

        if ($this->editingZoneId) {
            DeliveryZone::findOrFail($this->editingZoneId)->update($payload);
            Flux::toast(heading: 'Zone updated', text: $payload['name'].' has been saved.', variant: 'success');
        } else {
            DeliveryZone::create($payload);
            Flux::toast(heading: 'Zone created', text: $payload['name'].' is now a delivery area.', variant: 'success');
        }

        $this->showZoneModal = false;
        unset($this->zones);
    }

    public function toggleZoneActive(int $id): void
    {
        $zone = DeliveryZone::findOrFail($id);
        $zone->update(['is_active' => ! $zone->is_active]);
        unset($this->zones);
    }

    public function deleteZone(int $id): void
    {
        DeliveryZone::findOrFail($id)->delete();
        unset($this->zones, $this->promotions);
        Flux::toast(heading: 'Zone removed', text: 'The delivery area has been deleted.', variant: 'warning');
    }

    // ─── Promotion actions ─────────────────────────────────────────────────
    public function promoRules(): array
    {
        return [
            'pName' => ['required', 'string', 'max:100'],
            'pIsActive' => ['boolean'],
            'pPriority' => ['integer', 'min:0'],
            'pScope' => ['required', 'in:global,zone'],
            'pZoneId' => ['nullable', 'required_if:pScope,zone', 'exists:delivery_zones,id'],
            'pEffect' => ['required', 'in:free,flat_fee,percent_off'],
            'pValue' => ['nullable', 'required_if:pEffect,flat_fee', 'numeric', 'min:0'],
            'pPercent' => ['nullable', 'required_if:pEffect,percent_off', 'integer', 'between:1,100'],
            'pMinSubtotal' => ['numeric', 'min:0'],
            'pStartsAt' => ['nullable', 'date'],
            'pEndsAt' => ['nullable', 'date', 'after_or_equal:pStartsAt'],
        ];
    }

    public function openCreatePromo(): void
    {
        $this->resetValidation();
        $this->reset(['editingPromoId', 'pName', 'pIsActive', 'pPriority', 'pScope', 'pZoneId', 'pEffect', 'pValue', 'pPercent', 'pMinSubtotal', 'pStartsAt', 'pEndsAt']);
        $this->pIsActive = true;
        $this->pScope = 'global';
        $this->pEffect = 'free';
        $this->showPromoModal = true;
    }

    public function openEditPromo(int $id): void
    {
        $this->resetValidation();
        $promo = DeliveryPromotion::findOrFail($id);
        $this->editingPromoId = $promo->id;
        $this->pName = $promo->name;
        $this->pIsActive = $promo->is_active;
        $this->pPriority = $promo->priority;
        $this->pScope = $promo->scope->value;
        $this->pZoneId = $promo->zone_id;
        $this->pEffect = $promo->effect->value;
        $this->pValue = $promo->value_cents !== null ? $promo->value_cents / 100 : null;
        $this->pPercent = $promo->percent;
        $this->pMinSubtotal = $promo->min_subtotal_cents / 100;
        $this->pStartsAt = $promo->starts_at?->format('Y-m-d\TH:i');
        $this->pEndsAt = $promo->ends_at?->format('Y-m-d\TH:i');
        $this->showPromoModal = true;
    }

    public function savePromo(): void
    {
        $data = $this->validate($this->promoRules());

        $payload = [
            'name' => $data['pName'],
            'is_active' => $data['pIsActive'],
            'priority' => $data['pPriority'],
            'scope' => $data['pScope'],
            'zone_id' => $data['pScope'] === 'zone' ? $data['pZoneId'] : null,
            'effect' => $data['pEffect'],
            'value_cents' => $data['pEffect'] === 'flat_fee' && $data['pValue'] !== null ? (int) round($data['pValue'] * 100) : null,
            'percent' => $data['pEffect'] === 'percent_off' ? $data['pPercent'] : null,
            'min_subtotal_cents' => (int) round($data['pMinSubtotal'] * 100),
            'starts_at' => $data['pStartsAt'] ?: null,
            'ends_at' => $data['pEndsAt'] ?: null,
        ];

        if ($this->editingPromoId) {
            DeliveryPromotion::findOrFail($this->editingPromoId)->update($payload);
            Flux::toast(heading: 'Promotion updated', text: $payload['name'].' has been saved.', variant: 'success');
        } else {
            DeliveryPromotion::create($payload);
            Flux::toast(heading: 'Promotion created', text: $payload['name'].' is now live.', variant: 'success');
        }

        $this->showPromoModal = false;
        unset($this->promotions);
    }

    public function togglePromoActive(int $id): void
    {
        $promo = DeliveryPromotion::findOrFail($id);
        $promo->update(['is_active' => ! $promo->is_active]);
        unset($this->promotions);
    }

    public function deletePromo(int $id): void
    {
        DeliveryPromotion::findOrFail($id)->delete();
        unset($this->promotions);
        Flux::toast(heading: 'Promotion removed', text: 'The promotion has been deleted.', variant: 'warning');
    }
}; ?>

@include('partials.admin.zone-map-scripts')

<div class="space-y-10" x-data="zoneMap()"
     x-effect="$wire.showZoneModal ? open() : close()">

    {{-- Page breadcrumbs --}}
    @push('breadcrumbs')
<flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Delivery zones</flux:breadcrumbs.item>
    </flux:breadcrumbs>
@endpush

    {{-- ── Zones ── --}}
    <section>
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">Delivery zones</flux:heading>
                <flux:text class="mt-1">Circular areas you deliver to. A customer's map pin must fall inside an active zone.</flux:text>
            </div>
            <flux:button variant="primary" icon="plus" wire:click="openCreateZone">Add zone</flux:button>
        </div>

        <div class="mt-6 overflow-hidden rounded-md border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 text-left text-[11px] font-bold tracking-wide text-zinc-500 uppercase dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-3">Zone</th>
                        <th class="px-4 py-3">County</th>
                        <th class="px-4 py-3">Radius</th>
                        <th class="px-4 py-3">Base fee</th>
                        <th class="px-4 py-3">Free over</th>
                        <th class="px-4 py-3">Priority</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($this->zones as $zone)
                        <tr wire:key="zone-{{ $zone->id }}">
                            <td class="px-4 py-3 font-medium">
                                {{ $zone->name }}
                                @if ($zone->eta_label)
                                    <span class="block text-[11px] text-zinc-400">{{ $zone->eta_label }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-zinc-500">{{ $zone->county }}</td>
                            <td class="px-4 py-3 tabular-nums text-zinc-500">{{ number_format($zone->radius_meters / 1000, 1) }} km</td>
                            <td class="px-4 py-3 tabular-nums">{{ money($zone->base_fee_cents) }}</td>
                            <td class="px-4 py-3 tabular-nums text-zinc-500">{{ $zone->free_over_cents !== null ? money($zone->free_over_cents) : '—' }}</td>
                            <td class="px-4 py-3 tabular-nums text-zinc-500">{{ $zone->priority }}</td>
                            <td class="px-4 py-3">
                                <button type="button" wire:click="toggleZoneActive({{ $zone->id }})">
                                    <flux:badge :color="$zone->is_active ? 'green' : 'zinc'" size="sm">
                                        {{ $zone->is_active ? 'Active' : 'Off' }}
                                    </flux:badge>
                                </button>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <flux:button size="xs" variant="ghost" icon="pencil-square" wire:click="openEditZone({{ $zone->id }})" />
                                    <flux:button size="xs" variant="ghost" icon="trash"
                                                 wire:click="deleteZone({{ $zone->id }})"
                                                 wire:confirm="Delete {{ $zone->name }}? Addresses in this zone will lose their assignment."
                                                 class="text-red-500! hover:text-red-600!" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-zinc-400">No delivery zones yet. Add your first area.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- ── Promotions ── --}}
    <section>
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">Delivery promotions</flux:heading>
                <flux:text class="mt-1">Overrides applied on top of zone fees. The launch free-delivery offer lives here.</flux:text>
            </div>
            <flux:button variant="primary" icon="plus" wire:click="openCreatePromo">Add promotion</flux:button>
        </div>

        <div class="mt-6 overflow-hidden rounded-md border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 text-left text-[11px] font-bold tracking-wide text-zinc-500 uppercase dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-3">Promotion</th>
                        <th class="px-4 py-3">Applies to</th>
                        <th class="px-4 py-3">Effect</th>
                        <th class="px-4 py-3">Min order</th>
                        <th class="px-4 py-3">Window</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($this->promotions as $promo)
                        <tr wire:key="promo-{{ $promo->id }}">
                            <td class="px-4 py-3 font-medium">{{ $promo->name }}</td>
                            <td class="px-4 py-3 text-zinc-500">{{ $promo->scope->label() }}{{ $promo->zone ? ': '.$promo->zone->name : '' }}</td>
                            <td class="px-4 py-3 text-zinc-500">
                                {{ $promo->effect->label() }}@if ($promo->effect === DeliveryPromotionEffect::FLAT_FEE) ({{ money($promo->value_cents) }})@elseif ($promo->effect === DeliveryPromotionEffect::PERCENT_OFF) ({{ $promo->percent }}%)@endif
                            </td>
                            <td class="px-4 py-3 tabular-nums text-zinc-500">{{ $promo->min_subtotal_cents > 0 ? money($promo->min_subtotal_cents) : '—' }}</td>
                            <td class="px-4 py-3 text-[12px] text-zinc-500">
                                {{ $promo->starts_at?->format('d M Y') ?? 'now' }} – {{ $promo->ends_at?->format('d M Y') ?? 'open' }}
                            </td>
                            <td class="px-4 py-3">
                                <button type="button" wire:click="togglePromoActive({{ $promo->id }})">
                                    <flux:badge :color="$promo->isLiveNow() ? 'green' : 'zinc'" size="sm">
                                        {{ $promo->isLiveNow() ? 'Live' : ($promo->is_active ? 'Scheduled' : 'Off') }}
                                    </flux:badge>
                                </button>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <flux:button size="xs" variant="ghost" icon="pencil-square" wire:click="openEditPromo({{ $promo->id }})" />
                                    <flux:button size="xs" variant="ghost" icon="trash"
                                                 wire:click="deletePromo({{ $promo->id }})"
                                                 wire:confirm="Delete the {{ $promo->name }} promotion?"
                                                 class="text-red-500! hover:text-red-600!" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-zinc-400">No promotions. Add a global "free delivery" promo for launch.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- ── Zone modal ── --}}
    <flux:modal wire:model.self="showZoneModal" class="md:w-[640px]" :dismissible="false">
        <flux:heading>{{ $editingZoneId ? 'Edit zone' : 'New delivery zone' }}</flux:heading>
        <flux:subheading>Click the map to set the centre, then set the radius. Drag the pin to fine-tune.</flux:subheading>

        <form wire:submit="saveZone" class="mt-6 space-y-4">
            <div x-ref="zoneMapContainer" class="h-64 w-full overflow-hidden rounded-md border border-zinc-200 bg-zinc-100 dark:border-zinc-700"></div>
            @error('center_lat') <flux:error>Drop a pin on the map to set the zone centre.</flux:error> @enderror

            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Name</flux:label>
                    <flux:input wire:model="name" placeholder="Westlands" />
                    <flux:error name="name" />
                </flux:field>
                <flux:field>
                    <flux:label>County</flux:label>
                    <flux:input wire:model="county" placeholder="Nairobi" />
                    <flux:error name="county" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>Radius (metres)</flux:label>
                <flux:input type="number" wire:model.live="radius_meters" min="100" max="200000" step="100" />
                <flux:description>Circle covering the area. e.g. 5000 = 5 km.</flux:description>
                <flux:error name="radius_meters" />
            </flux:field>

            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Base fee (KES)</flux:label>
                    <flux:input type="number" wire:model="base_fee" min="0" step="1" />
                    <flux:description>The real charge once promos end.</flux:description>
                    <flux:error name="base_fee" />
                </flux:field>
                <flux:field>
                    <flux:label>Free over (KES)</flux:label>
                    <flux:input type="number" wire:model="free_over" min="0" step="1" placeholder="Optional" />
                    <flux:description>Free if the order subtotal exceeds this.</flux:description>
                    <flux:error name="free_over" />
                </flux:field>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <flux:field>
                    <flux:label>ETA label</flux:label>
                    <flux:input wire:model="eta_label" placeholder="Same day" />
                    <flux:error name="eta_label" />
                </flux:field>
                <flux:field>
                    <flux:label>Priority</flux:label>
                    <flux:input type="number" wire:model="priority" min="0" />
                    <flux:description>Higher wins when zones overlap.</flux:description>
                    <flux:error name="priority" />
                </flux:field>
                <flux:field>
                    <flux:label>Sort order</flux:label>
                    <flux:input type="number" wire:model="sort_order" min="0" />
                    <flux:error name="sort_order" />
                </flux:field>
            </div>

            <flux:checkbox wire:model="is_active" label="Active (available at checkout)" />

            <div class="flex justify-end gap-3 pt-2">
                <flux:button type="button" variant="ghost" x-on:click="$flux.modals().close()">Cancel</flux:button>
                <flux:button type="submit" variant="primary">{{ $editingZoneId ? 'Save zone' : 'Create zone' }}</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- ── Promotion modal ── --}}
    <flux:modal wire:model.self="showPromoModal" class="md:w-[560px]" :dismissible="false">
        <flux:heading>{{ $editingPromoId ? 'Edit promotion' : 'New promotion' }}</flux:heading>
        <flux:subheading>Layer a discount or free delivery on top of zone fees.</flux:subheading>

        <form wire:submit="savePromo" class="mt-6 space-y-4">
            <flux:field>
                <flux:label>Name</flux:label>
                <flux:input wire:model="pName" placeholder="Launch free delivery" />
                <flux:error name="pName" />
            </flux:field>

            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Applies to</flux:label>
                    <flux:select wire:model.live="pScope">
                        <flux:select.option value="global">All zones</flux:select.option>
                        <flux:select.option value="zone">Specific zone</flux:select.option>
                    </flux:select>
                    <flux:error name="pScope" />
                </flux:field>
                <flux:field x-show="$wire.pScope === 'zone'">
                    <flux:label>Zone</flux:label>
                    <flux:select wire:model="pZoneId" placeholder="Choose a zone…">
                        @foreach ($this->zones as $zone)
                            <flux:select.option :value="$zone->id">{{ $zone->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="pZoneId" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>Effect</flux:label>
                <flux:select wire:model.live="pEffect">
                    <flux:select.option value="free">Free delivery</flux:select.option>
                    <flux:select.option value="flat_fee">Flat fee</flux:select.option>
                    <flux:select.option value="percent_off">Percent off</flux:select.option>
                </flux:select>
                <flux:error name="pEffect" />
            </flux:field>

            <flux:field x-show="$wire.pEffect === 'flat_fee'">
                <flux:label>Flat fee (KES)</flux:label>
                <flux:input type="number" wire:model="pValue" min="0" step="1" />
                <flux:error name="pValue" />
            </flux:field>

            <flux:field x-show="$wire.pEffect === 'percent_off'">
                <flux:label>Percent off</flux:label>
                <flux:input type="number" wire:model="pPercent" min="1" max="100" />
                <flux:error name="pPercent" />
            </flux:field>

            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Min order (KES)</flux:label>
                    <flux:input type="number" wire:model="pMinSubtotal" min="0" step="1" />
                    <flux:error name="pMinSubtotal" />
                </flux:field>
                <flux:field>
                    <flux:label>Priority</flux:label>
                    <flux:input type="number" wire:model="pPriority" min="0" />
                    <flux:error name="pPriority" />
                </flux:field>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Starts at</flux:label>
                    <flux:input type="datetime-local" wire:model="pStartsAt" />
                    <flux:error name="pStartsAt" />
                </flux:field>
                <flux:field>
                    <flux:label>Ends at</flux:label>
                    <flux:input type="datetime-local" wire:model="pEndsAt" />
                    <flux:error name="pEndsAt" />
                </flux:field>
            </div>

            <flux:checkbox wire:model="pIsActive" label="Active" />

            <div class="flex justify-end gap-3 pt-2">
                <flux:button type="button" variant="ghost" x-on:click="$flux.modals().close()">Cancel</flux:button>
                <flux:button type="submit" variant="primary">{{ $editingPromoId ? 'Save promotion' : 'Create promotion' }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
