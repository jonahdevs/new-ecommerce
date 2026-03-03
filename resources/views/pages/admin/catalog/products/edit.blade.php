<?php
use Livewire\Attributes\Title;
use App\Livewire\Admin\BaseProductComponent;
use App\Models\Product;

new #[Title('Edit Product')] class extends BaseProductComponent {
    public Product $product;

    public function mount(Product $product): void
    {
        $this->product = $product;
        $this->form->setProduct($product);
    }

    protected function executeSave(): void
    {
        try {
            $this->form->update();
            $this->persistProduct($this->product);
            $this->dispatch('notify', variant: 'success', message: 'Product updated successfully!');
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
        <flux:breadcrumbs.item :href="route('admin.products.index')" wire:navigate>Products</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Edit</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between">
        <flux:heading size="xl">Edit Product</flux:heading>
        <flux:button variant="primary" type="submit" form="product-form">Update</flux:button>
    </div>

    @include('pages.admin.catalog.products.partials._form')
</div>
