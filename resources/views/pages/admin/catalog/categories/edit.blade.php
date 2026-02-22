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

            $this->redirectRoute('admin.categories.index', navigate: true);
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
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('dashboard')" icon="home" icon-variant="outline" wire:navigate>
        </flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('admin.categories.index')" wire:navigate>Categories</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Edit</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl">Edit Category</flux:heading>

    <form wire:submit="save" class="space-y-5 mt-6">
        @include('pages.admin.catalog.categories._form-fields')

        <flux:card class="flex justify-end gap-3 bg-zinc-50 dark:bg-zinc-800">
            <flux:button variant="ghost" href="{{ route('admin.categories.index') }}" class="cursor-pointer">Discard
                Changes
            </flux:button>
            <flux:button type="submit" variant="primary" class="cursor-pointer">Save Category</flux:button>
        </flux:card>
    </form>
</div>
