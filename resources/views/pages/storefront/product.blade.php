<?php

use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::storefront')] class extends Component
{
    public Product $product;

    public function mount(Product $product): void
    {
        $this->product = $product;
    }

    public function rendering($view): void
    {
        $view->title($this->product->name);
    }
}; ?>

<div class="shell py-12">
    {{-- TODO: gallery, title, price, variant selector, qty, add-to-cart, description, specs, related --}}
</div>
