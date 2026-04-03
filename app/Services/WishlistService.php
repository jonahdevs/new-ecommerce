<?php

namespace App\Services;

use App\Models\Product;
use App\Models\WishlistItem;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Class WishlistService.
 *
 * Singleton service — registered in AppServiceProvider.
 *
 * For authenticated users, wishlist product IDs are loaded from the
 * database once per request and cached in memory. All has() checks
 * after the first call are O(1) array lookups with zero DB hits.
 */
class WishlistService
{
    /** @var array<int>|null Cached wishlist product IDs for this request. */
    private ?array $cachedIds = null;

    /**
     * Get Wishlist count
     */
    public function getCount(): int
    {
        try {
            if (Auth::check()) {
                return Auth::user()->wishlistItems()->count();
            }

            return \count(session('wishlist', []));
        } catch (\Exception $e) {
            Log::error('Error getting wishlist count', [
                'error' => $e->getMessage(),
            ]);

            // Return 0 as a safe fallback
            return 0;
        }
    }

    /**
     * Check if product is in wishlist.
     * After the first call the result comes from an in-memory array — no DB hit.
     */
    public function has(int $productId): bool
    {
        return in_array($productId, $this->ids(), true);
    }

    /**
     * Get all wishlist product IDs for the current user/session.
     * For authenticated users the DB is queried once and the result is
     * cached in memory for the lifetime of this request.
     */
    public function ids(): array
    {
        if ($this->cachedIds !== null) {
            return $this->cachedIds;
        }

        if (Auth::check()) {
            $this->cachedIds = WishlistItem::where('user_id', Auth::id())
                ->pluck('product_id')
                ->all();
        } else {
            $this->cachedIds = session('wishlist', []);
        }

        return $this->cachedIds;
    }

    /**
     * Toggle product in wishlist (add if not present, remove if present)
     * Returns true if added, false if removed
     */
    public function toggle(int $productId): bool
    {
        try {
            if ($this->has($productId)) {
                $this->remove($productId);

                return false; // Removed
            }

            $this->add($productId);

            return true; // Added

        } catch (\Exception $e) {
            // Re-throw with context
            throw new \RuntimeException('Unable to toggle wishlist. Please try again.');
        }
    }

    /**
     * Add product to wishlist
     */
    public function add(int $productId): bool
    {
        try {
            // Verify product exists
            $product = Product::findOrFail($productId);

            if (Auth::check()) {
                $existing = WishlistItem::where('user_id', Auth::id())
                    ->where('product_id', $productId)
                    ->exists();

                if (! $existing) {
                    WishlistItem::create([
                        'user_id' => Auth::id(),
                        'product_id' => $productId,
                    ]);

                    $this->invalidateCache();

                    return true;
                }

                return false; // Already exists
            }

            // For guest users
            $wishlist = session('wishlist', []);
            if (! \in_array($productId, $wishlist, true)) {
                $wishlist[] = $productId;
                session()->put('wishlist', $wishlist);
                $this->invalidateCache();

                return true;
            }

            return false; // Already exists

        } catch (ModelNotFoundException $e) {
            throw new \RuntimeException('Product not found');
        } catch (\Exception $e) {
            Log::error('Error adding to wishlist', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Unable to add to wishlist. Please try again.');
        }
    }

    /**
     * Remove product from wishlist
     */
    public function remove(int $productId): bool
    {
        try {
            if (Auth::check()) {
                $deleted = WishlistItem::where('user_id', Auth::id())
                    ->where('product_id', $productId)
                    ->delete();

                $this->invalidateCache();

                return $deleted > 0;
            }

            // For guest users
            $wishlist = session('wishlist', []);
            if (\in_array($productId, $wishlist, true)) {
                $wishlist = array_values(array_diff($wishlist, [$productId]));
                session()->put('wishlist', $wishlist);
                $this->invalidateCache();

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Error removing from wishlist', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Unable to remove from wishlist. Please try again.');
        }
    }

    /**
     * Merge guest wishlist with user wishlist on login
     */
    public function mergeGuestWishlist(): void
    {
        if (! Auth::check()) {
            return;
        }

        $guestWishlist = session()->pull('wishlist', []);

        if (empty($guestWishlist)) {
            return;
        }

        $userId = Auth::id();

        foreach ($guestWishlist as $productId) {
            $exists = WishlistItem::where('user_id', $userId)
                ->where('product_id', $productId)
                ->exists();

            if (! $exists) {
                WishlistItem::create([
                    'user_id' => $userId,
                    'product_id' => $productId,
                ]);
            }
        }

        $this->invalidateCache();
    }

    /**
     * Bust the in-memory cache after any write operation.
     * The next ids() call will re-query the database.
     */
    private function invalidateCache(): void
    {
        $this->cachedIds = null;
    }
}
