<?php

use Artesaos\SEOTools\Facades\JsonLd;
use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\TwitterCard;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::storefront')] #[Title('Contact — Sheffield')] class extends Component
{
    public function mount(): void
    {
        $description = 'Talk to Sheffield about commercial kitchen equipment, quotes, install and service. Showrooms in Nairobi, Mombasa, Kampala and Kigali.';

        SEOMeta::setDescription($description);
        OpenGraph::setDescription($description)->setType('website');
        TwitterCard::setDescription($description);

        JsonLd::setType('ContactPage')->setDescription($description);
    }
}; ?>

<div class="mx-auto max-w-3xl px-6 py-16">
    {{-- TODO: contact form (name, email, message), showroom address, hours, phone --}}
</div>
