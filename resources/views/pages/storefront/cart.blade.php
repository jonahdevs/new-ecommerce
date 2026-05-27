<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::storefront')] #[Title('Cart')] class extends Component
{
    //
}; ?>

<div class="mx-auto max-w-7xl px-6 py-12">
    {{-- TODO: line items, qty controls, remove, subtotal, proceed-to-checkout CTA, empty state --}}
</div>
