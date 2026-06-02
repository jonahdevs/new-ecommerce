<?php

use App\Models\Attribute;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::app')] #[Title('Attributes — Admin')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public int $perPage = 10;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function attributeList()
    {
        return Attribute::withCount('values')
            ->when($this->search, fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($this->perPage);
    }

    public function delete(int $id): void
    {
        Attribute::findOrFail($id)->delete();
        unset($this->attributeList);
        Flux::toast(heading: 'Attribute deleted', text: 'The attribute and its values have been removed.', variant: 'success');
    }
}; ?>

<div>
    <div class="flex items-center justify-between">
        <div>
            @push('breadcrumbs')
                <flux:breadcrumbs>
                    <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item>Attributes</flux:breadcrumbs.item>
                </flux:breadcrumbs>
            @endpush
            <flux:heading size="xl">Attributes</flux:heading>
            <flux:subheading>Product variation attributes such as colour, material or size.</flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" :href="route('admin.attributes.create')" wire:navigate>
            Add attribute
        </flux:button>
    </div>

    <flux:card class="mt-6 p-0 overflow-hidden">

        {{-- Toolbar --}}
        <div class="flex items-center justify-between gap-4 border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search attributes…"
                icon="magnifying-glass" clearable class="max-w-xs" />

            <flux:select wire:model.live="perPage" class="w-28">
                <flux:select.option value="10">10 / page</flux:select.option>
                <flux:select.option value="25">25 / page</flux:select.option>
                <flux:select.option value="50">50 / page</flux:select.option>
                <flux:select.option value="100">100 / page</flux:select.option>
                <flux:select.option value="250">250 / page</flux:select.option>
            </flux:select>
        </div>

        <flux:table container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
            <flux:table.columns class="bg-zinc-50 dark:bg-zinc-800/60">
                <flux:table.column>Attribute</flux:table.column>
                <flux:table.column>Type</flux:table.column>
                <flux:table.column>Values</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->attributeList as $attribute)
                    <flux:table.row :key="$attribute->id">
                        <flux:table.cell variant="strong">
                            {{ $attribute->name }}
                            <span class="block font-mono text-xs font-normal text-zinc-400">{{ $attribute->slug }}</span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" color="zinc" inset="top bottom" class="capitalize">
                                {{ $attribute->type->value }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="tabular-nums">
                            {{ $attribute->values_count }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" inset="top bottom" :color="$attribute->is_active ? 'green' : 'zinc'">
                                {{ $attribute->is_active ? 'Active' : 'Inactive' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="flex items-center justify-end gap-1">
                                <flux:button size="xs" variant="ghost" icon="pencil-square"
                                    :href="route('admin.attributes.edit', $attribute)"
                                    wire:navigate />
                                <flux:button size="xs" variant="ghost" icon="trash"
                                    wire:click="delete({{ $attribute->id }})"
                                    wire:confirm="Delete '{{ addslashes($attribute->name) }}' and all its values?"
                                    class="text-red-500! hover:text-red-600!" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="py-12 text-center text-zinc-400">
                            No attributes yet.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if ($this->attributeList->hasPages())
            <div class="border-t border-zinc-200 px-6 pb-3 dark:border-zinc-700">
                <flux:pagination :paginator="$this->attributeList" />
            </div>
        @endif
    </flux:card>
</div>
