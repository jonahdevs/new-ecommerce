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

    /** @var array<int, string> */
    public array $selected = [];

    public bool $selectAll = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedSelectAll(bool $value): void
    {
        $this->selected = $value ? $this->categories->pluck('id')->map(fn ($id) => (string) $id)->all() : [];
    }

    public function clearSelection(): void
    {
        $this->selected = [];
        $this->selectAll = false;
    }

    #[Computed]
    public function categories()
    {
        return Category::with(['parent:id,name,slug', 'media'])
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

    public string $deletingName = '';

    private int $deletingId = 0;

    public function confirmDelete(int $id): void
    {
        $cat = Category::findOrFail($id);
        $this->deletingId = $id;
        $this->deletingName = $cat->name;
        Flux::modal('delete-category')->show();
    }

    public function delete(): void
    {
        $cat = Category::withCount('products')->findOrFail($this->deletingId);

        Flux::modal('delete-category')->close();

        if ($cat->products_count > 0) {
            Flux::toast(heading: 'Cannot delete', text: $cat->name.' has '.$cat->products_count.' products attached.', variant: 'danger');

            return;
        }

        $cat->delete();
        unset($this->categories);
        Flux::toast(heading: 'Category deleted', text: $cat->name.' has been removed.', variant: 'success');
    }

    public function bulkSetStatus(string $status): void
    {
        if ($this->selected === [] || ! in_array($status, array_column(CategoryStatus::cases(), 'value'), true)) {
            return;
        }

        $count = Category::whereIn('id', $this->selected)->update(['status' => $status]);
        $this->afterBulk();

        Flux::toast(heading: 'Status updated', text: $count.' '.str('category')->plural($count).' set to '.CategoryStatus::from($status)->label().'.', variant: 'success');
    }

    public function bulkDelete(): void
    {
        if ($this->selected === []) {
            return;
        }

        Flux::modal('bulk-delete-categories')->show();
    }

    public function confirmBulkDelete(): void
    {
        $cats = Category::withCount('products')->whereIn('id', $this->selected)->get();
        $deletable = $cats->where('products_count', 0);
        $skipped = $cats->where('products_count', '>', 0)->count();

        $deletable->each->delete();
        $this->afterBulk();
        Flux::modal('bulk-delete-categories')->close();

        $msg = $deletable->count().' '.str('category')->plural($deletable->count()).' deleted.';
        if ($skipped > 0) {
            $msg .= " {$skipped} skipped (products attached).";
        }

        Flux::toast(heading: 'Bulk delete', text: $msg, variant: $deletable->count() > 0 ? 'success' : 'warning');
    }

    private function afterBulk(): void
    {
        $this->clearSelection();
        unset($this->categories);
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

        {{-- Bulk action bar --}}
        @if (count($selected) > 0)
            <div class="flex flex-wrap items-center gap-3 border-b border-zinc-200 bg-brand-50 px-6 py-2.5 dark:border-zinc-700 dark:bg-brand-500/10">
                <flux:text class="font-medium">{{ count($selected) }} selected</flux:text>

                <flux:dropdown>
                    <flux:button size="sm" variant="ghost" icon-trailing="chevron-down">Set status</flux:button>
                    <flux:menu>
                        @foreach (CategoryStatus::cases() as $s)
                            <flux:menu.item wire:click="bulkSetStatus('{{ $s->value }}')">{{ $s->label() }}</flux:menu.item>
                        @endforeach
                    </flux:menu>
                </flux:dropdown>

                <flux:button size="sm" variant="ghost" icon="trash-2" wire:click="bulkDelete"
                    class="text-red-500! hover:text-red-600!">Delete</flux:button>

                <flux:spacer />

                <flux:button size="sm" variant="ghost" wire:click="clearSelection">Clear</flux:button>
            </div>
        @endif

        <flux:table
            container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
            <flux:table.columns class="bg-zinc-50 dark:bg-zinc-800/60">
                <flux:table.column class="w-10">
                    <flux:checkbox wire:model.live="selectAll" />
                </flux:table.column>
                <flux:table.column>Category</flux:table.column>
                <flux:table.column>Slug</flux:table.column>
                <flux:table.column>Parent</flux:table.column>
                <flux:table.column>Products</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->categories as $category)
                    <flux:table.row :key="$category->id">
                        <flux:table.cell>
                            <flux:checkbox wire:model.live="selected" value="{{ $category->id }}" />
                        </flux:table.cell>
                        <flux:table.cell variant="strong">
                            <div class="flex items-center gap-3">
                                @if ($category->image_thumb_url)
                                    <img src="{{ $category->image_thumb_url }}" alt=""
                                        class="size-10 shrink-0 rounded-md object-cover" />
                                @else
                                    <div class="flex size-10 shrink-0 items-center justify-center rounded-md bg-zinc-100 dark:bg-zinc-800">
                                        <flux:icon.photo variant="micro" class="size-4 text-zinc-400" />
                                    </div>
                                @endif
                                <span>{{ $category->name }}</span>
                            </div>
                        </flux:table.cell>
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
                                    <flux:menu.item icon="pencil-square" icon-variant="outline"
                                        :href="route('admin.categories.edit', $category)" wire:navigate>
                                        Edit
                                    </flux:menu.item>
                                    <flux:menu.item icon="document-duplicate" icon-variant="outline"
                                        wire:click="duplicateCategory({{ $category->id }})">
                                        Duplicate
                                    </flux:menu.item>
                                    <flux:menu.item icon="clock" icon-variant="outline"
                                        :href="route('admin.activity.item', ['category', $category->id])"
                                        wire:navigate>
                                        Activity log
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item icon="trash-2" icon-variant="outline" variant="danger"
                                        wire:click="confirmDelete({{ $category->id }})">
                                        Delete
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="py-12 text-center text-zinc-400">
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

    <flux:modal name="bulk-delete-categories" class="max-w-sm">
        <flux:heading class="uppercase tracking-wide">Delete categories</flux:heading>
        <flux:subheading class="mt-1">Delete <strong>{{ count($selected) }}</strong> selected {{ str('category')->plural(count($selected)) }}? Categories with products attached will be skipped.</flux:subheading>
        <div class="mt-6 flex justify-end gap-3">
            <flux:modal.close>
                <flux:button variant="ghost">Cancel</flux:button>
            </flux:modal.close>
            <flux:button variant="danger" wire:click="confirmBulkDelete">Delete</flux:button>
        </div>
    </flux:modal>

    <flux:modal name="delete-category" class="max-w-sm">
        <flux:heading class="uppercase tracking-wide">Delete category</flux:heading>
        <flux:subheading class="mt-1">Are you sure you want to delete <strong>{{ $deletingName }}</strong>? This cannot be undone.</flux:subheading>
        <div class="mt-6 flex justify-end gap-3">
            <flux:modal.close>
                <flux:button variant="ghost">Cancel</flux:button>
            </flux:modal.close>
            <flux:button variant="danger" wire:click="delete">Delete</flux:button>
        </div>
    </flux:modal>
</div>
