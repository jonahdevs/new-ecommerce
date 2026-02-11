<?php

use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Create Product')] class extends Component {}; ?>

<div>
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" class="mb-2">Create New Product</flux:heading>
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="#" icon="squares-2x2" icon-variant="outline"></flux:breadcrumbs.item>
                <flux:breadcrumbs.item :href="route('admin.products')">Products</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Create</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>


        <flux:button variant="primary" type="submit" form="product-form">Create</flux:button>
    </div>

    <livewire:admin.product-form />
</div>
