<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::storefront')] #[Title('Checkout')] class extends Component
{
    //
}; ?>

<div class="shell py-12">
    {{-- TODO: contact, shipping address, delivery method, payment, order summary, place-order --}}
</div>
