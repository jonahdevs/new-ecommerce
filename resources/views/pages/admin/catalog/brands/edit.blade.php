<?php
use App\Models\Brand;
use App\Livewire\Forms\Admin\BrandForm;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public BrandForm $form;
    public Brand $brand;

    public function mount(Brand $brand)
    {
        $this->brand = $brand;
        $this->form->setBrand($brand);
    }

    public function save()
    {
        try {
            $this->form->update();
            $this->dispatch('notify', variant: 'success', message: 'Brand updated successfully!');
            $this->redirectRoute('admin.brands', navigate: true);
        } catch (\Throwable $th) {
            \Log::error('Error updating brand: ' . $th->getMessage(), ['exception' => $th]);
            $this->dispatch('notify', variant: 'danger', message: 'Failed to update brand. Please try again.');
        }
    }
}; ?>

<div>
    <flux:heading size="xl" class="mb-2">Edit Brand: {{ $brand->name }}</flux:heading>

    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('dashboard')" icon="home" icon-variant="outline"></flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('admin.brands')">Brands</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Edit</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <form wire:submit="save" class="space-y-8 mt-6">
        @include('pages.admin.catalog.brands._form-fields')

        <div class="flex justify-end gap-3 p-4 bg-zinc-50 dark:bg-zinc-800 rounded-xl border">
            <flux:button variant="ghost" href="{{ route('admin.brands') }}" class="cursor-pointer">
                Discard Changes
            </flux:button>
            <flux:button type="submit" variant="primary" class="cursor-pointer">
                Save Brand
            </flux:button>
        </div>
    </form>
</div>
