<?php

namespace App\Livewire\Concerns;

use App\Enums\StockStatus;
use App\Models\Product;
use App\Support\StorefrontSession;
use App\Support\TaxCalculator;
use Flux\Flux;

/**
 * Mix into any Livewire page that renders <x-storefront.product-card> so
 * the card's "Add to cart" and wishlist-heart buttons can dispatch
 * wire:click actions on the parent.
 *
 * Adding a product that has accessory links opens a "Complete your purchase"
 * prompt so the customer can add the matching accessories (e.g. an oven's
 * trays) in the same step instead of shopping for them separately.
 */
trait InteractsWithStorefront
{
    // ==================================================
    // ACCESSORY PROMPT ("Complete your purchase")
    // ==================================================

    public bool $showAccessoryModal = false;

    public string $accessoryParentName = '';

    /**
     * Display rows for the prompt, captured when it opens.
     *
     * @var array<int, array{slug: string, name: string, image: ?string, price_cents: ?int, is_required: bool, in_stock: bool}>
     */
    public array $accessoryModalItems = [];

    /**
     * Customer selections keyed by accessory slug.
     *
     * @var array<string, array{checked: bool, qty: int}>
     */
    public array $accessorySelections = [];

    public function addToCart(string $slug, int $qty = 1, ?int $variantId = null): void
    {
        StorefrontSession::addToCart($slug, $qty, $variantId);
        $key = StorefrontSession::lineKey($slug, $variantId);
        $newQty = StorefrontSession::cartQuantity($key);

        $this->dispatch('cart-updated');
        $this->dispatch('cart-qty-changed', slug: $slug, qty: $newQty);

        // A specific variant is the line the customer chose; only prompt for
        // accessories when adding the base product.
        if ($variantId === null && $this->openAccessoryPrompt($slug)) {
            // Keep rendering so the modal appears; the modal is the feedback.
            return;
        }

        $this->skipRender();
        Flux::toast(heading: 'Added to cart', text: 'Item has been added to your cart.', variant: 'success');
    }

    /**
     * Capture the just-added product's accessories and open the prompt.
     * Returns false (no prompt) when the product has no visible accessories.
     */
    protected function openAccessoryPrompt(string $parentSlug): bool
    {
        // Reuse already-loaded product when available (e.g. on the product page).
        $parent = (isset($this->product) && $this->product instanceof Product && $this->product->slug === $parentSlug)
            ? $this->product
            : Product::query()->where('slug', $parentSlug)->where('visibility', 'visible')->first();

        if (! $parent) {
            return false;
        }

        $accessories = $parent->accessories()
            ->visibleInCatalog()
            ->published()
            ->with('media')
            ->get();

        if ($accessories->isEmpty()) {
            return false;
        }

        $tax = app(TaxCalculator::class);
        $items = [];
        $selections = [];

        foreach ($accessories as $accessory) {
            $price = $accessory->sale_price ?? $accessory->price;
            $qty = max(1, (int) $accessory->pivot->default_quantity);

            $items[] = [
                'slug' => $accessory->slug,
                'name' => $accessory->name,
                'image' => $accessory->cover_url,
                'price_cents' => $price ? $tax->displayPriceCents($accessory, (int) $price) : null,
                'is_required' => (bool) $accessory->pivot->is_required,
                'in_stock' => $accessory->stock_status === StockStatus::IN_STOCK,
            ];

            // Required and recommended accessories are both pre-checked; the
            // customer can adjust the quantity or uncheck before confirming.
            $selections[$accessory->slug] = ['checked' => true, 'qty' => $qty];
        }

        $this->accessoryParentName = $parent->name;
        $this->accessoryModalItems = $items;
        $this->accessorySelections = $selections;
        $this->showAccessoryModal = true;

        return true;
    }

    public function incAccessoryQty(string $slug): void
    {
        if (! isset($this->accessorySelections[$slug])) {
            return;
        }

        $this->accessorySelections[$slug]['qty'] = min(99, (int) ($this->accessorySelections[$slug]['qty'] ?? 1) + 1);
    }

    public function decAccessoryQty(string $slug): void
    {
        if (! isset($this->accessorySelections[$slug])) {
            return;
        }

        $this->accessorySelections[$slug]['qty'] = max(1, (int) ($this->accessorySelections[$slug]['qty'] ?? 1) - 1);
    }

    /** Add the checked accessories to the cart as their own line items. */
    public function addSelectedAccessories(): void
    {
        // Only slugs captured when the prompt opened are honoured (the public
        // selections array is client-editable).
        $allowed = collect($this->accessoryModalItems)->keyBy('slug');
        $added = 0;

        foreach ($this->accessorySelections as $accessorySlug => $selection) {
            if (! $allowed->has($accessorySlug) || empty($selection['checked'])) {
                continue;
            }

            $qty = max(1, (int) ($selection['qty'] ?? 1));
            StorefrontSession::addToCart($accessorySlug, $qty);
            $added += $qty;
        }

        $this->closeAccessoryModal();
        $this->dispatch('cart-updated');

        Flux::toast(
            heading: $added > 0 ? 'Added to cart' : 'Nothing added',
            text: $added > 0 ? 'Your accessories have been added to the cart.' : 'No accessories were selected.',
            variant: $added > 0 ? 'success' : 'warning',
        );
    }

    public function closeAccessoryModal(): void
    {
        $this->showAccessoryModal = false;
        $this->accessoryParentName = '';
        $this->accessoryModalItems = [];
        $this->accessorySelections = [];
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
