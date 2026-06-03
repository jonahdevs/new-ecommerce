<?php

use Artesaos\SEOTools\Facades\SEOMeta;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::storefront')] #[Title('Order confirmation')] class extends Component
{
    public function mount(): void
    {
        SEOMeta::setRobots('noindex,nofollow');
    }
}; ?>

<div class="mx-auto max-w-3xl px-6 py-16">
    {{-- TODO: order number, items, totals, shipping address, next-steps copy --}}
</div>
