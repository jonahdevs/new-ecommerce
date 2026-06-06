<?php

use App\Models\Quote;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Preview Quote')] class extends Component
{
    public Quote $quote;

    public function mount(Quote $quote): void
    {
        $this->quote = $quote->load('items');
    }
}; ?>

<div>
    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('admin.quotes.index')" wire:navigate>Quotes</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('admin.quotes.show', $quote)" wire:navigate>{{ $quote->quote_number }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Preview</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <div id="quote-print-area">
        <x-quote-document :quote="$quote" :show-actions="false" />
    </div>

    <div class="print:hidden mt-4 flex justify-center">
        <button onclick="window.print()"
                class="inline-flex items-center gap-2 text-sm text-zinc-400 hover:text-zinc-600 transition">
            <flux:icon.printer variant="micro" class="size-4" />
            Print / Save as PDF
        </button>
    </div>
</div>
