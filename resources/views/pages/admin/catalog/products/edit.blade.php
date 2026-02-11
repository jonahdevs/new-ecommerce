<?php

use Livewire\Component;
use Livewire\Attributes\Title;
use App\Models\Product;

new #[Title('Edit Product')] class extends Component {
    public Product $product;

    public function mount(Product $product)
    {
        \Log::info(json_encode($product, JSON_PRETTY_PRINT));
        $this->product = $product;
    }
}; ?>

<div x-data="productForm">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" class="mb-2">Edit Product</flux:heading>
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="#" icon="squares-2x2" icon-variant="outline"></flux:breadcrumbs.item>
                <flux:breadcrumbs.item :href="route('admin.products')">Products</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Edit</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>

        <flux:button variant="primary" type="submit" form="product-form" class="cursor-pointer">
            Update
        </flux:button>
    </div>

    <livewire:admin.product-form :product="$product" />
</div>
