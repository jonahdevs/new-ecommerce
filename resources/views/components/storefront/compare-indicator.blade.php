<?php

use App\Support\StorefrontSession;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    /**
     * Dispatched by InteractsWithStorefront::toggleCompare() and the compare page's remove/clear actions.
     */
    #[On('compare-updated')]
    public function refresh(): void {}
}; ?>

@php
    $compareCount = StorefrontSession::compareCount();
@endphp

<flux:tooltip content="Compare">
    <a href="{{ route('compare') }}" wire:navigate aria-label="Compare"
        class="relative inline-flex size-10 items-center justify-center rounded-md text-ink-2 transition hover:bg-surface-sunken hover:text-ink">
        <flux:icon.scale variant="micro" class="size-5" />
        @if ($compareCount > 0)
            <span class="absolute top-1 right-1 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-brand-500 px-1 text-[10px] font-bold text-white tabular-nums">{{ $compareCount }}</span>
        @endif
    </a>
</flux:tooltip>
