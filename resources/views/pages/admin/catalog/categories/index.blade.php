<?php

use App\Enums\CategorySection;
use App\Enums\CategoryStatus;
use App\Models\Category;
use App\Models\CategoryPlacement;
use Livewire\Attributes\{Title, Computed, Url};
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Categories')] class extends Component {
    use WithPagination;

    #[Url]
    public string $tab = 'all';

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    public ?int $categoryToDelete = null;
    public ?string $categoryNameToDelete = null;

    public function selectTab(string $tab): void
    {
        $this->tab = $tab;
        $this->resetPage();
        $this->search = '';
        $this->statusFilter = '';
    }

    // -----------------------------------------------
    // All tab — paginated searchable table
    // -----------------------------------------------

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function categories()
    {
        return Category::query()
            ->with(['parent', 'placements'])
            ->withCount('children')
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(15);
    }

    // -----------------------------------------------
    // Section tabs — sortable placements
    // -----------------------------------------------

    #[Computed]
    public function sectionPlacements()
    {
        return CategoryPlacement::with('category')->where('section', $this->tab)->orderBy('sort_order')->get();
    }

    #[Computed]
    public function availableCategories()
    {
        $assignedIds = $this->sectionPlacements->pluck('category_id');

        return Category::active()
            ->whereNotIn('id', $assignedIds)
            ->orderBy('name')
            ->get(['id', 'name', 'image_icon']);
    }

    public function reorder(string $id, string $position): void
    {
        try {
            $placements = CategoryPlacement::where('section', $this->tab)->orderBy('sort_order')->get();

            $dragged = $placements->firstWhere('id', $id);
            $reordered = $placements->filter(fn($p) => $p->id != $id)->values();
            $reordered->splice((int) $position, 0, [$dragged]);

            // Single query instead of N updates
            $cases = $reordered->map(fn($p, $index) => "WHEN {$p->id} THEN {$index}")->join(' ');
            $ids = $reordered->pluck('id')->join(',');

            \DB::statement("UPDATE category_placements SET sort_order = CASE id {$cases} END WHERE id IN ({$ids})");

            unset($this->sectionPlacements);
        } catch (\Throwable $th) {
            \Log::error('Category placement sort failed.', [
                'section' => $this->tab,
                'exception' => $th->getMessage(),
            ]);
            $this->dispatch('notify', variant: 'danger', message: 'Failed to save order.');
        }
    }

    public function addPlacement(int $categoryId): void
    {
        $exists = CategoryPlacement::where('category_id', $categoryId)->where('section', $this->tab)->exists();

        if ($exists) {
            $this->dispatch('notify', variant: 'warning', message: 'Category is already in this section.');
            return;
        }

        $maxOrder = CategoryPlacement::where('section', $this->tab)->max('sort_order') ?? 0;

        CategoryPlacement::create([
            'category_id' => $categoryId,
            'section' => CategorySection::from($this->tab),
            'sort_order' => $maxOrder + 1,
        ]);

        unset($this->sectionPlacements, $this->availableCategories);
        $this->dispatch('notify', variant: 'success', message: 'Category added to section.');
    }

    public function removePlacement(int $placementId): void
    {
        CategoryPlacement::findOrFail($placementId)->delete();
        unset($this->sectionPlacements, $this->availableCategories);
        $this->dispatch('notify', variant: 'success', message: 'Category removed from section.');
    }

    // -----------------------------------------------
    // Delete (all tab)
    // -----------------------------------------------

    public function confirmDelete(int $id, string $name): void
    {
        $this->categoryToDelete = $id;
        $this->categoryNameToDelete = $name;
        $this->modal('delete-category')->show();
    }

    public function delete(): void
    {
        try {
            if ($this->categoryToDelete) {
                Category::findOrFail($this->categoryToDelete)->delete();
                $this->modal('delete-category')->close();
                $this->dispatch('notify', variant: 'success', message: 'Category deleted successfully!');
                $this->categoryToDelete = null;
                $this->categoryNameToDelete = null;
            }
        } catch (\Throwable $th) {
            \Log::error('Error deleting category: ' . $th->getMessage(), ['exception' => $th]);
            $this->dispatch('notify', variant: 'danger', message: 'Failed to delete category.');
        }
    }

    // -----------------------------------------------
    // Helpers
    // -----------------------------------------------

    public function isSection(): bool
    {
        return $this->tab !== 'all';
    }

    #[Computed]
    public function currentSection(): ?CategorySection
    {
        if (!$this->isSection()) {
            return null;
        }
        return CategorySection::from($this->tab);
    }

    #[Computed]
    public function sectionCounts(): array
    {
        return CategoryPlacement::selectRaw('section, count(*) as count')->groupBy('section')->pluck('count', 'section')->toArray();
    }
}; ?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate />
        <flux:breadcrumbs.item>Categories</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex justify-between items-center mb-6 mt-2">
        <div>
            <flux:heading size="xl" class="mb-1">Categories</flux:heading>
            <flux:subheading>Manage your product categories and storefront placements.</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus-circle" :href="route('admin.categories.create')" wire:navigate>
            Create Category
        </flux:button>
    </div>

    {{-- Tabs --}}
    <div class="flex items-center gap-1 border-b mb-5 overflow-x-auto">

        {{-- All tab --}}
        <button type="button" wire:click="selectTab('all')" @class([
            'px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors whitespace-nowrap cursor-pointer',
            'border-sheffield-blue text-sheffield-blue' => $tab === 'all',
            'border-transparent text-zinc-500 hover:text-zinc-800' => $tab !== 'all',
        ])>
            All Categories
        </button>

        {{-- Section tabs --}}
        @foreach (\App\Enums\CategorySection::cases() as $section)
            <button type="button" wire:click="selectTab('{{ $section->value }}')" @class([
                'flex items-center gap-1.5 px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors whitespace-nowrap cursor-pointer',
                'border-sheffield-blue text-sheffield-blue' => $tab === $section->value,
                'border-transparent text-zinc-500 hover:text-zinc-800' =>
                    $tab !== $section->value,
            ])>
                {{ $section->label() }}
                @if (!empty($this->sectionCounts[$section->value]))
                    <span @class([
                        'text-xs px-1.5 py-0.5 rounded-full font-normal',
                        'bg-sheffield-blue text-white' => $tab === $section->value,
                        'bg-zinc-200 text-zinc-600' => $tab !== $section->value,
                    ])>{{ $this->sectionCounts[$section->value] }}</span>
                @endif
            </button>
        @endforeach
    </div>

    {{-- ================================================ --}}
    {{-- ALL TAB                                          --}}
    {{-- ================================================ --}}
    @if ($tab === 'all')
        <flux:card class="p-0 **:data-flux-columns:bg-zinc-50">

            {{-- Toolbar --}}
            <div class="px-5 py-3 border-b flex items-center gap-3">
                <flux:input wire:model.live="search" icon="magnifying-glass" placeholder="Search categories..."
                    class="max-w-sm" clearable />

                <div class="ms-auto">
                    <flux:select wire:model.live="statusFilter" class="w-40">
                        <flux:select.option value="">All Statuses</flux:select.option>
                        @foreach (\App\Enums\CategoryStatus::cases() as $status)
                            <flux:select.option value="{{ $status->value }}">
                                {{ $status->label() }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>

            <flux:table :paginate="$this->categories">
                <flux:table.columns>
                    <flux:table.column class="ps-4!">Name</flux:table.column>
                    <flux:table.column>Parent</flux:table.column>
                    <flux:table.column>Children</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Placements</flux:table.column>
                    <flux:table.column align="end" class="pe-4!">Actions</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->categories as $category)
                        <flux:table.row :key="$category->id">

                            <flux:table.cell class="flex items-center gap-3 ps-4!">
                                <div
                                    class="shrink-0 w-9 h-9 rounded border bg-zinc-50 flex items-center justify-center overflow-hidden">
                                    @if ($category->image_icon)
                                        <img src="{{ asset('storage/' . $category->image_icon) }}"
                                            class="w-full h-full object-contain p-0.5" />
                                    @else
                                        <flux:icon.folder variant="micro" class="text-zinc-400" />
                                    @endif
                                </div>
                                <div>
                                    <span class="font-medium text-zinc-800 dark:text-white">{{ $category->name }}</span>
                                    <div class="text-xs text-zinc-400">{{ $category->slug }}</div>
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:badge size="sm" variant="outline">
                                    {{ $category->parent?->name ?? 'Root' }}
                                </flux:badge>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:badge size="sm" color="zinc" variant="subtle">
                                    {{ $category->children_count }}
                                    {{ Str::plural('sub-category', $category->children_count) }}
                                </flux:badge>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:badge size="sm" :color="$category->status->color()" variant="flat">
                                    {{ $category->status->label() }}
                                </flux:badge>
                            </flux:table.cell>

                            <flux:table.cell>
                                @if ($category->placements->isEmpty())
                                    <span class="text-xs text-zinc-400">None</span>
                                @else
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($category->placements as $placement)
                                            <flux:badge size="sm" color="blue" variant="subtle">
                                                {{ $placement->section->label() }}
                                            </flux:badge>
                                        @endforeach
                                    </div>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell align="end" class="pe-4!">
                                <flux:button variant="ghost" size="sm" icon="pencil-square"
                                    :href="route('admin.categories.edit', $category->id)" wire:navigate
                                    icon-variant="outline" class="cursor-pointer text-sheffield-blue!" title="Edit" />

                                <flux:button variant="ghost" size="sm" icon="trash"
                                    wire:click="confirmDelete({{ $category->id }}, '{{ addslashes($category->name) }}')"
                                    icon-variant="outline" class="text-red-500! cursor-pointer" title="Delete" />
                            </flux:table.cell>

                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="text-center py-12 text-zinc-400">
                                <flux:icon.folder-open class="w-10 h-10 mx-auto mb-3 stroke-1" />
                                <p class="font-medium">No categories found</p>
                                <p class="text-sm mt-1">
                                    @if ($search || $statusFilter)
                                        Try adjusting your search or filter.
                                    @else
                                        Get started by creating your first category.
                                    @endif
                                </p>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
    @endif

    {{-- ================================================ --}}
    {{-- SECTION TABS                                     --}}
    {{-- ================================================ --}}
    @if ($this->isSection())
        <div class="flex gap-5">

            {{-- Sortable list --}}
            <div class="flex-1">
                <flux:card class="p-0">

                    {{-- Section toolbar --}}
                    <div class="px-5 py-3 border-b flex items-center justify-between">
                        <div>
                            <span class="font-medium">{{ $this->currentSection->label() }}</span>
                            <span class="text-sm text-zinc-500 ml-2">
                                {{ $this->sectionPlacements->count() }}
                                {{ Str::plural('category', $this->sectionPlacements->count()) }}
                            </span>
                        </div>

                        @if ($this->availableCategories->isNotEmpty())
                            <flux:dropdown>
                                <flux:button variant="primary" icon="plus-circle" size="sm" class="cursor-pointer">
                                    Add Category
                                </flux:button>
                                <flux:menu class="max-h-72! overflow-y-auto w-56">
                                    @foreach ($this->availableCategories as $cat)
                                        <flux:menu.item wire:click="addPlacement({{ $cat->id }})"
                                            wire:key="avail-{{ $cat->id }}">
                                            <div class="flex items-center gap-2">
                                                @if ($cat->image_icon)
                                                    <img src="{{ asset('storage/' . $cat->image_icon) }}"
                                                        class="w-5 h-5 object-contain rounded shrink-0" />
                                                @else
                                                    <flux:icon.folder variant="micro" class="text-zinc-400 shrink-0" />
                                                @endif
                                                {{ $cat->name }}
                                            </div>
                                        </flux:menu.item>
                                    @endforeach
                                </flux:menu>
                            </flux:dropdown>
                        @endif
                    </div>

                    <div wire:sort="reorder" class="divide-y">
                        @forelse ($this->sectionPlacements as $placement)
                            <div wire:sort:item="{{ $placement->id }}" wire:key="placement-{{ $placement->id }}"
                                class="flex items-center gap-3 px-4 py-3 hover:bg-zinc-50 group bg-white">

                                {{-- Drag handle --}}
                                <div wire:sort:handle
                                    class="cursor-grab active:cursor-grabbing text-zinc-300 hover:text-zinc-500 shrink-0">
                                    <flux:icon.bars-3 class="w-5 h-5" />
                                </div>

                                {{-- Position --}}
                                <span class="text-xs font-mono text-zinc-400 w-5 text-center shrink-0 tabular-nums">
                                    {{ $loop->iteration }}
                                </span>

                                {{-- Icon --}}
                                <div
                                    class="w-9 h-9 rounded border bg-zinc-50 flex items-center justify-center overflow-hidden shrink-0">
                                    @if ($placement->category->image_icon)
                                        <img src="{{ asset('storage/' . $placement->category->image_icon) }}"
                                            class="w-full h-full object-contain p-0.5" />
                                    @else
                                        <flux:icon.folder variant="micro" class="text-zinc-400" />
                                    @endif
                                </div>

                                {{-- Name & slug --}}
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-sm truncate">{{ $placement->category->name }}</p>
                                    <p class="text-xs text-zinc-400 truncate">{{ $placement->category->slug }}</p>
                                </div>

                                {{-- Status --}}
                                <flux:badge size="sm" :color="$placement->category->status->color()"
                                    variant="subtle">
                                    {{ $placement->category->status->label() }}
                                </flux:badge>

                                {{-- Edit shortcut --}}
                                <flux:button variant="ghost" size="sm" icon="pencil-square"
                                    :href="route('admin.categories.edit', $placement->category_id)" wire:navigate
                                    icon-variant="outline"
                                    class="opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer text-sheffield-blue!"
                                    title="Edit category" />

                                {{-- Remove from section --}}
                                <flux:button type="button" variant="ghost" size="sm" icon="x-mark"
                                    wire:click="removePlacement({{ $placement->id }})"
                                    wire:confirm="Remove '{{ addslashes($placement->category->name) }}' from this section?"
                                    class="opacity-0 group-hover:opacity-100 transition-opacity text-red-400! cursor-pointer"
                                    title="Remove from section" />

                            </div>

                        @empty
                            {{-- Empty state --}}
                            <div class="flex flex-col items-center justify-center py-16 text-zinc-400">
                                <flux:icon.squares-2x2 class="w-10 h-10 mb-3 stroke-1" />
                                <p class="font-medium text-sm">No categories in this section</p>
                                <p class="text-xs mt-1">Use "Add Category" above to assign categories here.</p>
                            </div>
                        @endforelse

                    </div>

                    <div class="px-4 py-2 border-t bg-zinc-50">
                        <p class="text-xs text-zinc-400">
                            Drag rows to reorder. Changes are saved automatically.
                        </p>
                    </div>


                </flux:card>
            </div>
        </div>
    @endif

    {{-- Delete Modal --}}
    <flux:modal name="delete-category" class="md:w-96">
        <flux:heading size="lg" class="mb-2">Delete Category</flux:heading>
        <form wire:submit="delete" class="space-y-6">
            <flux:subheading>
                @if ($categoryNameToDelete)
                    <p class="mt-2">Are you sure you want to delete
                        <strong>{{ $categoryNameToDelete }}</strong>?
                    </p>
                    <p class="mt-1 text-sm text-red-600">
                        This action cannot be undone. All child categories will also be deleted.
                    </p>
                @endif
            </flux:subheading>
            <div class="flex gap-2">
                <flux:button type="button" variant="ghost" class="cursor-pointer flex-1"
                    wire:click="modal('delete-category').close()">
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="danger" class="cursor-pointer flex-1">
                    Delete Category
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>

<style>
    [data-flux-pagination] {
        padding-inline: 1rem;
        padding-bottom: 1rem;
    }
</style>
