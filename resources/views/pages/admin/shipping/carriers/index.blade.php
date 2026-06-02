<?php

use App\Models\ShippingCarrier;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Carriers — Admin')] class extends Component
{
    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $filterDriver = '';

    #[Url]
    public string $filterStatus = '';

    #[Computed]
    public function carriers()
    {
        return ShippingCarrier::withCount(['carrierZones', 'carrierRates'])
            ->when($this->search, fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->when($this->filterDriver, fn ($q) => $q->where('driver', $this->filterDriver))
            ->when($this->filterStatus !== '', fn ($q) => $q->where('is_active', $this->filterStatus === 'active'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function toggleActive(int $id): void
    {
        $carrier = ShippingCarrier::findOrFail($id);
        $carrier->update(['is_active' => ! $carrier->is_active]);
        unset($this->carriers);
    }

    public function delete(int $id): void
    {
        $carrier = ShippingCarrier::withCount('carrierZones')->findOrFail($id);

        if ($carrier->carrier_zones_count > 0) {
            Flux::toast(
                heading: 'Cannot delete',
                text: $carrier->name.' is assigned to '.$carrier->carrier_zones_count.' zone(s). Remove zone coverage first.',
                variant: 'danger',
            );

            return;
        }

        $carrier->delete();
        unset($this->carriers);
        Flux::toast(heading: 'Carrier removed', text: $carrier->name.' has been deleted.', variant: 'success');
    }
}; ?>

<div>
    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Carriers</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Carriers</flux:heading>
            <flux:subheading>Logistics companies that fulfil deliveries. Invisible to customers — they see method names only.</flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" :href="route('admin.shipping.carriers.create')" wire:navigate>
            Add carrier
        </flux:button>
    </div>

    {{-- Toolbar --}}
    <div class="mt-6 flex flex-wrap items-center gap-3">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search carriers…"
            icon="magnifying-glass" clearable class="max-w-xs" />
        <flux:select wire:model.live="filterDriver" class="w-44">
            <flux:select.option value="">All drivers</flux:select.option>
            @foreach (\App\Enums\CarrierDriver::cases() as $d)
                <flux:select.option :value="$d->value">{{ $d->label() }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="filterStatus" class="w-36">
            <flux:select.option value="">All statuses</flux:select.option>
            <flux:select.option value="active">Active</flux:select.option>
            <flux:select.option value="inactive">Inactive</flux:select.option>
        </flux:select>
    </div>

    @if ($this->carriers->isEmpty() && ! $search && ! $filterDriver && ! $filterStatus)
        <flux:card class="mt-6 py-16 text-center">
            <flux:icon.truck class="mx-auto size-12 text-zinc-300 dark:text-zinc-600" />
            <flux:heading class="mt-4">No carriers yet</flux:heading>
            <flux:text class="mt-1 text-zinc-400">Add your internal fleet or a third-party courier to start delivering.</flux:text>
            <flux:button class="mt-6" variant="primary" icon="plus" :href="route('admin.shipping.carriers.create')" wire:navigate>
                Add first carrier
            </flux:button>
        </flux:card>
    @elseif ($this->carriers->isEmpty())
        <flux:card class="mt-4 py-12 text-center text-zinc-400">
            No carriers match your filters.
        </flux:card>
    @else
        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($this->carriers as $carrier)
                <flux:card class="p-0 overflow-hidden">

                    {{-- Header --}}
                    <div class="flex items-start justify-between border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
                        <div class="min-w-0">
                            <flux:heading size="base">{{ $carrier->name }}</flux:heading>
                            <flux:badge size="sm" color="zinc" class="mt-1 capitalize">
                                {{ $carrier->driver->label() }}
                            </flux:badge>
                        </div>
                        <button wire:click="toggleActive({{ $carrier->id }})">
                            <flux:badge size="sm" :color="$carrier->is_active ? 'green' : 'zinc'">
                                {{ $carrier->is_active ? 'Active' : 'Inactive' }}
                            </flux:badge>
                        </button>
                    </div>

                    {{-- Stats --}}
                    <div class="space-y-2 px-5 py-4 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-zinc-500">Zones covered</span>
                            <span class="font-medium tabular-nums dark:text-white">{{ $carrier->carrier_zones_count }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-zinc-500">Active rates</span>
                            <span class="font-medium tabular-nums dark:text-white">{{ $carrier->carrier_rates_count }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-zinc-500">Credentials</span>
                            @if ($carrier->credentials)
                                <flux:badge size="sm" color="green">Configured</flux:badge>
                            @elseif ($carrier->isSelfManaged())
                                <flux:badge size="sm" color="blue">Internal fleet</flux:badge>
                            @else
                                <flux:badge size="sm" color="yellow">Not configured</flux:badge>
                            @endif
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="flex items-center justify-between border-t border-zinc-200 px-5 py-3 dark:border-zinc-700">
                        <flux:button size="sm" variant="ghost" icon="pencil-square"
                            :href="route('admin.shipping.carriers.edit', $carrier)" wire:navigate>
                            Configure
                        </flux:button>
                        <flux:button size="sm" variant="ghost" icon="trash" tooltip="Delete"
                            wire:click="delete({{ $carrier->id }})"
                            wire:confirm="Delete {{ $carrier->name }}?"
                            class="text-red-500! hover:text-red-600!" />
                    </div>

                </flux:card>
            @endforeach
        </div>
    @endif
</div>
