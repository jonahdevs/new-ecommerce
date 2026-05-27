<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::storefront')] #[Title('Shop')] class extends Component
{
    //
}; ?>

<div class="mx-auto max-w-7xl px-6 py-12">
    {{-- TODO: product grid, filters (price, brand, category), sort, pagination --}}
</div>
