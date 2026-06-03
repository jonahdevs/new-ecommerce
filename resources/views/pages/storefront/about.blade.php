<?php

use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\TwitterCard;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::storefront')] #[Title('About')] class extends Component
{
    public function mount(): void
    {
        $description = 'Sheffield has supplied commercial kitchens across East Africa since 2003 — restaurants, hotels and caterers in Kenya, Uganda, Tanzania and Rwanda. Authorised distributor with regional install and service teams.';

        SEOMeta::setDescription($description);
        OpenGraph::setDescription($description)->setType('website');
        TwitterCard::setDescription($description);
    }
}; ?>

<div class="mx-auto max-w-3xl px-6 py-16">
    {{-- TODO: company story, mission, team, showroom info --}}
</div>
