<?php
use Livewire\Attributes\Title;
use App\Livewire\Admin\BaseProductComponent;
use App\Enums\ProductType;
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

        // Attributes are always needed (shown on the Attributes tab for all types)
        $this->loadProductAttributes($product);

        // Variants are only relevant for variable products — skip the query for all other types
        if ($product->type === ProductType::VARIABLE->value) {
            $this->loadProductVariants($product);
        }

        // Grouped product children are only relevant for grouped products
        if ($product->type === ProductType::GROUPED->value) {
            $this->loadGroupedProducts($product);
        }

        // Accessories can appear on any product type
        $this->loadAccessories($product);

        // Download file rows are only loaded when the product is flagged as downloadable
        if ($product->is_downloadable) {
            $this->loadProductDownloads($product);
        }
    }

    protected function executeSave(): void
    {
        $this->authorize('update', $this->product);

        try {
            DB::transaction(function () {
                $this->persistProduct($this->product);
                $this->form->update();
            });

            $this->dispatch('notify', title: 'Product Updated', variant: 'success', message: 'Product updated successfully!');
            $this->dispatch('product-saved');
            
            // Skip re-render to prevent DOM replacement that breaks Alpine.js anchor references
            // This preserves wire:navigate functionality after save operations
            $this->skipRender();
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $th) {
            \Log::error('Product update failed', ['exception' => $th]);
            $this->dispatch('notify', title: 'Update Failed', variant: 'danger', message: 'Failed to update product.');
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
