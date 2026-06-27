<?php

use App\Enums\CategorySection;
use App\Enums\CategoryStatus;
use App\Models\Category;
use App\Models\CategoryPlacement;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Manage Section | Admin')] class extends Component
{
    #[Locked]
    public CategorySection $section;

    public ?int $addCategoryId = null;

    public string $addStatus = 'active';

    public function mount(CategorySection $section): void
    {
        $this->section = $section;
    }

    #[Computed]
    public function placements(): Collection
    {
        return CategoryPlacement::with(['category.media'])
            ->where('location', $this->section->value)
            ->orderBy('sort_order')
            ->get();
    }

    /** Categories not yet placed in this section, for the add modal. */
    #[Computed]
    public function availableCategories(): Collection
    {
        $placed = $this->placements->pluck('category_id');

        return Category::where('status', CategoryStatus::ACTIVE)
            ->whereNotIn('id', $placed)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function handleSort(int $id, int $position): void
    {
        $items = CategoryPlacement::where('location', $this->section->value)
            ->orderBy('sort_order')
            ->get();

        $moving = $items->firstWhere('id', $id);

        if (! $moving) {
            return;
        }

        $reordered = $items->reject(fn ($p) => $p->id === $id)->values();
        $reordered->splice($position, 0, [$moving]);

        foreach ($reordered as $index => $placement) {
            $placement->update(['sort_order' => $index]);
        }

        unset($this->placements);
    }

    public function toggleStatus(int $id): void
    {
        $placement = CategoryPlacement::findOrFail($id);
        $placement->update([
            'status' => $placement->status === CategoryStatus::ACTIVE
                ? CategoryStatus::INACTIVE
                : CategoryStatus::ACTIVE,
        ]);
        unset($this->placements);
    }

    public function addCategory(): void
    {
        $this->validate([
            'addCategoryId' => ['required', 'exists:categories,id'],
            'addStatus' => ['required', 'in:active,inactive'],
        ]);

        $nextOrder = CategoryPlacement::where('location', $this->section->value)->max('sort_order') + 1;

        CategoryPlacement::create([
            'category_id' => $this->addCategoryId,
            'location' => $this->section->value,
            'sort_order' => $nextOrder,
            'status' => $this->addStatus,
        ]);

        $this->addCategoryId = null;
        $this->addStatus = 'active';

        unset($this->placements, $this->availableCategories);

        Flux::modal('add-category')->close();
        Flux::toast(heading: 'Category added', text: 'The category has been added to '.$this->section->label().'.', variant: 'success');
    }

    public function remove(int $id): void
    {
        $placement = CategoryPlacement::findOrFail($id);
        $name = $placement->category?->name ?? 'Category';
        $placement->delete();
        unset($this->placements, $this->availableCategories);
        Flux::toast(heading: 'Removed', text: $name.' removed from '.$this->section->label().'.', variant: 'success');
    }
}; ?>

<div>
    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('admin.categories.index')" wire:navigate>Categories</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('admin.placements.index')" wire:navigate>Placements</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $section->label() }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ $section->label() }}</flux:heading>
            <flux:subheading>{{ $section->description() }}</flux:subheading>
        </div>
        <flux:modal.trigger name="add-category">
            <flux:button variant="primary" icon="plus">Add category</flux:button>
        </flux:modal.trigger>
    </div>

    {{-- Sortable category list --}}
    <flux:card class="mt-6 overflow-hidden p-0">

        @if ($this->placements->isEmpty())
            <div class="flex flex-col items-center gap-3 py-16 text-center">
                <flux:icon.squares-plus variant="outline" class="size-10 text-zinc-300" />
                <div class="text-sm text-zinc-400">No categories in this section yet.</div>
                <flux:modal.trigger name="add-category">
                    <flux:button size="sm" variant="ghost" icon="plus">Add the first one</flux:button>
                </flux:modal.trigger>
            </div>
        @else
            {{-- Table header --}}
            <div class="grid grid-cols-[auto_1fr_auto_auto_auto] items-center gap-4 border-b border-zinc-200 bg-zinc-50 px-6 py-2.5 text-xs font-medium tracking-wider text-zinc-500 uppercase dark:border-zinc-700 dark:bg-zinc-800/60">
                <div class="w-4"></div>
                <div>Category</div>
                <div class="w-20 text-center">Order</div>
                <div class="w-20 text-center">Status</div>
                <div class="w-16"></div>
            </div>

            <div wire:sort="handleSort">
                @foreach ($this->placements as $placement)
                    <div wire:key="{{ $placement->id }}"
                        wire:sort:item="{{ $placement->id }}"
                        class="grid grid-cols-[auto_1fr_auto_auto_auto] items-center gap-4 border-b border-zinc-100 px-6 py-3 last:border-b-0 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/40">

                        {{-- Drag handle --}}
                        <div wire:sort:handle
                            class="w-4 cursor-grab text-zinc-300 active:cursor-grabbing dark:text-zinc-600">
                            <flux:icon.bars-2 variant="micro" class="size-4" />
                        </div>

                        {{-- Category --}}
                        <div class="flex min-w-0 items-center gap-3">
                            @if ($placement->category?->image_thumb_url)
                                <img src="{{ $placement->category->image_thumb_url }}" alt=""
                                    class="size-9 shrink-0 rounded object-cover" />
                            @else
                                <div class="flex size-9 shrink-0 items-center justify-center rounded bg-zinc-100 dark:bg-zinc-800">
                                    <flux:icon.photo variant="micro" class="size-4 text-zinc-400" />
                                </div>
                            @endif
                            <span class="truncate text-sm font-medium text-zinc-800 dark:text-zinc-200">
                                {{ $placement->category?->name ?? '—' }}
                            </span>
                        </div>

                        {{-- Sort order --}}
                        <div class="w-20 text-center text-sm tabular-nums text-zinc-400">
                            {{ $placement->sort_order }}
                        </div>

                        {{-- Status toggle --}}
                        <div class="w-20 flex justify-center" wire:sort:ignore>
                            <flux:switch
                                :checked="$placement->status === \App\Enums\CategoryStatus::ACTIVE"
                                wire:click="toggleStatus({{ $placement->id }})" />
                        </div>

                        {{-- Remove --}}
                        <div class="w-16 text-right" wire:sort:ignore>
                            <flux:button size="xs" variant="ghost" icon="trash-2" inset="right"
                                wire:click="remove({{ $placement->id }})"
                                wire:confirm="Remove {{ $placement->category?->name }} from {{ $section->label() }}?"
                                class="text-red-400 hover:text-red-600" />
                        </div>

                    </div>
                @endforeach
            </div>

            <div class="border-t border-zinc-100 px-6 py-3 dark:border-zinc-800">
                <flux:text size="sm" class="text-zinc-400">
                    {{ $this->placements->count() }} {{ str('category')->plural($this->placements->count()) }} · Drag rows to reorder
                </flux:text>
            </div>
        @endif

    </flux:card>

    {{-- Add category modal --}}
    <flux:modal name="add-category" class="w-full max-w-md">
        <flux:heading class="uppercase tracking-wide">Add category</flux:heading>
        <flux:subheading class="mt-1">Add a category to the {{ $section->label() }} section.</flux:subheading>

        <div class="mt-5 space-y-4">
            <flux:select wire:model="addCategoryId" label="Category" placeholder="Select a category…">
                @foreach ($this->availableCategories as $cat)
                    <flux:select.option :value="$cat->id">{{ $cat->name }}</flux:select.option>
                @endforeach
            </flux:select>
            @error('addCategoryId') <flux:error>{{ $message }}</flux:error> @enderror

            <flux:select wire:model="addStatus" label="Status">
                <flux:select.option value="active">Active</flux:select.option>
                <flux:select.option value="inactive">Inactive</flux:select.option>
            </flux:select>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <flux:modal.close>
                <flux:button variant="ghost">Cancel</flux:button>
            </flux:modal.close>
            <flux:button variant="primary" wire:click="addCategory">Add to section</flux:button>
        </div>
    </flux:modal>

</div>
