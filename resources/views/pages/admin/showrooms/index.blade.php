<?php

use App\Models\Showroom;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Showrooms | Admin')] class extends Component
{
    #[Url(as: 'q')]
    public string $search = '';

    /** @var array<int, string> */
    public array $selected = [];

    public bool $selectAll = false;

    #[Computed]
    public function showrooms(): Collection
    {
        return Showroom::query()
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('city', 'like', '%'.$this->search.'%')
                    ->orWhere('address', 'like', '%'.$this->search.'%');
            }))
            ->orderBy('sort_order')
            ->orderBy('city')
            ->get();
    }

    public function updatedSearch(): void
    {
        $this->clearSelection();
    }

    public function updatedSelectAll(bool $value): void
    {
        $this->selected = $value
            ? $this->showrooms->pluck('id')->map(fn ($id) => (string) $id)->all()
            : [];
    }

    public function clearSelection(): void
    {
        $this->selected = [];
        $this->selectAll = false;
    }

    public function delete(int $id): void
    {
        Showroom::findOrFail($id)->delete();
        unset($this->showrooms);
        Flux::toast(heading: 'Showroom removed', text: 'The location has been deleted.', variant: 'warning');
    }

    public function bulkDelete(): void
    {
        if ($this->selected === []) {
            return;
        }

        $count = Showroom::whereIn('id', $this->selected)->delete();
        $this->clearSelection();
        unset($this->showrooms);
        Flux::toast(heading: 'Showrooms removed', text: $count.' location(s) have been deleted.', variant: 'warning');
    }
}; ?>

<div class="space-y-6">

    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Showrooms</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Showrooms</flux:heading>
            <flux:text class="mt-1">Branch locations shown in the storefront footer.</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" :href="route('admin.showrooms.create')" wire:navigate>
            Add showroom
        </flux:button>
    </div>

    <flux:card class="mt-6 p-0 overflow-hidden">

        {{-- Toolbar --}}
        <div class="flex items-center border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search city or address…"
                icon="magnifying-glass" clearable class="max-w-xs" />
        </div>

        {{-- Bulk action bar --}}
        @if (count($selected) > 0)
            <div class="flex flex-wrap items-center gap-3 border-b border-zinc-200 bg-brand-50 px-6 py-2.5 dark:border-zinc-700 dark:bg-brand-500/10">
                <flux:text class="font-medium">{{ count($selected) }} selected</flux:text>
                <flux:button size="sm" variant="ghost" icon="trash-2"
                    wire:click="bulkDelete"
                    wire:confirm="Delete {{ count($selected) }} showroom(s)? This cannot be undone."
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
                <flux:table.column>City</flux:table.column>
                <flux:table.column>Address</flux:table.column>
                <flux:table.column>Phones</flux:table.column>
                <flux:table.column>Order</flux:table.column>
                <flux:table.column align="end">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->showrooms as $showroom)
                    <flux:table.row :key="$showroom->id" wire:key="showroom-{{ $showroom->id }}">
                        <flux:table.cell>
                            <flux:checkbox wire:model.live="selected" value="{{ $showroom->id }}" />
                        </flux:table.cell>
                        <flux:table.cell variant="strong">
                            {{ $showroom->city }}
                            @if ($showroom->is_hq)
                                <flux:badge color="blue" size="sm" inset="top bottom" class="ml-1">HQ</flux:badge>
                            @endif
                            <span class="block text-xs text-zinc-400">{{ $showroom->country }}</span>
                        </flux:table.cell>
                        <flux:table.cell class="text-zinc-500">
                            {{ $showroom->address }}
                            @if ($showroom->pobox)
                                <span class="block text-xs text-zinc-400">{{ $showroom->pobox }}</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="text-sm text-zinc-500">{{ implode(' / ', $showroom->phones ?? []) }}</flux:table.cell>
                        <flux:table.cell class="tabular-nums text-zinc-500">{{ $showroom->sort_order }}</flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="flex items-center justify-end gap-1">
                                <flux:button size="xs" variant="ghost" icon="pencil-square" tooltip="Edit"
                                    :href="route('admin.showrooms.edit', $showroom)" wire:navigate />
                                <flux:button size="xs" variant="ghost" icon="trash-2" tooltip="Delete"
                                    wire:click="delete({{ $showroom->id }})"
                                    wire:confirm="Delete the {{ $showroom->city }} showroom?"
                                    class="text-red-500! hover:text-red-600!" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="py-12 text-center text-zinc-400">
                            @if ($search)
                                No showrooms match your search.
                            @else
                                No showrooms yet.
                                <a href="{{ route('admin.showrooms.create') }}" wire:navigate
                                    class="ml-1 text-brand-500 hover:underline">Add your first location</a>.
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
