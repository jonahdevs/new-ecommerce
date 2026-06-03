<?php

use Artesaos\SEOTools\Facades\SEOMeta;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::storefront')] #[Title('Search')] class extends Component
{
    #[Url(as: 'q')]
    public string $query = '';

    public function mount(): void
    {
        // Search result pages shouldn't be indexed (thin/duplicate content).
        SEOMeta::setRobots('noindex,follow');
    }
}; ?>

<div class="shell py-12">
    {{-- TODO: search input bound to $query, results grid, empty/no-results state --}}
</div>
