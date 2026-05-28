<?php

use Artesaos\SEOTools\Facades\SEOMeta;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::storefront')] #[Title('Checkout — Sheffield')] class extends Component
{
    public function mount(): void
    {
        SEOMeta::setRobots('noindex,nofollow');
    }
}; ?>

<div class="shell py-12">
    {{-- TODO: contact, shipping address, delivery method, payment, order summary, place-order --}}
</div>
