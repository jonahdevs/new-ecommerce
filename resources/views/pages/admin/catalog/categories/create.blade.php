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

    public function save()
    {
        try {
            $this->form->store();
            $this->dispatch('notify', variant: 'success', message: 'Category created successfully!');
            $this->redirectRoute('admin.categories.index', navigate: true);
        } catch (ValidationException $e) {
            $this->dispatch('notify', variant: 'warning', message: 'Please correct the highlighted fields and try again.');
            throw $e;
        } catch (\Throwable $th) {
            \Log::error('Error creating category: ' . $th->getMessage(), ['exception' => $th]);
            $this->dispatch('notify', variant: 'danger', message: 'Failed to create category. Please try again.');
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
        <flux:breadcrumbs.item :href="route('dashboard')" icon="home" icon-variant="outline" wire:navigate>
        </flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('admin.categories.index')" wire:navigate>Categories</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Create</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl">Create New Category</flux:heading>


    <form wire:submit="save" class="space-y-5 mt-6">
        @include('pages.admin.catalog.categories._form-fields')

        <flux:card class="bg-zinc-50 flex justify-end gap-3">
            <flux:button variant="ghost" href="{{ route('admin.categories.index') }}" class="cursor-pointer">Cancel
            </flux:button>
            <flux:button type="submit" variant="primary" class="cursor-pointer">Create Category</flux:button>
        </flux:card>
    </form>
</div>
