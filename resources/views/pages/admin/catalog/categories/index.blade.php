<?php
use App\Models\Category;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Title;

new #[Title('Categories')] class extends Component {
    use WithPagination;

    public $search = '';

    public function delete($id)
    {
        $category = Category::findOrFail($id);
        // Safety check: handle children before deletion
        $category->delete();
    }

    public function with()
    {
        return [
            'categories' => Category::query()
                ->with(['parent'])
                ->withCount('children')
                ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->orderBy('sort_order')
                ->paginate(15),
        ];
    }
}; ?>

<div>
    <div class="flex justify-between items-center mb-6">
        <div>
            <flux:heading size="xl">Categories</flux:heading>
            <flux:subheading>Manage your product hierarchy and SEO</flux:subheading>
        </div>

        <flux:modal.trigger name="category-form">
            <flux:button variant="primary" icon="plus" :href="route('admin.categories.create')" wire:navigate>Create
                Category</flux:button>
        </flux:modal.trigger>
    </div>

    <div class="mb-4">
        <flux:input wire:model.live="search" icon="magnifying-glass" placeholder="Search categories..." />
    </div>

    <flux:table :paginate="$categories">
        <flux:table.columns>
            <flux:table.column>Name</flux:table.column>
            <flux:table.column>Parent</flux:table.column>
            <flux:table.column>Children</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Featured</flux:table.column>
            <flux:table.column align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($categories as $category)
                <flux:table.row :key="$category->id">
                    <flux:table.cell class="flex items-center gap-3">
                        @if ($category->image_icon)
                            <img src="{{ asset('storage/' . $category->image_icon) }}" class="w-8 h-8 rounded">
                        @else
                            <div class="w-8 h-8 bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center rounded">
                                <flux:icon name="folder" variant="micro" />
                            </div>
                        @endif

                        <div>
                            <span class="font-medium text-zinc-800 dark:text-white">{{ $category->name }}</span>
                            <div class="text-xs text-zinc-500">{{ $category->slug }}</div>
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:badge size="sm" variant="outline">
                            {{ $category->parent?->name ?? 'Root' }}
                        </flux:badge>
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:badge size="sm" color="zinc" variant="subtle">
                            {{ $category->children_count }} sub-categories
                        </flux:badge>
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:badge size="sm" :color="$category->is_active ? 'green' : 'red'" variant="flat">
                            {{ $category->is_active ? 'Active' : 'Inactive' }}
                        </flux:badge>
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:icon name="{{ $category->is_featured ? 'star' : 'minus' }}" variant="micro"
                            class="{{ $category->is_featured ? 'text-yellow-500' : 'text-zinc-300' }}" />
                    </flux:table.cell>

                    <flux:table.cell align="end">
                        <flux:button variant="ghost" size="sm" icon="pencil-square"
                            :href="route('admin.categories.edit', $category->id)" wire:navigate />

                        <flux:button variant="ghost" size="sm" icon="trash" color="red"
                            wire:confirm="Deleting a parent category will delete all children. Continue?"
                            wire:click="delete({{ $category->id }})" />
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    {{-- <livewire:admin.categories.category-form /> --}}
</div>
