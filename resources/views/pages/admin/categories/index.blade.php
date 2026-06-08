<?php

use App\Enums\CategoryStatus;
use App\Models\Category;
use Flux\Flux;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::app')] #[Title('Categories — Admin')] class extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $filterStatus = '';

    #[Url]
    public int $perPage = 10;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function categories()
    {
        return Category::with('parent')
            ->withCount('products')
            ->when($this->search, fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($this->perPage);
    }

    public function toggleStatus(int $id): void
    {
        $cat = Category::findOrFail($id);
        $cat->update([
            'status' => $cat->status === CategoryStatus::ACTIVE ? CategoryStatus::INACTIVE : CategoryStatus::ACTIVE,
        ]);
        unset($this->categories);
    }

    public function duplicateCategory(int $id): void
    {
        $original = Category::findOrFail($id);
        $copy = $original->replicate(['slug']);
        $copy->name = 'Copy of '.$original->name;
        $copy->status = CategoryStatus::INACTIVE;
        $base = Str::slug($copy->name);
        $slug = $base;
        $i = 1;
        while (Category::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }
        $copy->slug = $slug;
        $copy->save();
        unset($this->categories);
        Flux::toast(heading: 'Category duplicated', text: $copy->name.' has been created.', variant: 'success');
    }

    public function delete(int $id): void
    {
        $cat = Category::withCount('products')->findOrFail($id);

        if ($cat->products_count > 0) {
            Flux::toast(heading: 'Cannot delete', text: $cat->name.' has '.$cat->products_count.' products attached.', variant: 'danger');

            return;
        }

        $cat->delete();
        unset($this->categories);
        Flux::toast(heading: 'Category deleted', text: $cat->name.' has been removed.', variant: 'success');
    }
}; ?>

<div>
    <div class="flex items-center justify-between">
        <div>
            @push('breadcrumbs')
                <flux:breadcrumbs>
                    <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item>Categories</flux:breadcrumbs.item>
                </flux:breadcrumbs>
            @endpush
            <flux:heading size="xl">Categories</flux:heading>
            <flux:subheading>Organise products into a browsable hierarchy.</flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" :href="route('admin.categories.create')" wire:navigate>
            Add category
        </flux:button>
    </div>

    <flux:card class="mt-6 overflow-hidden p-0">

        {{-- Toolbar --}}
        <div class="flex items-center justify-between gap-4 border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search categories…"
                icon="magnifying-glass" clearable class="max-w-xs" />

            <div class="flex items-center gap-2">
                <flux:select wire:model.live="filterStatus" class="w-40">
                    <flux:select.option value="">All statuses</flux:select.option>
                    @foreach (CategoryStatus::cases() as $s)
                        <flux:select.option :value="$s->value">{{ $s->label() }}</flux:select.option>
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
                <flux:table.column>Name</flux:table.column>
                <flux:table.column>Slug</flux:table.column>
                <flux:table.column>Parent</flux:table.column>
                <flux:table.column>Products</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->categories as $category)
                    <flux:table.row :key="$category->id">
                        <flux:table.cell variant="strong">{{ $category->name }}</flux:table.cell>
                        <flux:table.cell class="font-mono text-xs text-zinc-400">{{ $category->slug }}</flux:table.cell>
                        <flux:table.cell class="text-zinc-500">
                            {{ $category->parent?->name ?? '—' }}
                        </flux:table.cell>
                        <flux:table.cell class="tabular-nums">{{ $category->products_count }}</flux:table.cell>
                        <flux:table.cell>
                            <button wire:click="toggleStatus({{ $category->id }})">
                                <flux:badge size="sm" inset="top bottom" :color="$category->status->color()">
                                    {{ $category->status->label() }}
                                </flux:badge>
                            </button>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:dropdown align="end">
                                <flux:button size="sm" icon-trailing="chevron-down">Actions</flux:button>
                                <flux:menu>
                                    <flux:menu.item icon="pencil-square"
                                        :href="route('admin.categories.edit', $category)" wire:navigate>
                                        Edit
                                    </flux:menu.item>
                                    <flux:menu.item icon="document-duplicate"
                                        wire:click="duplicateCategory({{ $category->id }})">
                                        Duplicate
                                    </flux:menu.item>
                                    <flux:menu.item icon="clock"
                                        :href="route('admin.activity.item', ['category', $category->id])"
                                        wire:navigate>
                                        Activity log
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item icon="trash" variant="danger"
                                        wire:click="delete({{ $category->id }})"
                                        wire:confirm="Delete '{{ addslashes($category->name) }}'? This cannot be undone.">
                                        Delete
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="py-12 text-center text-zinc-400">
                            No categories found.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if ($this->categories->hasPages())
            <div class="border-t border-zinc-200 px-6 pb-3 dark:border-zinc-700">
                <flux:pagination :paginator="$this->categories" />
            </div>
        @endif
    </flux:card>
</div>
