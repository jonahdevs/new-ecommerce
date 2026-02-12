<?php
use App\Models\Category;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\{Title, Computed};

new #[Title('Categories')] class extends Component {
    use WithPagination;

    public $search = '';
    public ?int $categoryToDelete = null;
    public ?string $categoryNameToDelete = null;

    public function confirmDelete($id, $name)
    {
        $this->categoryToDelete = $id;
        $this->categoryNameToDelete = $name;
        $this->modal('delete-category')->show();
    }

    public function delete()
    {
        try {
            if ($this->categoryToDelete) {
                $category = Category::findOrFail($this->categoryToDelete);
                $category->delete();
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

    #[Computed]
    public function categories()
    {
        return Category::query()
            ->with(['parent'])
            ->withCount('children')
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('sort_order')
            ->paginate(15);
    }
}; ?>

<div>
    <div class="flex justify-between items-center mb-6">
        <div>
            <flux:heading size="xl" class="mb-2">Categories</flux:heading>
            <flux:subheading>Organize and manage product categories to structure your catalog.</flux:subheading>
        </div>

        <flux:modal.trigger name="category-form">
            <flux:button variant="primary" icon="plus" :href="route('admin.categories.create')" wire:navigate>Create
                Category</flux:button>
        </flux:modal.trigger>
    </div>

    <div class="mb-4">
        <flux:input wire:model.live="search" icon="magnifying-glass" placeholder="Search categories..." class="max-w-md"
            clearable />
    </div>

    <flux:table :paginate="$this->categories">
        <flux:table.columns>
            <flux:table.column>Name</flux:table.column>
            <flux:table.column>Parent</flux:table.column>
            <flux:table.column>Children</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Featured</flux:table.column>
            <flux:table.column align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->categories as $category)
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
                            :href="route('admin.categories.edit', $category->id)" wire:navigate icon-variant="outline"
                            class="cursor-pointer text-sheffield-blue!" title="Edit Category" />

                        <flux:button variant="ghost" size="sm" icon="trash" color="red"
                            wire:click="confirmDelete({{ $category->id }}, '{{ $category->name }}')"
                            class="cursor-pointer" icon-variant="outline" class="text-red-500! cursor-pointer"
                            title="Delete Category" />
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    {{-- Delete Confirmation Modal --}}
    <flux:modal name="delete-category" class="md:w-96">
        <flux:heading size="lg" class="mb-2">Delete Category</flux:heading>

        <form wire:submit="delete" class="space-y-6">
            <div>
                <flux:subheading>
                    @if ($categoryNameToDelete)
                        <p class="mt-2">Are you sure you want to delete <strong>{{ $categoryNameToDelete }}</strong>?
                        </p>
                        <p class="mt-1 text-sm text-red-600">This action cannot be undone. All child categories will
                            also be deleted.</p>
                    @endif
                </flux:subheading>
            </div>

            <div class="flex gap-2 justify-evenly items-center">
                <flux:button type="button" variant="ghost" wire:click="$dispatch('modal-close', 'delete-category')"
                    class="cursor-pointer w-full">
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="danger" class="cursor-pointer w-full">
                    Delete Category
                </flux:button>
            </div>
        </form>
    </flux:modal>

</div>
