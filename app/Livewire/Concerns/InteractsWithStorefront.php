<?php

namespace App\Livewire\Concerns;

use App\Support\StorefrontSession;

/**
 * Mix into any Livewire page that renders <x-storefront.product-card> so
 * the card's "Add to cart" and wishlist-heart buttons can dispatch
 * wire:click actions on the parent.
 */
trait InteractsWithStorefront
{
    public function addToCart(string $slug, int $qty = 1): void
    {
        StorefrontSession::addToCart($slug, $qty);

        // Notify the header cart indicator (which lives in the layout, outside
        // this component's render tree) that it needs to recompute its count.
        $this->dispatch('cart-updated');
    }

    public function toggleWishlist(string $slug): void
    {
        StorefrontSession::toggleWishlist($slug);

        $this->dispatch('wishlist-updated');
    }

    public function toggleCompare(string $slug): void
    {
        StorefrontSession::toggleCompare($slug);

        $this->dispatch('compare-updated');
    }
}
