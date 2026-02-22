<?php
use App\Models\Brand;
use App\Livewire\Forms\Admin\BrandForm;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Validation\ValidationException;

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
            $this->redirectRoute('admin.brands.index', navigate: true);
        } catch (ValidationException $e) {
            $this->dispatch('notify', variant: 'warning', message: 'Please correct the highlighted fields and try again.');
            throw $e;
        } catch (\Throwable $th) {
            \Log::error('Error updating brand: ' . $th->getMessage(), ['exception' => $th]);
            $this->dispatch('notify', variant: 'danger', message: 'Failed to update brand. Please try again.');
        }
    }
}; ?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('dashboard')" icon="home" icon-variant="outline" wire:navigate>
        </flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('admin.brands.index')" wire:navigate>Brands</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Edit</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl">Edit Brand: {{ $brand->name }}</flux:heading>

    <form wire:submit="save" class="space-y-5 mt-6">
        @include('pages.admin.catalog.brands._form-fields')

        <flux:card class="flex justify-end gap-3 bg-zinc-50 dark:bg-zinc-800">
            <flux:button variant="ghost" href="{{ route('admin.brands.index') }}" class="cursor-pointer">
                Discard Changes
            </flux:button>
            <flux:button type="submit" variant="primary" class="cursor-pointer">
                Save Brand
            </flux:button>
        </flux:card>
    </form>
</div>
