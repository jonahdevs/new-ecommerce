<?php

use App\Models\Category;
use App\Livewire\Forms\Admin\CategoryForm;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Computed;
use Illuminate\Validation\ValidationException;

new class extends Component {
    use WithFileUploads;

    public CategoryForm $form;

    public function updatedFormName(string $value): void
    {
        // Auto-generate slug only if user hasn't manually set one
        if (empty($this->form->slug)) {
            $this->form->slug = \Illuminate\Support\Str::slug($value);
        }
    }

    public function save(): void
    {
        try {
            $this->form->store();

            $this->dispatch('notify', title: 'Category Created', variant: 'success', message: 'The category has been created successfully');

            $this->redirectRoute('admin.catalog.categories.index', navigate: true);
        } catch (ValidationException $e) {
            $this->dispatch('notify', title: 'Validation Error', variant: 'warning', message: 'Please correct the highlighted fields and try again');

            throw $e;
        } catch (\Throwable $th) {
            \Log::error('Error creating category: ' . $th->getMessage(), ['exception' => $th]);

            $this->dispatch('notify', title: 'Creation Failed', variant: 'danger', message: 'Failed to create category. Please try again');
        }
    }

    #[Computed]
    public function parents()
    {
        return Category::orderBy('name')->get();
    }
}; ?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate />
        <flux:breadcrumbs.item :href="route('admin.catalog.categories.index')" wire:navigate>Categories
        </flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Create</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="mt-2 mb-6">
        <flux:heading size="xl">Create New Category</flux:heading>
        <flux:subheading size="md" class="text-zinc-500">Add a new category to your store. You can edit it later if
            you need to.</flux:subheading>
    </div>

    <form wire:submit="save" class="space-y-5 mt-6">
        @include('pages.admin.catalog.categories._form-fields')

        <flux:card class="bg-zinc-50 dark:bg-zinc-900 flex justify-end gap-3">
            <flux:button type="button" variant="ghost" :href="route('admin.catalog.categories.index')" wire:navigate
                class="cursor-pointer">
                Cancel
            </flux:button>
            <flux:button type="submit" variant="primary" class="cursor-pointer">
                Create Category
            </flux:button>
        </flux:card>
    </form>
</div>
