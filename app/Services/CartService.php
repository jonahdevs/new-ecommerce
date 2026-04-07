<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class CartService
 *
 * Singleton service — bind in AppServiceProvider:
 *   $this->app->singleton(CartService::class);
 *
 * The in-memory cache ($resolvedCart, $cachedItemKeys) lives for the
 * lifetime of a single request, eliminating the N+1 pattern that
 * previously fired 2 queries per product card on every page render.
 */
class CartService
{
    // -------------------------------------------------------------------------
    // In-memory request cache
    // -------------------------------------------------------------------------

    /** @var Cart|null Resolved cart for this request — null until first access. */
    private ?Cart $resolvedCart = null;

    /**
     * @var Collection|null Flat collection of "productId-variantId" strings.
     *                      Built once from the eager-loaded items and reused for O(1) has() checks.
     */
    private ?Collection $cachedItemKeys = null;

    // =========================================================================
    // Core cart resolution
    // =========================================================================

    /**
     * Get (or create) the cart for the current user / guest session.
     *
     * The cart is resolved once per request and cached in memory.
     * Items are always eager-loaded with a minimal select so that has()
     * and getCartItem() never need to hit the database again.
     *
     * @param  User|null  $user  Pass an explicit user to override Auth::user().
     * @param  bool  $withItems  When true, also loads item.product & item.variant
     *                           relationships (needed for summary / checkout).
     */
    public function getCart(?User $user = null, bool $withItems = false): Cart
    {
        if ($this->resolvedCart === null) {
            $this->resolvedCart = $this->resolveCart($user);

            // Always load the lightweight item list so has() is query-free.
            $this->resolvedCart->load([
                'items' => fn ($q) => $q->select(['id', 'cart_id', 'product_id', 'variant_id', 'quantity']),
            ]);

            session(['cart_count' => $this->resolvedCart->items->sum('quantity')]);
        }

        // Optionally pull in the heavier relationships on demand.
        if ($withItems && ! $this->resolvedCart->items->first()?->relationLoaded('product')) {
            $this->resolvedCart->load(['items.product', 'items.variant']);
        }

        return $this->resolvedCart;
    }

    /**
     * Convenience wrapper — returns a cart with items.product + items.variant loaded.
     * Use this when you need to iterate over items for display or calculation.
     */
    public function getCartWithItems(?User $user = null): Cart
    {
        return $this->getCart($user, withItems: true);
    }

    // =========================================================================
    // Read methods (query-free after first getCart() call)
    // =========================================================================

