<?php

namespace App\Livewire\Concerns;

use App\Support\StorefrontSession;
use Flux\Flux;

/**
 * Mix into any Livewire page that renders <x-storefront.product-card> so
 * the card's "Add to cart" and wishlist-heart buttons can dispatch
 * wire:click actions on the parent.
 */
trait InteractsWithStorefront
{
    public function addToCart(string $slug, int $qty = 1, ?int $variantId = null): void
    {
        StorefrontSession::addToCart($slug, $qty, $variantId);
        $key = StorefrontSession::lineKey($slug, $variantId);
        $newQty = StorefrontSession::cartQuantity($key);

        $this->skipRender();
        $this->dispatch('cart-updated');
        $this->dispatch('cart-qty-changed', slug: $slug, qty: $newQty);
        Flux::toast(heading: 'Added to cart', text: 'Item has been added to your cart.', variant: 'success');
    }

    public function decrementCart(string $slug): void
    {
        $current = StorefrontSession::cartQuantity($slug);

        if ($current <= 1) {
            StorefrontSession::removeFromCart($slug);
        } else {
            StorefrontSession::setCartQty($slug, $current - 1);
        }

        $this->skipRender();
        $this->dispatch('cart-updated');
        $this->dispatch('cart-qty-changed', slug: $slug, qty: StorefrontSession::cartQuantity($slug));
    }

    public function toggleWishlist(string $slug): void
    {
        $added = StorefrontSession::toggleWishlist($slug);

        $this->dispatch('wishlist-updated');
        Flux::toast(
            heading: $added ? 'Saved to wishlist' : 'Removed from wishlist',
            text: $added ? 'You can view your saved items on the wishlist page.' : 'Item has been removed from your wishlist.',
            variant: $added ? 'success' : 'warning',
        );
    }

    public function toggleCompare(string $slug): void
    {
        $added = StorefrontSession::toggleCompare($slug);

        $this->dispatch('compare-updated');
        Flux::toast(
            heading: $added ? 'Added to compare' : 'Removed from compare',
            text: $added ? 'Head to the compare page to view products side by side.' : 'Item has been removed from your compare list.',
            variant: $added ? 'success' : 'warning',
        );
    }
}
