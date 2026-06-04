<?php

use App\Enums\CategoryStatus;
use App\Models\Category;
use Flux\Flux;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::app')] #[Title('Categories — Admin')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $filterStatus = '';

    #[Url]
    public int $perPage = 10;

    public bool $showModal = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $slug = '';
    public ?int $parent_id = null;
    public string $status = 'draft';
    public int $sort_order = 0;
    public string $description = '';

    private bool $slugManuallyEdited = false;

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
        return Category::with('parent')->withCount('products')->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%'))->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))->orderBy('sort_order')->orderBy('name')->paginate($this->perPage);
    }

    #[Computed]
    public function parentOptions()
    {
        return Category::whereNull('parent_id')
            ->when($this->editingId, fn($q) => $q->where('id', '!=', $this->editingId))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function updatedName(): void
    {
        if (!$this->slugManuallyEdited) {
            $this->slug = Str::slug($this->name);
        }
    }

    public function updatedSlug(): void
    {
        $this->slugManuallyEdited = true;
        $this->slug = Str::slug($this->slug);
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'name', 'slug', 'parent_id', 'description']);
        $this->status = 'draft';
        $this->sort_order = 0;
        $this->slugManuallyEdited = false;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $cat = Category::findOrFail($id);
        $this->editingId = $id;
        $this->name = $cat->name;
        $this->slug = $cat->slug;
        $this->parent_id = $cat->parent_id;
        $this->status = $cat->status->value;
        $this->sort_order = $cat->sort_order;
        $this->description = (string) $cat->description;
        $this->slugManuallyEdited = true;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('categories', 'slug')->ignore($this->editingId)],
            'status' => ['required', Rule::in(array_column(CategoryStatus::cases(), 'value'))],
            'parent_id' => ['nullable', 'exists:categories,id'],
        ]);

        $data = [
            'name' => $this->name,
            'slug' => $this->slug,
            'parent_id' => $this->parent_id ?: null,
            'status' => $this->status,
            'sort_order' => $this->sort_order,
            'description' => $this->description ?: null,
        ];

        if ($this->editingId) {
            Category::findOrFail($this->editingId)->update($data);
            Flux::toast(heading: 'Category updated', text: $this->name . ' has been saved.', variant: 'success');
        } else {
            Category::create($data);
            Flux::toast(heading: 'Category created', text: $this->name . ' has been added.', variant: 'success');
        }

        $this->showModal = false;
        unset($this->categories);
    }

    public function toggleStatus(int $id): void
    {
        $cat = Category::findOrFail($id);
        $cat->update([
            'status' => $cat->status === CategoryStatus::ACTIVE ? CategoryStatus::INACTIVE : CategoryStatus::ACTIVE,
        ]);
        unset($this->categories);
    }

    public function delete(int $id): void
    {
        $cat = Category::withCount('products')->findOrFail($id);

        if ($cat->products_count > 0) {
            Flux::toast(heading: 'Cannot delete', text: $cat->name . ' has ' . $cat->products_count . ' products attached.', variant: 'danger');

            return;
        }

        $cat->delete();
        unset($this->categories);
        Flux::toast(heading: 'Category deleted', text: $cat->name . ' has been removed.', variant: 'success');
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
        <flux:button variant="primary" icon="plus" wire:click="openCreate">Add category</flux:button>
    </div>

    <flux:card class="mt-6 p-0 overflow-hidden">

        {{-- Toolbar --}}
        <div class="flex items-center justify-between gap-4 border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search categories…" icon="magnifying-glass"
                clearable class="max-w-xs" />

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
                <flux:table.column>Parent</flux:table.column>
                <flux:table.column>Products</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->categories as $category)
                    <flux:table.row :key="$category->id">
                        <flux:table.cell variant="strong">
                            {{ $category->name }}
                            <span class="block font-mono text-xs font-normal text-zinc-400">{{ $category->slug }}</span>
                        </flux:table.cell>
                        <flux:table.cell class="text-zinc-500">
                            {{ $category->parent?->name ?? '—' }}
                        </flux:table.cell>
                        <flux:table.cell class="tabular-nums">
                            {{ $category->products_count }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <button wire:click="toggleStatus({{ $category->id }})">
                                <flux:badge size="sm" inset="top bottom" :color="$category->status->color()">
                                    {{ $category->status->label() }}
                                </flux:badge>
                            </button>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="flex items-center justify-end gap-1">
                                <flux:tooltip content="Activity log">
                                    <flux:button size="xs" variant="ghost" icon="clock"
                                        :href="route('admin.activity.item', ['category', $category->id])"
                                        wire:navigate />
                                </flux:tooltip>
                                <flux:button size="xs" variant="ghost" icon="pencil-square"
                                    wire:click="openEdit({{ $category->id }})" />
                                <flux:button size="xs" variant="ghost" icon="trash"
                                    wire:click="delete({{ $category->id }})"
                                    wire:confirm="Delete '{{ addslashes($category->name) }}'?"
                                    class="text-red-500! hover:text-red-600!" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="py-12 text-center text-zinc-400">
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

    {{-- Modal --}}
    <flux:modal wire:model.self="showModal" class="md:w-[520px]" :dismissible="false">
        <flux:heading>{{ $editingId ? 'Edit category' : 'New category' }}</flux:heading>

        <form wire:submit="save" class="mt-5 space-y-4">
            <flux:input wire:model.live.debounce.400ms="name" label="Name" placeholder="e.g. Cooking Ranges"
                required />
            <flux:input wire:model.blur="slug" label="Slug" description="Auto-generated from name." />

            <div class="grid grid-cols-2 gap-4">
                <flux:select wire:model="parent_id" label="Parent category">
                    <flux:select.option value="">No parent</flux:select.option>
                    @foreach ($this->parentOptions as $opt)
                        <flux:select.option :value="$opt->id">{{ $opt->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="status" label="Status">
                    @foreach (CategoryStatus::cases() as $s)
                        <flux:select.option :value="$s->value">{{ $s->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="sort_order" label="Sort order" type="number" min="0" />
            </div>

            <flux:textarea wire:model="description" label="Description" rows="3" />

            <div class="flex justify-end gap-3 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">
                    {{ $editingId ? 'Save changes' : 'Create category' }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
