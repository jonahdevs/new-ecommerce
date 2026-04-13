<?php

use App\Livewire\Concerns\ManagesProductForm;
use App\Livewire\Forms\Admin\ProductForm;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Edit Product')] class extends Component {
    use WithFileUploads, ManagesProductForm;

    public ProductForm $form;
    public Product $product;

    public function mount(Product $product): void
    {
        $this->authorize('update', $product);

        $this->product = $product;
        $this->form->setProduct($product->load(
            'images',
            'categories',
            'upsells',
            'crossSells',
            'accessories',
            'attributes',
            'variants',
        ));
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

    public function removeExistingImage(int $imageId): void
    {
        $this->form->images_to_delete[] = $imageId;
        $this->form->existing_images = array_values(
            array_filter($this->form->existing_images, fn ($img) => $img['id'] !== $imageId)
        );
    }

    public function save(): void
    {
        try {
            $this->authorize('update', $this->product);

            $this->processVariantImages();

            $this->form->update();

            $this->dispatch('notify', title: 'Product Updated', variant: 'success', message: 'Product updated successfully.');

            $this->redirectRoute('admin.catalog.products.index', navigate: true);
        } catch (ValidationException $e) {
            $this->dispatch('notify', title: 'Validation Error', variant: 'warning', message: 'Please correct the highlighted fields.');
            throw $e;
        } catch (\Throwable $th) {
            \Log::error('Error updating product.', ['exception' => $th]);
            $this->dispatch('notify', title: 'Update Failed', variant: 'danger', message: 'Failed to update product. Please try again.');
        }
    }
}; ?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate />
        <flux:breadcrumbs.item :href="route('admin.catalog.products.index')" wire:navigate>Products</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ Str::limit($product->name, 40) }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="mt-2 mb-6">
        <flux:heading size="xl">{{ $product->name }}</flux:heading>
        <flux:subheading>SKU: {{ $product->sku ?? '—' }} &mdash; Last updated {{ $product->updated_at->diffForHumans() }}</flux:subheading>
    </div>

    <form wire:submit="save" class="space-y-5">
        @include('pages.admin.catalog.products._form-fields')

        <flux:card class="flex justify-end gap-3 p-4 bg-zinc-50 dark:bg-zinc-900">
            <flux:button variant="ghost" :href="route('admin.catalog.products.index')" wire:navigate class="cursor-pointer">
                Discard Changes
            </flux:button>
            <flux:button type="submit" variant="primary" class="cursor-pointer min-w-36"
                wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">Save Product</span>
                <span wire:loading wire:target="save">Saving...</span>
            </flux:button>
        </flux:card>
    </form>
</div>
