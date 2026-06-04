<?php

use App\Models\Warehouse;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Warehouses — Admin')] class extends Component
{
    // ==================================================
    // SEARCH & FILTER
    // ==================================================
    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $filterStatus = '';

    // ==================================================
    // BULK SELECTION
    // ==================================================
    /** @var array<int, string> */
    public array $selected = [];

    public bool $selectAll = false;

    #[Computed]
    public function warehouses()
    {
        return Warehouse::withCount('shipments')
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('city', 'like', '%'.$this->search.'%')
                    ->orWhere('county', 'like', '%'.$this->search.'%');
            }))
            ->when($this->filterStatus !== '', fn ($q) => $q->where('is_active', $this->filterStatus === 'active'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function updatedSearch(): void
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
            ? $this->warehouses->pluck('id')->map(fn ($id) => (string) $id)->all()
            : [];
    }

    public function clearSelection(): void
    {
        $this->selected = [];
        $this->selectAll = false;
    }

    public function toggleActive(int $id): void
    {
        $warehouse = Warehouse::findOrFail($id);
        $warehouse->update(['is_active' => ! $warehouse->is_active]);
        unset($this->warehouses);
    }

    public function delete(int $id): void
    {
        $warehouse = Warehouse::withCount('shipments')->findOrFail($id);

        if ($warehouse->shipments_count > 0) {
            Flux::toast(
                heading: 'Cannot delete',
                text: $warehouse->name.' has '.$warehouse->shipments_count.' associated shipment(s). Remove them first.',
                variant: 'danger',
            );

            return;
        }

        $warehouse->delete();
        unset($this->warehouses);
        Flux::toast(heading: 'Warehouse removed', text: $warehouse->name.' has been deleted.', variant: 'success');
    }

    // ==================================================
    // BULK ACTIONS
    // ==================================================
    public function bulkActivate(): void
    {
        if ($this->selected === []) {
            return;
        }

        $count = Warehouse::whereIn('id', $this->selected)->update(['is_active' => true]);
        $this->afterBulk();
        Flux::toast(heading: 'Warehouses activated', text: $count.' warehouse(s) set to active.', variant: 'success');
    }

    public function bulkDeactivate(): void
    {
        if ($this->selected === []) {
            return;
        }

        $count = Warehouse::whereIn('id', $this->selected)->update(['is_active' => false]);
        $this->afterBulk();
        Flux::toast(heading: 'Warehouses deactivated', text: $count.' warehouse(s) turned off.', variant: 'success');
    }

    public function bulkDelete(): void
    {
        if ($this->selected === []) {
            return;
        }

        $count = Warehouse::whereIn('id', $this->selected)->delete();
        $this->afterBulk();
        Flux::toast(heading: 'Warehouses deleted', text: $count.' warehouse(s) have been removed.', variant: 'warning');
    }

    private function afterBulk(): void
    {
        $this->clearSelection();
        unset($this->warehouses);
    }
}; ?>

<div>
    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Warehouses</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Warehouses</flux:heading>
            <flux:subheading>Stock locations customers can collect orders from.</flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" :href="route('admin.shipping.warehouses.create')" wire:navigate>
            Add warehouse
        </flux:button>
    </div>

    <flux:card class="mt-6 p-0 overflow-hidden">

        {{-- Toolbar --}}
        <div class="flex items-center justify-between gap-4 border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search name, city or county…"
                icon="magnifying-glass" clearable class="max-w-xs" />
            <flux:select wire:model.live="filterStatus" class="w-36">
                <flux:select.option value="">All statuses</flux:select.option>
                <flux:select.option value="active">Active</flux:select.option>
                <flux:select.option value="inactive">Inactive</flux:select.option>
            </flux:select>
        </div>

        {{-- Bulk action bar --}}
        @if (count($selected) > 0)
            <div class="flex flex-wrap items-center gap-3 border-b border-zinc-200 bg-brand-50 px-6 py-2.5 dark:border-zinc-700 dark:bg-brand-500/10">
                <flux:text class="font-medium">{{ count($selected) }} selected</flux:text>
                <flux:button size="sm" variant="ghost" wire:click="bulkActivate">Activate</flux:button>
                <flux:button size="sm" variant="ghost" wire:click="bulkDeactivate">Deactivate</flux:button>
                <flux:button size="sm" variant="ghost" icon="trash"
                    wire:click="bulkDelete"
                    wire:confirm="Delete {{ count($selected) }} warehouse(s)? This cannot be undone."
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
                <flux:table.column>Name</flux:table.column>
                <flux:table.column>Location</flux:table.column>
                <flux:table.column>Contact</flux:table.column>
                <flux:table.column>Shipments</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->warehouses as $warehouse)
                    <flux:table.row :key="$warehouse->id">
                        <flux:table.cell>
                            <flux:checkbox wire:model.live="selected" value="{{ $warehouse->id }}" />
                        </flux:table.cell>
                        <flux:table.cell variant="strong">{{ $warehouse->name }}</flux:table.cell>
                        <flux:table.cell>
                            <span class="block">{{ $warehouse->address }}</span>
                            <span class="text-xs text-zinc-400">{{ $warehouse->city }}, {{ $warehouse->county }}</span>
                        </flux:table.cell>
                        <flux:table.cell class="text-zinc-500">
                            {{ $warehouse->phone ?? '—' }}
                            @if ($warehouse->email)
                                <span class="block text-xs text-zinc-400">{{ $warehouse->email }}</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="tabular-nums text-zinc-500">
                            {{ $warehouse->shipments_count }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <button wire:click="toggleActive({{ $warehouse->id }})">
                                <flux:badge size="sm" inset="top bottom" :color="$warehouse->is_active ? 'green' : 'zinc'">
                                    {{ $warehouse->is_active ? 'Active' : 'Inactive' }}
                                </flux:badge>
                            </button>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="flex items-center justify-end gap-1">
                                <flux:button size="xs" variant="ghost" icon="pencil-square" tooltip="Edit"
                                    :href="route('admin.shipping.warehouses.edit', $warehouse)" wire:navigate />
                                <flux:button size="xs" variant="ghost" icon="trash" tooltip="Delete"
                                    wire:click="delete({{ $warehouse->id }})"
                                    wire:confirm="Delete {{ $warehouse->name }}?"
                                    class="text-red-500! hover:text-red-600!" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="py-12 text-center text-zinc-400">
                            @if ($search || $filterStatus)
                                No warehouses match your filters.
                            @else
                                No warehouses yet.
                                <a href="{{ route('admin.shipping.warehouses.create') }}" wire:navigate
                                    class="ml-1 text-brand-500 hover:underline">Add your first warehouse</a>.
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
