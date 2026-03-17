<?php
use Livewire\Attributes\Title;
use App\Livewire\Admin\BaseProductComponent;
use App\Models\Product;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

new #[Title('Edit Product')] class extends BaseProductComponent {
    public Product $product;

    public function mount(Product $product): void
    {
        $this->authorize('update', $product);

        $this->product = $product;
        $this->form->setProduct($product);
        $this->loadProductAttributes($product);
        $this->loadProductVariants($product);
        $this->loadGroupedProducts($product);
        $this->loadAccessories($product);
        $this->loadProductDownloads($product);
    }

    protected function executeSave(): void
    {
        $this->authorize('update', $this->product);

        try {
            DB::transaction(function () {
                $this->form->update();
                $this->persistProduct($this->product);
            });

            $this->dispatch('notify', variant: 'success', message: 'Product updated successfully!');
            $this->dispatch('product-saved');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $th) {
            \Log::error('Product update failed', ['exception' => $th]);
            $this->dispatch('notify', variant: 'danger', message: 'Failed to update product.');
        }
    }
};
?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate />
        <flux:breadcrumbs.item :href="route('admin.catalog.products.index')" wire:navigate>Products
        </flux:breadcrumbs.item>
        <flux:breadcrumbs.item>
            {{ Str::limit($product->name, 40) }}
        </flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between">
        <flux:heading size="xl">Edit Product</flux:heading>

        <flux:button variant="primary" type="submit" form="product-form" class="cursor-pointer min-w-32"
            wire:loading.attr="disabled" wire:target="save">
            Update
        </flux:button>
    </div>

    @include('pages.admin.catalog.products.partials._form')
</div>
