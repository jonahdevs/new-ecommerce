<?php

use App\Models\Category;
use App\Livewire\Forms\Admin\CategoryForm;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Computed;

new class extends Component {
    use WithFileUploads;

    public CategoryForm $form;
    public Category $category;

    public function mount(Category $category)
    {
        $this->category = $category;
        $this->form->setCategory($category);
    }

    public function save()
    {
        try {
            //code...
            $this->form->update();
            $this->dispatch('notify', variant: 'success', message: 'Category updated successfully!');

            $this->redirectRoute('admin.categories', navigate: true);
        } catch (\Throwable $th) {
            \Log::error('Error updating category: ' . $th->getMessage(), ['exception' => $th]);
            session()->flash('status', 'An error occurred while updating the category.');
            $this->dispatch('notify', variant: 'danger', message: 'Failed to update category. Please try again.');
        }
    }

    #[Computed]
    public function parents()
    {
        return Category::where('id', '!=', $this->category->id)->orderBy('name')->get();
    }
}; ?>

<div>
    <flux:heading size="xl" class="mb-2">Edit Category: {{ $category->name }}</flux:heading>

    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('dashboard')" icon="home" icon-variant="outline"></flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('admin.categories')">Categories</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Edit</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <form wire:submit="save" class="space-y-8 mt-6">
        @include('pages.admin.catalog.categories._form-fields')

        <div class="flex justify-end gap-3 p-4 bg-zinc-50 dark:bg-zinc-800 rounded-xl border">
            <flux:button variant="ghost" href="{{ route('admin.categories') }}" class="cursor-pointer">Discard Changes
            </flux:button>
            <flux:button type="submit" variant="primary" class="cursor-pointer">Save Category</flux:button>
        </div>
    </form>
</div>