    /**
     * Check whether a product (optionally a specific variant) is in the cart.
     * After the first call the result comes from an in-memory Collection — no DB hit.
     */
    public function has(int $productId, ?int $variantId = null): bool
    {
        try {
            return $this->itemKeys()->contains($this->itemKey($productId, $variantId));
        } catch (\Throwable $th) {
            Log::error('CartService::has() failed', [
                'product_id' => $productId,
                'error' => $th->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Return the CartItem model for a product/variant, or null if not in cart.
     * Uses the already-loaded items collection — no extra query.
     */
    public function getCartItem(int $productId, ?int $variantId = null): mixed
    {
        try {
            return $this->getCart()->items->first(
                fn ($item) => $item->product_id === $productId
                && $item->variant_id === $variantId
            );
        } catch (Exception $e) {
            Log::error('CartService::getCartItem() failed', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Return true when the cart has at least one item.
     */
    public function hasItems(): bool
    {
        return $this->getCart()->items->isNotEmpty();
    }

    /**
     * Total quantity of all items in the cart.
     */
    public function getCount(): int
    {
        try {
            return (int) $this->getCart()->items->sum('quantity');
        } catch (Exception $e) {
            Log::error('CartService::getCount() failed', ['error' => $e->getMessage()]);

            return 0;
        }
    }

    // =========================================================================
    // Write methods (mutate DB, then invalidate in-memory cache)
    // =========================================================================

    /**
     * Add a product (or variant) to the cart.
     *
     * @throws InvalidArgumentException On bad quantity values.
     * @throws RuntimeException On stock / not-found errors.
     */
    public function addItem(int $productId, int $quantity = 1, ?int $variantId = null): Cart
    {
        try {
            if ($quantity < 1) {
                throw new InvalidArgumentException('Quantity must be at least 1!');
            }

            if ($quantity > 100) {
                throw new InvalidArgumentException('Quantity cannot exceed 100 items!');
            }

            $cart = $this->getCart();
            $product = Product::findOrFail($productId);

            // Stock guard — only for stock-managed products without backorder.
            if (
                $product->manage_stock
                && $product->stock_quantity <= 0
                && $product->allow_backorder === 'no'
            ) {
                throw new RuntimeException('Product is out of stock');
            }

            $existing = $this->getCartItem($productId, $variantId);

            if ($existing) {
                $newQuantity = $existing->quantity + $quantity;

                if ($product->manage_stock && $newQuantity > $product->stock_quantity) {
                    throw new RuntimeException(
                        "Cannot add {$quantity} more items. Only {$product->stock_quantity} available "
                        ."(you have {$existing->quantity} in cart)."
                    );
                }

                if ($newQuantity > 100) {
                    throw new RuntimeException('Cart item quantity cannot exceed 100.');
                }

                $existing->increment('quantity', $quantity);
            } else {
                if ($product->manage_stock && $quantity > $product->stock_quantity) {
                    throw new RuntimeException(
                        "Cannot add {$quantity} items. Only {$product->stock_quantity} available in stock."
                    );
                }

                $cart->items()->create([
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'variant_id' => $variantId,
                ]);
            }

            $cart->update([
                'expires_at' => Auth::check() ? now()->addDays(30) : now()->addDays(7),
            ]);

            $this->invalidateCache();

            return $cart->fresh();
        } catch (ModelNotFoundException) {
            throw new RuntimeException('Product not found.');
        } catch (InvalidArgumentException|RuntimeException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('CartService::addItem() failed', [
                'product_id' => $productId,
                'quantity' => $quantity,
                'error' => $e->getMessage(),
            ]);
            throw new RuntimeException('Unable to add item to cart. Please try again.');
        }
    }

    /**
     * Update the quantity of an existing cart item.
     * Passing quantity = 0 removes the item.
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function updateItemQuantity(int $cartItemId, int $quantity): void
    {
        try {
            if ($quantity < 0) {
                throw new InvalidArgumentException('Quantity cannot be negative.');
            }

            if ($quantity > 100) {
                throw new InvalidArgumentException('Quantity cannot exceed 100 items.');
            }

            $cart = $this->getCart();
            $cartItem = $cart->items()->findOrFail($cartItemId);

            if ($quantity === 0) {
                $cartItem->delete();
                $this->invalidateCache();

                return;
            }

            $product = $cartItem->product;

            if (! $product) {
                throw new RuntimeException('Product no longer available.');
            }

            if ($product->manage_stock && $quantity > $product->stock_quantity) {
                throw new RuntimeException(
                    "Cannot update to {$quantity} items. Only {$product->stock_quantity} available in stock."
                );
            }

            $cartItem->update(['quantity' => $quantity]);
            $this->invalidateCache();
        } catch (ModelNotFoundException) {
            throw new RuntimeException('Cart item not found.');
        } catch (InvalidArgumentException|RuntimeException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('CartService::updateItemQuantity() failed', [
                'cart_item_id' => $cartItemId,
                'quantity' => $quantity,
                'error' => $e->getMessage(),
            ]);
            throw new RuntimeException('Unable to update cart. Please try again.');
        }
    }

    /**
     * Remove a single item from the cart by its ID.
     *
     * @throws RuntimeException
     */
    public function removeItem(int $cartItemId): void
    {
        try {
            $cart = $this->getCart();
            $cartItem = $cart->items()->findOrFail($cartItemId);
            $cartItem->delete();
            $this->invalidateCache();
        } catch (ModelNotFoundException) {
            throw new RuntimeException('Cart item not found.');
        } catch (Exception $e) {
            Log::error('CartService::removeItem() failed', [
                'cart_item_id' => $cartItemId,
                'error' => $e->getMessage(),
            ]);
            throw new RuntimeException('Unable to remove item from cart. Please try again.');
        }
    }

    /**
     * Remove all items from the cart.
     *
     * @throws RuntimeException
     */
    public function clear(?User $user = null): void
    {
        try {
            if ($user) {
                $user->cart->items()->delete();
                $this->invalidateCache();

                return;
            }

            $cart = $this->getCart();

            Log::info('CartService: clearing cart', [
                'cart_id' => $cart->id,
                'user_id' => $cart->user_id,
            ]);

            $cart->items()->delete();
            $this->invalidateCache();
        } catch (Exception $e) {
            Log::error('CartService::clear() failed', ['error' => $e->getMessage()]);
            throw new RuntimeException('Unable to clear cart. Please try again.');
        }
    }

    // =========================================================================
    // Financial / weight calculations
    // =========================================================================

    /**
     * Return a summary array with subtotal, discount, and tax figures.
     *
     * Expects items to be eager-loaded with product + variant.
     * Calls getCartWithItems() automatically if they are not already loaded.
     */
    public function summary(Cart $cart): array
    {
        // Ensure the heavy relationships are present.
        if (
            ! $cart->relationLoaded('items')
            || ($cart->items->isNotEmpty() && ! $cart->items->first()->relationLoaded('product'))
        ) {
            $cart->load(['items.product', 'items.variant']);
        }

        $subtotal = $cart->items->reduce(function (float $carry, $item): float {
            $price = $item->variant?->final_price ?? $item->product->final_price;

            return $carry + ($price * $item->quantity);
        }, 0.0);

        $discount = $cart->items->reduce(function (float $carry, $item): float {
            $regular = $item->variant?->price ?? $item->product->price;
            $sale = $item->variant?->sale_price ?? $item->product->sale_price;

            if ($sale && $sale < $regular) {
                return $carry + (($regular - $sale) * $item->quantity);
            }

            return $carry;
        }, 0.0);

        $taxService = app(TaxService::class);

        // Calculate tax per line item so each product's tax class rate is applied correctly.
        $taxCents = $cart->items->reduce(function (int $carry, $item) use ($taxService, $subtotal, $discount): int {
            $price = $item->variant?->final_price ?? $item->product->final_price;
            $lineSubtotalCents = (int) round($price * $item->quantity * 100);

            // Apply proportional discount for sale-priced lines
            $regularPrice = $item->variant?->price ?? $item->product->price;
            $salePrice = $item->variant?->sale_price ?? $item->product->sale_price;
            if ($salePrice && $salePrice < $regularPrice) {
                $lineSavingCents = (int) round(($regularPrice - $salePrice) * $item->quantity * 100);
                $lineSubtotalCents = max(0, $lineSubtotalCents - $lineSavingCents);
            }

            return $carry + $taxService->calculateTax($lineSubtotalCents, $item->product);
        }, 0);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $taxCents / 100,
            'tax_name' => $taxService->name(),
            'tax_rate' => $taxService->rateLabel(),
            'tax_enabled' => $taxService->isEnabled(),
            'tax_inclusive' => $taxService->isInclusive(),
        ];
    }

    /**
     * Total weight of all cart items in kilograms.
     * Products with no weight default to 0.5 kg per item.
     */
    public function getWeight(?Cart $cart = null): float
    {
        $cart = $cart ?? $this->getCart();

        if ($cart->items->isEmpty()) {
            return 0.0;
        }

        // Load product weights if not already loaded.
        if ($cart->items->first()?->relationLoaded('product') === false) {
            $cart->load(['items.product']);
        }

        $total = $cart->items->reduce(function (float $carry, $item): float {
            $weightKg = $item->product?->weight ?? 0.5;

            return $carry + ($weightKg * $item->quantity);
        }, 0.0);

        return round($total, 3);
    }

    /**
     * Cart subtotal (after sale prices, before shipping/tax).
     * Used by ShippingCalculator for free-shipping threshold checks.
     */
    public function getSubtotal(?Cart $cart = null): float
    {
        $cart = $cart ?? $this->getCart();

        if ($cart->items->isEmpty()) {
            return 0.0;
        }

        if ($cart->items->first()?->relationLoaded('product') === false) {
            $cart->load(['items.product']);
        }

        return (float) $cart->items->reduce(function (float $carry, $item): float {
            return $carry + ($item->product->final_price * $item->quantity);
        }, 0.0);
    }

    // =========================================================================
    // Guest → user cart merge
    // =========================================================================

    /**
     * Merge the guest cart into the authenticated user's cart.
     * Call this immediately after login / registration.
     */
    public function mergeGuestCart(?string $oldSessionId = null): void
    {
        if (! Auth::check()) {
            Log::warning('CartService::mergeGuestCart() aborted — user not authenticated.');

            return;
        }

        $user = Auth::user();

        // Locate the guest cart.
        $guestCartId = session()->pull('guest_cart_id');
        $guestCart = null;

        if ($guestCartId) {
            $guestCart = Cart::where('id', $guestCartId)
                ->whereNull('user_id')
                ->with('items')
                ->first();
        }

        if (! $guestCart) {
            $sessionId = $oldSessionId ?? session()->getId();
            $guestCart = Cart::where('session_id', $sessionId)
                ->whereNull('user_id')
                ->with('items')
                ->first();
        }

        if (! $guestCart || $guestCart->items->isEmpty()) {
            return;
        }

        // Get or create the user's cart.
        $userCart = Cart::firstOrCreate(
            ['user_id' => $user->id],
            ['expires_at' => now()->addDays(30)]
        );

        foreach ($guestCart->items as $guestItem) {
            $existing = $userCart->items()
                ->where('product_id', $guestItem->product_id)
                ->where('variant_id', $guestItem->variant_id)
                ->first();

            if ($existing) {
                $existing->increment('quantity', $guestItem->quantity);
            } else {
                $guestItem->update(['cart_id' => $userCart->id]);
            }
        }

        $userCart->update(['expires_at' => now()->addDays(30)]);
        $guestCart->delete();

        // Bust the cache so the next getCart() call returns the merged user cart.
        $this->invalidateCache();
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Resolve (find or create) the correct cart row without loading relationships.
     */
    private function resolveCart(?User $user): Cart
    {
        if (Auth::check() || $user) {
            $userId = $user?->id ?? Auth::id();

            return Cart::firstOrCreate(
                ['user_id' => $userId],
                ['expires_at' => now()->addDays(30)]
            );
        }

        $sessionId = session()->getId();
        $cart = Cart::firstOrCreate(
            ['session_id' => $sessionId],
            ['expires_at' => now()->addDays(7)]
        );

        session(['guest_cart_id' => $cart->id]);

        return $cart;
    }

    /**
     * Build (or return the cached) collection of item-key strings.
     * Format: "{product_id}-{variant_id|null}"
     */
    private function itemKeys(): Collection
    {
        if ($this->cachedItemKeys === null) {
            $this->cachedItemKeys = $this->getCart()->items
                ->map(fn ($item) => $this->itemKey($item->product_id, $item->variant_id));
        }

        return $this->cachedItemKeys;
    }

    /**
     * Canonical string key for a product + optional variant combination.
     */
    private function itemKey(int $productId, ?int $variantId): string
    {
        return $productId.'-'.($variantId ?? 'null');
    }

    /**
     * Bust the in-memory cache after any write operation.
     * The next read will re-query the database and rebuild the cache.
     */
    private function invalidateCache(): void
    {
        $this->resolvedCart = null;
        $this->cachedItemKeys = null;

        if (app()->has(self::class)) {
            session(['cart_count' => $this->getCount()]);
        }
    }
}
