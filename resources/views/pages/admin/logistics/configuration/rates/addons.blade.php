<?php

use App\Enums\AddonType;
use App\Enums\ShippingRateAddonStatus;
use App\Models\PickupStation;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\ShippingRateAddon;
use App\Livewire\Forms\Admin\ShippingRateAddonForm;
use Livewire\Attributes\{Title, Computed, Url};
use Livewire\WithPagination;
use Livewire\Component;
use Flux\Flux;

new #[Title('Rate Addons')] class extends Component {
    use WithPagination;

    public ShippingRateAddonForm $form;
    public ?int $deletingId = null;

    #[Url(history: true)]
    public string $filterMethod = '';

    #[Url(history: true)]
    public string $filterAddonType = '';

    #[Url(history: true)]
    public string $filterStatus = '';

    public function updatedFilterMethod(): void
    {
        $this->resetPage();
    }
    public function updatedFilterAddonType(): void
    {
        $this->resetPage();
    }
    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function addons()
    {
        return ShippingRateAddon::with(['shippingRate.shippingZone', 'shippingRate.shippingMethod', 'pickupStation'])
            ->when($this->filterMethod, fn($q) => $q->whereHas('shippingRate', fn($r) => $r->where('shipping_method_id', $this->filterMethod)))
            ->when($this->filterAddonType, fn($q) => $q->where('addon_type', $this->filterAddonType))
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    // Only flat/pus methods make sense for addons
    #[Computed]
    public function methods()
    {
        return ShippingMethod::whereIn('type', ['flat', 'pus'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    // Active flat rates — what an addon stacks on top of
    #[Computed]
    public function baseRates()
    {
        return ShippingRate::with(['shippingZone', 'shippingMethod'])
            ->where('status', 'active')
            ->when($this->filterMethod, fn($q) => $q->where('shipping_method_id', $this->filterMethod))
            ->orderBy('shipping_method_id')
            ->orderBy('min_weight')
            ->get()
            ->map(
                fn($r) => [
                    'id' => $r->id,
                    'label' => "{$r->shippingMethod->name} · {$r->shippingZone->name} · {$r->weight_label}",
                ],
            );
    }

    #[Computed]
    public function pickupStations()
    {
        return PickupStation::where('status', 'active')->orderBy('name')->get();
    }

    #[Computed]
    public function addonTypes(): array
    {
        return AddonType::cases();
    }

    #[Computed]
    public function statuses(): array
    {
        return ShippingRateAddonStatus::cases();
    }

    public function openCreate(): void
    {
        $this->form->reset();
        Flux::modal('addon-modal')->show();
    }

    public function save(): void
    {
        try {
            $isEditing = (bool) $this->form->addon;
            $isEditing ? $this->form->update() : $this->form->store();

            $this->form->reset();
            Flux::modal('addon-modal')->close();
            $this->dispatch('notify', title: $isEditing ? 'Addon Updated' : 'Addon Added', variant: 'success', message: $isEditing ? 'Addon updated.' : 'Addon added.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            logger()->error('Failed to save rate addon.', [
                'exception' => $e->getMessage(),
                'addon_id' => $this->form->addon?->id,
                'user_id' => auth()->id(),
            ]);
            $this->dispatch('notify', title: 'Save Failed', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }

    public function edit(ShippingRateAddon $addon): void
    {
        $this->form->setAddon($addon);
        Flux::modal('addon-modal')->show();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        Flux::modal('delete-confirmation')->show();
    }

    public function delete(): void
    {
        if (!$this->deletingId) {
            return;
        }

        try {
            ShippingRateAddon::destroy($this->deletingId);
            $this->deletingId = null;
            Flux::modal('delete-confirmation')->close();
            $this->dispatch('notify', title: 'Addon Deleted', variant: 'danger', message: 'Addon deleted.');
        } catch (\Throwable $e) {
            logger()->error('Failed to delete rate addon.', [
                'exception' => $e->getMessage(),
                'addon_id' => $this->deletingId,
                'user_id' => auth()->id(),
            ]);
            $this->dispatch('notify', title: 'Delete Failed', variant: 'danger', message: 'Could not delete this addon.');
        }
    }
}; ?>

<x-admin.logistics.layout heading="Rate Addons"
    subheading="Surcharges that stack on top of flat rates. Used primarily for PUS pickup station fees.">

    <x-slot:actions>
        <flux:button variant="primary" icon="plus-circle" wire:click="openCreate" class="cursor-pointer">
            Add Addon
        </flux:button>
    </x-slot:actions>

    <flux:card class="p-0 **:data-flux-columns:bg-zinc-50 dark:**:data-flux-columns:bg-zinc-800">

        {{-- Filters --}}
        <div class="flex items-center gap-4 px-5 py-3 border-b dark:border-zinc-600">
            <flux:dropdown position="bottom" align="end">
                <flux:button variant="ghost" size="sm" icon="funnel" icon-variant="outline" icon-trailing="chevron-down">
                    Filters
                    @php $activeFilters = collect([$filterMethod, $filterAddonType, $filterStatus])->filter()->count(); @endphp
                    @if ($activeFilters > 0)
                        <flux:badge size="sm" class="ms-1">{{ $activeFilters }}</flux:badge>
                    @endif
                </flux:button>

                <flux:menu class="min-w-64">
                    <div class="flex items-center justify-between px-3 py-2 border-b dark:border-zinc-700">
                        <flux:subheading>Filter Options</flux:subheading>
                        <flux:button variant="ghost" size="xs"
                            wire:click="$set('filterMethod', ''); $set('filterAddonType', ''); $set('filterStatus', '')"
                            class="cursor-pointer">Reset</flux:button>
                    </div>
                    <flux:separator />
                    <div class="p-3 space-y-3">
                        <flux:field>
                            <flux:label>Method</flux:label>
                            <flux:select wire:model.live="filterMethod" placeholder="All Methods" clearable>
                                @foreach ($this->methods as $method)
                                    <flux:select.option value="{{ $method->id }}">{{ $method->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </flux:field>
                        <flux:field>
                            <flux:label>Addon Type</flux:label>
                            <flux:select wire:model.live="filterAddonType" placeholder="All Types" clearable>
                                @foreach ($this->addonTypes as $type)
                                    <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </flux:field>
                        <flux:field>
                            <flux:label>Status</flux:label>
                            <flux:select wire:model.live="filterStatus" placeholder="All Statuses" clearable>
                                @foreach ($this->statuses as $status)
                                    <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </flux:field>
                    </div>
                </flux:menu>
            </flux:dropdown>
        </div>

        <flux:table :paginate="$this->addons">
            <flux:table.columns>
                <flux:table.column class="ps-4!">Base Rate</flux:table.column>
                <flux:table.column>Type</flux:table.column>
                <flux:table.column>Label</flux:table.column>
                <flux:table.column>Amount</flux:table.column>
                <flux:table.column>Station Scope</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end" class="pe-4!">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->addons as $addon)
                    <flux:table.row :key="$addon->id">
                        <flux:table.cell class="ps-4!">
                            <flux:heading size="sm">{{ $addon->shippingRate->shippingMethod->name }}</flux:heading>
                            <flux:subheading>
                                {{ $addon->shippingRate->shippingZone->name }}
                                &middot; {{ $addon->shippingRate->weight_label }}
                            </flux:subheading>
                        </flux:table.cell>

                        <flux:table.cell>
                            @php
                                $type =
                                    $addon->addon_type instanceof \App\Enums\AddonType
                                        ? $addon->addon_type
                                        : \App\Enums\AddonType::from($addon->addon_type);
                            @endphp
                            <flux:badge color="orange" variant="flat" size="sm">{{ $type->label() }}</flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:subheading>{{ $addon->label ?? '—' }}</flux:subheading>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:heading size="sm">{{ format_currency($addon->addon_amount) }}</flux:heading>
                        </flux:table.cell>

                        <flux:table.cell>
                            @if ($addon->pickupStation)
                                <flux:subheading>{{ $addon->pickupStation->name }}</flux:subheading>
                            @else
                                <flux:subheading>All stations</flux:subheading>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            @php
                                $status =
                                    $addon->status instanceof \App\Enums\ShippingRateAddonStatus
                                        ? $addon->status
                                        : \App\Enums\ShippingRateAddonStatus::from($addon->status);
                            @endphp
                            <flux:badge :color="$status->color()" variant="flat" size="sm">{{ $status->label() }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell align="end" class="pe-4!">
                            <flux:button variant="ghost" size="sm" icon="pencil-square" icon-variant="outline"
                                class="cursor-pointer" wire:click="edit({{ $addon->id }})" tooltip="Edit addon" />
                            <flux:button variant="ghost" size="sm" icon="trash" icon-variant="outline"
                                color="red" class="cursor-pointer text-red-500!"
                                wire:click="confirmDelete({{ $addon->id }})" tooltip="Delete addon" />
                        </flux:table.cell>
                    </flux:table.row>

                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="py-12 text-center">
                            <div class="flex flex-col items-center gap-3 text-zinc-400">
                                <flux:icon.plus-circle class="w-10 h-10 opacity-40" />
                                <div>
                                    <flux:heading size="sm">No rate addons found</flux:heading>
                                    <flux:subheading class="mt-0.5">
                                        @if ($this->filterMethod || $this->filterAddonType || $this->filterStatus)
                                            No results match your current filters.
                                        @else
                                            Add PUS surcharges or other fees that stack on top of base rates.
                                        @endif
                                    </flux:subheading>
                                </div>
                                @if ($this->filterMethod || $this->filterAddonType || $this->filterStatus)
                                    <flux:button variant="ghost" size="sm"
                                        wire:click="$set('filterMethod', ''); $set('filterAddonType', ''); $set('filterStatus', '')">
                                        Clear filters
                                    </flux:button>
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{-- Create / Edit Modal --}}
    <flux:modal name="addon-modal" class="md:w-120 space-y-6">
        <flux:heading size="lg">{{ $form->addon ? 'Edit Addon' : 'Add Rate Addon' }}</flux:heading>

        <form wire:submit="save" class="space-y-4">
            <flux:select wire:model="form.shipping_rate_id" label="Base Rate" searchable placeholder="Select a rate...">
                @foreach ($this->baseRates as $rate)
                    <flux:select.option value="{{ $rate['id'] }}">{{ $rate['label'] }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="grid grid-cols-2 gap-4">
                <flux:select wire:model="form.addon_type" label="Addon Type">
                    @foreach ($this->addonTypes as $type)
                        <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="form.addon_amount" label="Amount ({{ get_currency_symbol() }})" type="number"
                    min="0" step="0.01" placeholder="e.g. 300" />
            </div>

            <flux:input wire:model="form.label" label="Label (Optional)" placeholder="e.g. Pickup Station Surcharge" />

            <flux:select wire:model="form.pickup_station_id" label="Station Scope (Optional)" clearable
                placeholder="Applies to all stations">
                @foreach ($this->pickupStations as $station)
                    <flux:select.option value="{{ $station->id }}">{{ $station->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="form.status" label="Status">
                @foreach ($this->statuses as $status)
                    <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost" class="cursor-pointer">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" class="ml-2 cursor-pointer">Save Addon</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Delete Confirmation --}}
    <flux:modal name="delete-confirmation" class="md:w-88 space-y-6">
        <flux:heading size="lg" class="mb-2">Delete Addon?</flux:heading>
        <flux:subheading>This surcharge will be permanently removed and will no longer apply at checkout.
        </flux:subheading>
        <div class="flex gap-3">
            <flux:modal.close class="flex-1">
                <flux:button variant="ghost" class="w-full cursor-pointer">Cancel</flux:button>
            </flux:modal.close>
            <flux:button wire:click="delete" variant="danger" class="flex-1 cursor-pointer">Delete</flux:button>
        </div>
    </flux:modal>

</x-admin.logistics.layout>
