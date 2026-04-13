<?php

use App\Livewire\Concerns\ManagesProductForm;
use App\Livewire\Forms\Admin\ProductForm;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Create Product')] class extends Component {
    use WithFileUploads, ManagesProductForm;

    public ProductForm $form;

    public function updatedFormName(): void
    {
        if (empty($this->form->slug)) {
            $this->form->slug = Str::slug($this->form->name);
        }
    }

    #[Computed]
    public function brands()
    {
        return Brand::orderBy('name')->get();
    }

    #[Computed]
    public function categories()
    {
        return Category::orderBy('name')->get();
    }

    public function removeNewImage(int $index): void
    {
        array_splice($this->form->new_images, $index, 1);
    }

    public function save(): void
    {
        try {
            $this->authorize('create', Product::class);

            $this->processVariantImages();

            $product = $this->form->store();

            $this->dispatch('notify', title: 'Product Created', variant: 'success', message: 'Product created successfully.');

            $this->redirectRoute('admin.catalog.products.edit', ['product' => $product->id], navigate: true);
        } catch (ValidationException $e) {
            $this->dispatch('notify', title: 'Validation Error', variant: 'warning', message: 'Please correct the highlighted fields.');
            throw $e;
        } catch (\Throwable $th) {
            \Log::error('Error creating product.', ['exception' => $th]);
            $this->dispatch('notify', title: 'Creation Failed', variant: 'danger', message: 'Failed to create product. Please try again.');
        }
    }
}; ?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate />
        <flux:breadcrumbs.item :href="route('admin.catalog.products.index')" wire:navigate>Products</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Create</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="mt-2 mb-6">
        <flux:heading size="xl">Create New Product</flux:heading>
        <flux:subheading>Add a new product to your catalogue.</flux:subheading>
    </div>

    <form wire:submit="save" class="space-y-5">
        @include('pages.admin.catalog.products._form-fields')

        <flux:card class="flex justify-end gap-3 p-4 bg-zinc-50 dark:bg-zinc-900">
            <flux:button variant="ghost" :href="route('admin.catalog.products.index')" wire:navigate class="cursor-pointer">
                Cancel
            </flux:button>
            <flux:button type="submit" variant="primary" class="cursor-pointer min-w-36"
                wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">Create Product</span>
                <span wire:loading wire:target="save">Saving...</span>
            </flux:button>
        </flux:card>
    </form>
</div>
