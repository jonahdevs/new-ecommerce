<?php

use App\Enums\VehicleRateStatus;
use App\Enums\VehicleType;
use App\Models\ShippingMethod;
use App\Models\VehicleRate;
use App\Livewire\Forms\Admin\VehicleRateForm;
use Livewire\Attributes\{Title, Computed, Url};
use Livewire\Component;
use Flux\Flux;

new #[Title('Vehicle Rates')] class extends Component {
    public VehicleRateForm $form;
    public ?int $deletingId = null;

    #[Url(history: true)]
    public string $selectedMethodId = '';

    #[Url(history: true)]
    public string $filterStatus = '';

    public bool $showHistory = false;

    public function updatedFilterStatus(): void {}

    #[Computed]
    public function distanceMethods()
    {
        return ShippingMethod::where('type', 'distance')->where('status', 'active')->with('logisticsProvider')->orderBy('name')->get();
    }

    #[Computed]
    public function rates()
    {
        if (!$this->selectedMethodId) {
            return collect();
        }

        return VehicleRate::where('shipping_method_id', $this->selectedMethodId)
            ->when(!$this->showHistory, fn($q) => $q->where('status', VehicleRateStatus::ACTIVE->value))
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->orderByRaw("FIELD(vehicle_type, 'motorbike','van','truck_3t','truck_5t','truck_7t','truck_10t')")
            ->get();
    }

    #[Computed]
    public function vehicleTypes(): array
    {
        return VehicleType::cases();
    }

    #[Computed]
    public function statuses(): array
    {
        return VehicleRateStatus::cases();
    }

    public function openCreate(): void
    {
        $this->form->reset();
        $this->form->shipping_method_id = $this->selectedMethodId;
        Flux::modal('rate-modal')->show();
    }

    public function save(): void
    {
        try {
            $isEditing = (bool) $this->form->vehicleRate;
            $isEditing ? $this->form->update() : $this->form->store();

            $this->form->reset();
            Flux::modal('rate-modal')->close();

            $message = $isEditing ? 'Rate updated. Previous rate deprecated.' : 'Vehicle rate added.';

            $this->dispatch('notify', variant: 'success', message: $message);
            unset($this->rates);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            logger()->error('Failed to save vehicle rate.', [
                'exception' => $e->getMessage(),
                'vehicle_rate_id' => $this->form->vehicleRate?->id,
                'user_id' => auth()->id(),
            ]);
            $this->dispatch('notify', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }

    public function edit(VehicleRate $vehicleRate): void
    {
        $this->form->setVehicleRate($vehicleRate);
        Flux::modal('rate-modal')->show();
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
            VehicleRate::findOrFail($this->deletingId)->update(['status' => VehicleRateStatus::DEPRECATED->value]);

            $this->deletingId = null;
            Flux::modal('delete-confirmation')->close();
            $this->dispatch('notify', variant: 'warning', message: 'Vehicle rate deprecated.');
            unset($this->rates);
        } catch (\Throwable $e) {
            logger()->error('Failed to deprecate vehicle rate.', [
                'exception' => $e->getMessage(),
                'vehicle_rate_id' => $this->deletingId,
                'user_id' => auth()->id(),
            ]);
            $this->dispatch('notify', variant: 'danger', message: 'Could not deprecate this rate.');
        }
    }
}; ?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate />
        <flux:breadcrumbs.item>Logistics</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Vehicle Rates</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between mb-8">
        <div>
            <flux:heading size="xl" class="mb-2">Vehicle Rates</flux:heading>
            <flux:subheading>On-demand pricing by vehicle type. Price = base rate + extra KM charges beyond the included
                distance.</flux:subheading>
        </div>
        @if ($selectedMethodId)
            <div class="flex items-center gap-3">
                <flux:switch wire:model.live="showHistory" label="Show deprecated" />
                <flux:button variant="primary" icon="plus" wire:click="openCreate" class="cursor-pointer">
                    Add Vehicle Rate
                </flux:button>
            </div>
        @endif
    </div>

    {{-- Method selector + status filter --}}
    <div class="flex flex-col md:flex-row gap-4 mb-6">
        <flux:select wire:model.live="selectedMethodId" placeholder="Select a shipping method..." class="max-w-sm">
            @foreach ($this->distanceMethods as $method)
                <flux:select.option value="{{ $method->id }}">
                    {{ $method->name }} ({{ $method->logisticsProvider->name }})
                </flux:select.option>
            @endforeach
        </flux:select>

        @if ($selectedMethodId)
            <flux:select wire:model.live="filterStatus" placeholder="All Statuses" clearable class="md:w-44">
                @foreach ($this->statuses as $status)
                    <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                @endforeach
            </flux:select>
        @endif
    </div>

    @if (!$selectedMethodId)
        <flux:card class="py-16">
            <div class="flex flex-col items-center gap-3 text-zinc-400">
                <flux:icon.calculator class="w-10 h-10 opacity-40" />
                <div class="text-center">
                    <p class="text-sm font-medium text-zinc-600 dark:text-zinc-300">Select a distance method above</p>
                    <p class="text-xs mt-0.5">Only methods with type "Distance" are shown.</p>
                </div>
            </div>
        </flux:card>
    @else
        <flux:card class="p-0">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column class="ps-4!">Vehicle</flux:table.column>
                    <flux:table.column>Base Rate</flux:table.column>
                    <flux:table.column>Included KM</flux:table.column>
                    <flux:table.column>Extra KM Rate</flux:table.column>
                    <flux:table.column>Max Weight</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column align="end" class="pe-4!">Actions</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->rates as $rate)
                        <flux:table.row :key="$rate->id"
                            class="{{ $rate->status === VehicleRateStatus::DEPRECATED->value || ($rate->status instanceof \App\Enums\VehicleRateStatus && $rate->status === \App\Enums\VehicleRateStatus::DEPRECATED) }}">

                            <flux:table.cell class="ps-4!">
                                <div class="font-semibold">{{ $rate->vehicle_label }}</div>
                                <code
                                    class="text-xs text-zinc-400">{{ $rate->vehicle_type instanceof \App\Enums\VehicleType ? $rate->vehicle_type->value : $rate->vehicle_type }}</code>
                            </flux:table.cell>

                            <flux:table.cell>
                                <span class="font-medium">KES {{ number_format($rate->base_rate, 0) }}</span>
                            </flux:table.cell>

                            <flux:table.cell>
                                <span class="text-sm">{{ $rate->base_km }} km</span>
                            </flux:table.cell>

                            <flux:table.cell>
                                <span class="text-sm">KES {{ number_format($rate->extra_km_rate, 0) }}/km</span>
                            </flux:table.cell>

                            <flux:table.cell>
                                <span class="text-sm">
                                    {{ $rate->max_weight_kg ? number_format($rate->max_weight_kg, 0) . ' kg' : '—' }}
                                </span>
                            </flux:table.cell>

                            <flux:table.cell>
                                @php
                                    $status =
                                        $rate->status instanceof \App\Enums\VehicleRateStatus
                                            ? $rate->status
                                            : \App\Enums\VehicleRateStatus::from($rate->status);
                                @endphp
                                <flux:badge :color="$status->color()" variant="flat" size="sm">
                                    {{ $status->label() }}
                                </flux:badge>
                            </flux:table.cell>

                            <flux:table.cell align="end" class="pe-4!">
                                @if ($status !== \App\Enums\VehicleRateStatus::DEPRECATED)
                                    <flux:button variant="ghost" size="sm" icon="pencil-square"
                                        icon-variant="outline" class="cursor-pointer text-brand-secondary!"
                                        wire:click="edit({{ $rate->id }})" />
                                    <flux:button variant="ghost" size="sm" icon="archive-box"
                                        icon-variant="outline" color="red" class="cursor-pointer text-red-500!"
                                        wire:click="confirmDelete({{ $rate->id }})" />
                                @endif
                            </flux:table.cell>
                        </flux:table.row>

                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="7" class="py-12 text-center">
                                <div class="flex flex-col items-center gap-3 text-zinc-400">
                                    <flux:icon.calculator class="w-10 h-10 opacity-40" />
                                    <div>
                                        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-300">No vehicle rates
                                            yet</p>
                                        <p class="text-xs mt-0.5">Add a vehicle rate to enable on-demand pricing.</p>
                                    </div>
                                    <flux:button variant="primary" size="sm" icon="plus"
                                        wire:click="openCreate">
                                        Add Vehicle Rate
                                    </flux:button>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
    @endif

    {{-- Create / Edit Modal --}}
    <flux:modal name="rate-modal" class="md:w-120 space-y-6">
        <div>
            <flux:heading size="lg">{{ $form->vehicleRate ? 'Edit Vehicle Rate' : 'Add Vehicle Rate' }}
            </flux:heading>
            @if ($form->vehicleRate)
                <flux:subheading class="mt-1">This will deprecate the current rate and create a new active one.
                </flux:subheading>
            @endif
        </div>

        <form wire:submit="save" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <flux:select wire:model="form.vehicle_type" label="Vehicle Type" placeholder="Select...">
                    @foreach ($this->vehicleTypes as $type)
                        <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="form.vehicle_label" label="Display Label" placeholder="e.g. 3T Truck" />
            </div>

            <div class="grid grid-cols-3 gap-4">
                <flux:input wire:model="form.base_rate" label="Base Rate (KES)" type="number" min="0"
                    step="0.01" placeholder="8500" />
                <flux:input wire:model="form.base_km" label="Included KM" type="number" min="1"
                    placeholder="50" />
                <flux:input wire:model="form.extra_km_rate" label="Extra KM Rate" type="number" min="0"
                    step="0.01" placeholder="70" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="form.max_weight_kg" label="Max Weight (Kg)" type="number" min="0"
                    step="0.01" placeholder="Optional" />
                <flux:input wire:model="form.max_volume_m3" label="Max Volume (m³)" type="number" min="0"
                    step="0.001" placeholder="Optional" />
            </div>

            <div class="flex">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost" class="cursor-pointer">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" class="ml-2 cursor-pointer">
                    {{ $form->vehicleRate ? 'Update Rate' : 'Save Rate' }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Deprecate confirmation --}}
    <flux:modal name="delete-confirmation" class="md:w-88 space-y-6">
        <flux:heading size="lg" class="mb-2">Deprecate Vehicle Rate?</flux:heading>
        <flux:subheading>The rate will be marked as deprecated and hidden from checkout. Historical delivery orders that
            reference it will not be affected.</flux:subheading>
        <div class="flex gap-3">
            <flux:modal.close class="flex-1">
                <flux:button variant="ghost" class="w-full cursor-pointer">Cancel</flux:button>
            </flux:modal.close>
            <flux:button wire:click="delete" variant="danger" class="flex-1 cursor-pointer">Deprecate</flux:button>
        </div>
    </flux:modal>
</div>

<style>
    [data-flux-pagination] {
        padding-inline: 1rem;
        padding-bottom: 1rem;
    }
</style>
