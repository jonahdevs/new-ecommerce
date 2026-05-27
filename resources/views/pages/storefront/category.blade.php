<?php

use App\Models\Category;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::storefront')] class extends Component
{
    public Category $category;

    public function mount(Category $category): void
    {
        $this->category = $category;
    }

    public function rendering($view): void
    {
        $view->title($this->category->name);
    }
}; ?>

<div class="mx-auto max-w-7xl px-6 py-12">
    {{-- TODO: category header, breadcrumbs, products within this category, child-category chips --}}
</div>
