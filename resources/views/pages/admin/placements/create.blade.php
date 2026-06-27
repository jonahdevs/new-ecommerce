<?php

use App\Enums\CategorySection;
use App\Enums\CategoryStatus;
use App\Models\Category;
use App\Models\CategoryPlacement;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Add Placement | Admin')] class extends Component
{
    #[Url]
    public string $location = '';

    public ?int $category_id = null;

    public int $sort_order = 0;

    public string $status = 'active';

    public function mount(): void
    {
        // Pre-select location when arriving via "Add to section" shortcut
        if ($this->location && ! CategorySection::tryFrom($this->location)) {
            $this->location = '';
        }
    }

    #[Computed]
    public function categoryOptions()
    {
        return Category::where('status', CategoryStatus::ACTIVE)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function save(): void
    {
        $this->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'location' => ['required', Rule::in(array_column(CategorySection::cases(), 'value'))],
            'sort_order' => ['integer', 'min:0', 'max:9999'],
            'status' => ['required', Rule::in(array_column(CategoryStatus::cases(), 'value'))],
        ]);

        $this->validate([
            'category_id' => [Rule::unique('category_placements', 'category_id')->where('location', $this->location)],
        ], [
            'category_id.unique' => 'This category is already placed in that section.',
        ]);

        CategoryPlacement::create([
            'category_id' => $this->category_id,
            'location' => $this->location,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
        ]);

        Flux::toast(heading: 'Placement added', text: 'The category has been assigned to the section.', variant: 'success');

        $this->redirect(route('admin.placements.index'), navigate: true);
    }
}; ?>

<div>
    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('admin.categories.index')" wire:navigate>Categories</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('admin.placements.index')" wire:navigate>Placements</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Add placement</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <form wire:submit="save">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="xl">Add placement</flux:heading>
                <flux:subheading>Assign a category to a storefront section.</flux:subheading>
            </div>
            <div class="flex items-center gap-3">
                <flux:button :href="route('admin.placements.index')" wire:navigate variant="ghost">Cancel</flux:button>
                <flux:button type="submit" variant="primary">Save placement</flux:button>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- Main --}}
            <div class="space-y-6 lg:col-span-2">
                <flux:card class="space-y-4 p-6">
                    <flux:select wire:model="category_id" label="Category" placeholder="Select a category…">
                        @foreach ($this->categoryOptions as $cat)
                            <flux:select.option :value="$cat->id">{{ $cat->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    @error('category_id') <flux:error>{{ $message }}</flux:error> @enderror

                    <flux:select wire:model="location" label="Section">
                        <flux:select.option value="" disabled>Select a section…</flux:select.option>
                        @foreach (CategorySection::cases() as $section)
                            <flux:select.option :value="$section->value">{{ $section->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    @error('location') <flux:error>{{ $message }}</flux:error> @enderror
                </flux:card>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                <flux:card class="space-y-4 p-6">
                    <flux:select wire:model="status" label="Status">
                        @foreach (CategoryStatus::cases() as $s)
                            <flux:select.option :value="$s->value">{{ $s->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:input wire:model="sort_order" label="Sort order" type="number" min="0" max="9999"
                        description="Lower numbers appear first within the section." />
                </flux:card>
            </div>

        </div>
    </form>
</div>
