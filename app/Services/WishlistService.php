<?php

namespace App\Services;

use App\Models\Product;
use App\Models\WishlistItem;
use Illuminate\Support\Facades\Auth;

/**
 * Class WishlistService.
 */
class WishlistService
{
   /**
     * Check if product is in wishlist
     */
    public function has(int $productId): bool
    {
        if (Auth::check()) {
            return WishlistItem::where('user_id', Auth::id())
                ->where('product_id', $productId)
                ->exists();
        }

        $wishlist = session('wishlist', []);

        return in_array($productId, $wishlist);
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
                // For authenticated users
                $existing = WishlistItem::where('user_id', Auth::id())
                    ->where('product_id', $productId)
                    ->exists();

                if (! $existing) {
                    WishlistItem::create([
                        'user_id' => Auth::id(),
                        'product_id' => $productId,
                    ]);

                    return true;
                }

                return false; // Already exists
            }

            // For guest users
            $wishlist = session('wishlist', []);
            if (! \in_array($productId, $wishlist, true)) {
                $wishlist[] = $productId;
                session()->put('wishlist', $wishlist);

                return true;
            }

            return false; // Already exists

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw new \RuntimeException('Product not found');
        } catch (\Exception $e) {
            \Log::error('Error adding to wishlist', [
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

                return $deleted > 0;
            }

            // For guest users
            $wishlist = session('wishlist', []);
            if (\in_array($productId, $wishlist, true)) {
                $wishlist = array_values(array_diff($wishlist, [$productId]));
                session()->put('wishlist', $wishlist);

                return true;
            }

            return false;

        } catch (\Exception $e) {
            \Log::error('Error removing from wishlist', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Unable to remove from wishlist. Please try again.');
        }
    }
}
