<?php

use App\Models\ShippingMethod;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Shipping Methods — Admin')] class extends Component
{
    // ─── Search & filter ─────────────────────────────────────────────────────
    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $filterType = '';

    #[Url]
    public string $filterStatus = '';

    // ─── Bulk selection ───────────────────────────────────────────────────────
    /** @var array<int, string> */
    public array $selected = [];

    public bool $selectAll = false;

    #[Computed]
    public function methods()
    {
        return ShippingMethod::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->when($this->filterType, fn ($q) => $q->where('type', $this->filterType))
            ->when($this->filterStatus !== '', fn ($q) => $q->where('is_active', $this->filterStatus === 'active'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function updatedSearch(): void
    {
        $this->clearSelection();
    }

    public function updatedFilterType(): void
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
            ? $this->methods->pluck('id')->map(fn ($id) => (string) $id)->all()
            : [];
    }

    public function clearSelection(): void
    {
        $this->selected = [];
        $this->selectAll = false;
    }

    public function toggleActive(int $id): void
    {
        $method = ShippingMethod::findOrFail($id);
        $method->update(['is_active' => ! $method->is_active]);
        unset($this->methods);
    }

    public function delete(int $id): void
    {
        ShippingMethod::findOrFail($id)->delete();
        unset($this->methods);
        Flux::toast(heading: 'Method removed', text: 'The shipping method has been deleted.', variant: 'success');
    }

    // ─── Bulk actions ─────────────────────────────────────────────────────────
    public function bulkActivate(): void
    {
        if ($this->selected === []) {
            return;
        }

        $count = ShippingMethod::whereIn('id', $this->selected)->update(['is_active' => true]);
        $this->afterBulk();
        Flux::toast(heading: 'Methods activated', text: $count.' method(s) set to active.', variant: 'success');
    }

    public function bulkDeactivate(): void
    {
        if ($this->selected === []) {
            return;
        }

        $count = ShippingMethod::whereIn('id', $this->selected)->update(['is_active' => false]);
        $this->afterBulk();
        Flux::toast(heading: 'Methods deactivated', text: $count.' method(s) turned off.', variant: 'success');
    }

    public function bulkDelete(): void
    {
        if ($this->selected === []) {
            return;
        }

        $count = ShippingMethod::whereIn('id', $this->selected)->delete();
        $this->afterBulk();
        Flux::toast(heading: 'Methods deleted', text: $count.' method(s) have been removed.', variant: 'success');
    }

    private function afterBulk(): void
    {
        $this->clearSelection();
        unset($this->methods);
    }
}; ?>

<div>
    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Shipping methods</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Shipping methods</flux:heading>
            <flux:subheading>The labels customers see at checkout — pricing lives on each carrier's configuration.</flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" :href="route('admin.shipping.methods.create')" wire:navigate>
            Add method
        </flux:button>
    </div>

    <flux:card class="mt-6 p-0 overflow-hidden">

        {{-- Toolbar --}}
        <div class="flex items-center justify-between gap-4 border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search methods…"
                icon="magnifying-glass" clearable class="max-w-xs" />
            <div class="flex items-center gap-2">
                <flux:select wire:model.live="filterType" class="w-40">
                    <flux:select.option value="">All types</flux:select.option>
                    @foreach (\App\Enums\ShippingMethodType::cases() as $t)
                        <flux:select.option :value="$t->value">{{ $t->label() }}</flux:select.option>
                    @endforeach
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
                <flux:button size="sm" variant="ghost" icon="trash"
                    wire:click="bulkDelete"
                    wire:confirm="Delete {{ count($selected) }} method(s)? This cannot be undone."
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
                <flux:table.column>Method</flux:table.column>
                <flux:table.column>Type</flux:table.column>
                <flux:table.column>Description</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->methods as $method)
                    <flux:table.row :key="$method->id">
                        <flux:table.cell>
                            <flux:checkbox wire:model.live="selected" value="{{ $method->id }}" />
                        </flux:table.cell>
                        <flux:table.cell variant="strong">{{ $method->name }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" inset="top bottom"
                                :color="$method->type === \App\Enums\ShippingMethodType::DELIVERY ? 'blue' : 'purple'">
                                {{ $method->type->label() }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="text-zinc-500">{{ $method->description ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <button wire:click="toggleActive({{ $method->id }})">
                                <flux:badge size="sm" inset="top bottom" :color="$method->is_active ? 'green' : 'zinc'">
                                    {{ $method->is_active ? 'Active' : 'Inactive' }}
                                </flux:badge>
                            </button>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="flex items-center justify-end gap-1">
                                <flux:button size="xs" variant="ghost" icon="pencil-square" tooltip="Edit"
                                    :href="route('admin.shipping.methods.edit', $method)" wire:navigate />
                                <flux:button size="xs" variant="ghost" icon="trash" tooltip="Delete"
                                    wire:click="delete({{ $method->id }})"
                                    wire:confirm="Delete '{{ addslashes($method->name) }}'?"
                                    class="text-red-500! hover:text-red-600!" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="py-12 text-center text-zinc-400">
                            @if ($search || $filterType || $filterStatus)
                                No methods match your filters.
                            @else
                                No shipping methods yet.
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
