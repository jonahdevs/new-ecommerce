<?php
use App\Livewire\Forms\Admin\BrandForm;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public BrandForm $form;

    public function save()
    {
        try {
            $this->form->store();
            $this->dispatch('notify', variant: 'success', message: 'Brand created successfully!');
            $this->redirectRoute('admin.brands.index', navigate: true);
        } catch (\Throwable $th) {
            \Log::error('Error creating brand: ' . $th->getMessage(), ['exception' => $th]);
            $this->dispatch('notify', variant: 'danger', message: 'Failed to create brand. Please try again.');
        }
    }
}; ?>

<div>
    <flux:heading size="xl" class="mb-2">Create New Brand</flux:heading>

    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('dashboard')" icon="home" icon-variant="outline"></flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('admin.brands.index')">Brands</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Create</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <form wire:submit="save" class="space-y-8 mt-6">
        @include('pages.admin.catalog.brands._form-fields')

        <div class="flex justify-end gap-3 p-4 bg-zinc-50 dark:bg-zinc-800 rounded-xl border">
            <flux:button variant="ghost" href="{{ route('admin.brands') }}" class="cursor-pointer">
                Cancel
            </flux:button>
            <flux:button type="submit" variant="primary" class="cursor-pointer">
                Create Brand
            </flux:button>
        </div>
    </form>
</div>
