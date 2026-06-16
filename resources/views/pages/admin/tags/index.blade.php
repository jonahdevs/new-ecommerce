<?php

use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Tags\Tag;

new #[Layout('layouts::app')] #[Title('Tags — Admin')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $filterType = '';

    #[Url]
    public int $perPage = 10;

    public bool $showModal = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $type = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterType(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function tags()
    {
        return Tag::query()
            ->select('tags.*')
            ->selectSub(
                DB::table('taggables')->selectRaw('count(*)')->whereColumn('taggables.tag_id', 'tags.id'),
                'items_count'
            )
            ->when($this->search, fn ($q) => $q->containing($this->search))
            ->when($this->filterType !== '', fn ($q) => $q->where('type', $this->filterType))
            ->ordered()
            ->paginate($this->perPage);
    }

    /** @return \Illuminate\Support\Collection<int, string> */
    #[Computed]
    public function types(): \Illuminate\Support\Collection
    {
        return Tag::getTypes()->filter()->values();
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'name', 'type']);
        $this->resetValidation();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $tag = Tag::findOrFail($id);
        $this->editingId = $id;
        $this->name = $tag->name;
        $this->type = (string) $tag->type;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:255'],
        ]);

        $type = $this->type ?: null;

        $existing = Tag::findFromString($this->name, $type);
        if ($existing && $existing->id !== $this->editingId) {
            $this->addError('name', 'A tag with this name already exists for that type.');

            return;
        }

        if ($this->editingId) {
            $tag = Tag::findOrFail($this->editingId);
            $tag->name = $this->name;
            $tag->type = $type;
            $tag->save();

            Flux::toast(heading: 'Tag updated', text: $tag->name.' has been saved.', variant: 'success');
        } else {
            Tag::create(['name' => $this->name, 'type' => $type]);

            Flux::toast(heading: 'Tag created', text: $this->name.' has been added.', variant: 'success');
        }

        $this->showModal = false;
        unset($this->tags, $this->types);
    }

    public function delete(int $id): void
    {
        $tag = Tag::findOrFail($id);
        $name = $tag->name;
        $tag->delete();

        unset($this->tags, $this->types);
        Flux::toast(heading: 'Tag deleted', text: $name.' has been removed.', variant: 'success');
    }
}; ?>

<div>
    <div class="flex items-center justify-between">
        <div>
            @push('breadcrumbs')
<flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Tags</flux:breadcrumbs.item>
            </flux:breadcrumbs>
@endpush
            <flux:heading size="xl">Tags</flux:heading>
            <flux:subheading>Reusable labels for organising products and content.</flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="openCreate">Add tag</flux:button>
    </div>

    <flux:card class="mt-6 p-0 overflow-hidden">

        {{-- Toolbar --}}
        <div class="flex items-center justify-between gap-4 border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search tags…" icon="magnifying-glass"
                clearable class="max-w-xs" />

            <div class="flex items-center gap-2">
                <flux:select wire:model.live="filterType" class="w-44">
                    <flux:select.option value="">All types</flux:select.option>
                    @foreach ($this->types as $type)
                        <flux:select.option value="{{ $type }}">{{ ucfirst($type) }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="perPage" class="w-28">
                    <flux:select.option value="10">10 / page</flux:select.option>
                    <flux:select.option value="25">25 / page</flux:select.option>
                    <flux:select.option value="50">50 / page</flux:select.option>
                    <flux:select.option value="100">100 / page</flux:select.option>
                    <flux:select.option value="250">250 / page</flux:select.option>
                </flux:select>
            </div>
        </div>

        <flux:table
            container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
            <flux:table.columns class="bg-zinc-50 dark:bg-zinc-800/60">
                <flux:table.column>Tag</flux:table.column>
                <flux:table.column>Slug</flux:table.column>
                <flux:table.column>Type</flux:table.column>
                <flux:table.column align="end">Tagged items</flux:table.column>
                <flux:table.column align="end">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->tags as $tag)
                    <flux:table.row :key="$tag->id">
                        <flux:table.cell variant="strong">{{ $tag->name }}</flux:table.cell>
                        <flux:table.cell class="font-mono text-xs text-zinc-400">{{ $tag->slug }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($tag->type)
                                <flux:badge size="sm" inset="top bottom" color="zinc">{{ ucfirst($tag->type) }}</flux:badge>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell align="end" class="tabular-nums text-zinc-500">{{ $tag->items_count }}</flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="flex items-center justify-end gap-1">
                                <flux:button size="xs" variant="ghost" icon="pencil-square" wire:click="openEdit({{ $tag->id }})" />
                                <flux:button size="xs" variant="ghost" icon="trash-2"
                                    wire:click="delete({{ $tag->id }})"
                                    wire:confirm="Delete '{{ addslashes($tag->name) }}'? It will be removed from all tagged items."
                                    class="text-red-500! hover:text-red-600!" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="py-12 text-center text-zinc-400">No tags found.</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if ($this->tags->hasPages())
            <div class="border-t border-zinc-200 px-6 pb-3 dark:border-zinc-700">
                <flux:pagination :paginator="$this->tags" />
            </div>
        @endif
    </flux:card>

    {{-- Modal --}}
    <flux:modal wire:model.self="showModal" class="md:w-[440px]" :dismissible="false">
        <flux:heading class="uppercase tracking-wide">{{ $editingId ? 'Edit tag' : 'New tag' }}</flux:heading>

        <form wire:submit="save" class="mt-5 space-y-4">
            <flux:input wire:model="name" label="Name" placeholder="e.g. Energy efficient" required autofocus />
            <flux:input
                wire:model="type"
                label="Type"
                placeholder="Optional — e.g. feature, badge"
                description="Group related tags under a type. Leave blank for general tags." />

            <div class="flex justify-end gap-3 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ $editingId ? 'Save changes' : 'Create tag' }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
