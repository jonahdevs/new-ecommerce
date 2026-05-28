<?php

use App\Support\StorefrontSession;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    /**
     * Dispatched by InteractsWithStorefront::toggleWishlist().
     */
    #[On('wishlist-updated')]
    public function refresh(): void {}
}; ?>

@php
    $wishlistCount = StorefrontSession::wishlistCount();
@endphp

<flux:tooltip content="Wishlist">
    <a href="{{ route('wishlist') }}" wire:navigate aria-label="Wishlist"
        class="relative inline-flex size-10 items-center justify-center rounded-md text-ink-2 transition hover:bg-surface-sunken hover:text-ink">
        <flux:icon.heart variant="micro" class="size-5" />
        @if ($wishlistCount > 0)
            <span class="absolute top-1 right-1 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-brand-500 px-1 text-[10px] font-bold text-white tabular-nums">{{ $wishlistCount }}</span>
        @endif
    </a>
</flux:tooltip>
