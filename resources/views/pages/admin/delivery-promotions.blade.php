<?php

use App\Models\DeliveryPromotion;
use App\Models\DeliveryZone;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Delivery promotions — Admin')] class extends Component
{
    // ==================================================
    // SEARCH & FILTER
    // ==================================================
    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $filterScope = '';

    #[Url]
    public string $filterEffect = '';

    #[Url]
    public string $filterStatus = '';

    // ==================================================
    // BULK SELECTION
    // ==================================================
    /** @var array<int, string> */
    public array $selected = [];

    public bool $selectAll = false;

    // ==================================================
    // PROMOTION FORM
    // ==================================================
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
    public function promotions(): Collection
    {
        return DeliveryPromotion::query()
            ->with('zone')
            ->when($this->search, fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->when($this->filterScope, fn ($q) => $q->where('scope', $this->filterScope))
            ->when($this->filterEffect, fn ($q) => $q->where('effect', $this->filterEffect))
            ->when($this->filterStatus !== '', fn ($q) => $q->where('is_active', $this->filterStatus === 'active'))
            ->orderByDesc('priority')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function zones(): Collection
    {
        return DeliveryZone::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function updatedSearch(): void
    {
        $this->clearSelection();
    }

    public function updatedFilterScope(): void
    {
        $this->clearSelection();
    }

    public function updatedFilterEffect(): void
    {
        $this->clearSelection();
    }

    public function updatedFilterStatus(): void
    {
        $this->clearSelection();
    }

    public function updatedSelectAll(bool $value): void
    {
        $this->selected = $value
            ? $this->promotions->pluck('id')->map(fn ($id) => (string) $id)->all()
            : [];
    }

    public function clearSelection(): void
    {
        $this->selected = [];
        $this->selectAll = false;
    }

    // ==================================================
    // PROMOTION CRUD
    // ==================================================
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

    // ==================================================
    // BULK ACTIONS
    // ==================================================
    public function bulkActivate(): void
    {
        if ($this->selected === []) {
            return;
        }

        $count = DeliveryPromotion::whereIn('id', $this->selected)->update(['is_active' => true]);
        $this->afterBulk();
        Flux::toast(heading: 'Promotions activated', text: $count.' promotion(s) set to active.', variant: 'success');
    }

    public function bulkDeactivate(): void
    {
        if ($this->selected === []) {
            return;
        }

        $count = DeliveryPromotion::whereIn('id', $this->selected)->update(['is_active' => false]);
        $this->afterBulk();
        Flux::toast(heading: 'Promotions deactivated', text: $count.' promotion(s) turned off.', variant: 'success');
    }

    public function bulkDelete(): void
    {
        if ($this->selected === []) {
            return;
        }

        $count = DeliveryPromotion::whereIn('id', $this->selected)->delete();
        $this->afterBulk();
        Flux::toast(heading: 'Promotions deleted', text: $count.' promotion(s) have been removed.', variant: 'warning');
    }

    private function afterBulk(): void
    {
        $this->clearSelection();
        unset($this->promotions);
    }
}; ?>

<div>
    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Delivery promotions</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Delivery promotions</flux:heading>
            <flux:text class="mt-1">Overrides applied on top of zone fees — e.g. a launch free-delivery offer.</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="openCreatePromo">Add promotion</flux:button>
    </div>

    <flux:card class="mt-6 p-0 overflow-hidden">

        {{-- Toolbar --}}
        <div class="flex flex-col gap-3 border-b border-zinc-200 px-6 py-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4 dark:border-zinc-700">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search promotions…"
                icon="magnifying-glass" clearable class="sm:max-w-xs" />
            <div class="flex flex-wrap items-center gap-2">
                <flux:select wire:model.live="filterScope" class="w-36">
                    <flux:select.option value="">All scopes</flux:select.option>
                    <flux:select.option value="global">Global</flux:select.option>
                    <flux:select.option value="zone">Zone</flux:select.option>
                </flux:select>
                <flux:select wire:model.live="filterEffect" class="w-40">
                    <flux:select.option value="">All effects</flux:select.option>
                    <flux:select.option value="free">Free delivery</flux:select.option>
                    <flux:select.option value="flat_fee">Flat fee</flux:select.option>
                    <flux:select.option value="percent_off">Percent off</flux:select.option>
                </flux:select>
                <flux:select wire:model.live="filterStatus" class="w-36">
                    <flux:select.option value="">All statuses</flux:select.option>
                    <flux:select.option value="active">Active</flux:select.option>
                    <flux:select.option value="inactive">Inactive</flux:select.option>
                </flux:select>
            </div>
        </div>

        {{-- Bulk action bar --}}
        @if (count($selected) > 0)
            <div class="flex flex-wrap items-center gap-3 border-b border-zinc-200 bg-brand-50 px-6 py-2.5 dark:border-zinc-700 dark:bg-brand-500/10">
                <flux:text class="font-medium">{{ count($selected) }} selected</flux:text>
                <flux:button size="sm" variant="ghost" wire:click="bulkActivate">Activate</flux:button>
                <flux:button size="sm" variant="ghost" wire:click="bulkDeactivate">Deactivate</flux:button>
                <flux:button size="sm" variant="ghost" icon="trash-2"
                    wire:click="bulkDelete"
                    wire:confirm="Delete {{ count($selected) }} promotion(s)? This cannot be undone."
                    class="text-red-500! hover:text-red-600!">Delete</flux:button>
                <flux:spacer />
                <flux:button size="sm" variant="ghost" wire:click="clearSelection">Clear</flux:button>
            </div>
        @endif

        <flux:table container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
            <flux:table.columns class="bg-zinc-50 dark:bg-zinc-800/60">
                <flux:table.column class="w-10">
                    <flux:checkbox wire:model.live="selectAll" />
                </flux:table.column>
                <flux:table.column>Promotion</flux:table.column>
                <flux:table.column>Applies to</flux:table.column>
                <flux:table.column>Effect</flux:table.column>
                <flux:table.column>Min order</flux:table.column>
                <flux:table.column>Window</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->promotions as $promo)
                    <flux:table.row :key="$promo->id" wire:key="promo-{{ $promo->id }}">
                        <flux:table.cell>
                            <flux:checkbox wire:model.live="selected" value="{{ $promo->id }}" />
                        </flux:table.cell>
                        <flux:table.cell variant="strong">{{ $promo->name }}</flux:table.cell>
                        <flux:table.cell class="text-zinc-500">{{ $promo->scope->label() }}{{ $promo->zone ? ': '.$promo->zone->name : '' }}</flux:table.cell>
                        <flux:table.cell class="text-zinc-500">
                            {{ $promo->effect->label() }}@if ($promo->effect === \App\Enums\DeliveryPromotionEffect::FLAT_FEE) ({{ money($promo->value_cents) }})@elseif ($promo->effect === \App\Enums\DeliveryPromotionEffect::PERCENT_OFF) ({{ $promo->percent }}%)@endif
                        </flux:table.cell>
                        <flux:table.cell class="tabular-nums text-zinc-500">{{ $promo->min_subtotal_cents > 0 ? money($promo->min_subtotal_cents) : '—' }}</flux:table.cell>
                        <flux:table.cell class="text-sm text-zinc-500">
                            {{ $promo->starts_at?->format('d M Y') ?? 'now' }} – {{ $promo->ends_at?->format('d M Y') ?? 'open' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <button type="button" wire:click="togglePromoActive({{ $promo->id }})">
                                <flux:badge :color="$promo->isLiveNow() ? 'green' : 'zinc'" size="sm" inset="top bottom">
                                    {{ $promo->isLiveNow() ? 'Live' : ($promo->is_active ? 'Scheduled' : 'Off') }}
                                </flux:badge>
                            </button>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="flex items-center justify-end gap-1">
                                <flux:tooltip content="Activity log">
                                    <flux:button size="xs" variant="ghost" icon="clock"
                                        :href="route('admin.activity.item', ['delivery_promotion', $promo->id])"
                                        wire:navigate />
                                </flux:tooltip>
                                <flux:button size="xs" variant="ghost" icon="pencil-square" tooltip="Edit"
                                    wire:click="openEditPromo({{ $promo->id }})" />
                                <flux:button size="xs" variant="ghost" icon="trash-2" tooltip="Delete"
                                    wire:click="deletePromo({{ $promo->id }})"
                                    wire:confirm="Delete the {{ $promo->name }} promotion?"
                                    class="text-red-500! hover:text-red-600!" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="8" class="py-12 text-center text-zinc-400">
                            @if ($search || $filterScope || $filterEffect || $filterStatus)
                                No promotions match your filters.
                            @else
                                No promotions yet. Add a global "free delivery" promo for launch.
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{-- ================================================== --}}
    {{-- PROMOTION MODAL --}}
    {{-- ================================================== --}}
    <flux:modal wire:model.self="showPromoModal" class="md:w-[560px]" :dismissible="false">
        <flux:heading class="uppercase tracking-wide">{{ $editingPromoId ? 'Edit promotion' : 'New promotion' }}</flux:heading>
        <flux:subheading>Layer a discount or free delivery on top of zone fees.</flux:subheading>

        <form wire:submit="savePromo" class="mt-6 space-y-4">
            <flux:field>
                <flux:label>Name</flux:label>
                <flux:input wire:model="pName" placeholder="Launch free delivery" />
                <flux:error name="pName" />
            </flux:field>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
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

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
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

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
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
