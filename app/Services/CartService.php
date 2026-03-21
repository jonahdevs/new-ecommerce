<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class CartService.
 */
class CartService
{

    public function getCart($user = null)
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
     * Check if a product is in cart
     */
    public function has(int $productId, ?int $variantId = null): bool
    {
        try {
            $cart = $this->getCart();

            return $cart->items()
                ->where('product_id', $productId)
                ->where('variant_id', $variantId)
                ->exists();
        } catch (\Throwable $th) {
            Log::error('Error checking if product is in cart', [
                'product_id' => $productId,
                'error' => $th->getMessage(),
            ]);
            return false;
        }
    }

    public function getCartItem(int $productId, ?int $variantId = null)
    {
        try {
            $cart = $this->getCart();

            return $cart->items()
                ->where('product_id', $productId)
                ->where('variant_id', $variantId)
                ->first();
        } catch (Exception $e) {
            Log::error('Error getting cart item', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function addItem(int $productId, int $quantity = 1, ?int $variantId = null)
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

            // Stock check — only for products that manage stock
            // Skip for virtual, downloadable, and backorder products
            if ($product->manage_stock && $product->stock_quantity <= 0 && $product->allow_backorder === 'no') {
                throw new RuntimeException('Product is out of stock');
            }

            $cartItem = $cart->items()
                ->where('product_id', $productId)
                ->where('variant_id', $variantId)
                ->first();

            if ($cartItem) {
                $newQuantity = $cartItem->quantity + $quantity;

                if ($product->manage_stock && $newQuantity > $product->stock_quantity) {
                    throw new RuntimeException(
                        "Cannot add {$quantity} more items. Only {$product->stock_quantity} available (you have {$cartItem->quantity} in cart)"
                    );
                }

                if ($newQuantity > 100) {
                    throw new RuntimeException('Cart item quantity cannot exceed 100');
                }

                $cartItem->increment('quantity', $quantity);
            } else {
                if ($product->manage_stock && $quantity > $product->stock_quantity) {
                    throw new RuntimeException(
                        "Cannot add {$quantity} items. Only {$product->stock_quantity} available in stock"
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

            return $cart->fresh();
        } catch (ModelNotFoundException $e) {
            throw new RuntimeException('Product not found');
        } catch (InvalidArgumentException | RuntimeException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Error adding item to cart', [
                'product_id' => $productId,
                'quantity' => $quantity,
                'error' => $e->getMessage(),
            ]);
            throw new RuntimeException('Unable to add item to cart. Please try again.');
        }
    }
    public function hasItems(): bool
    {
        $cart = $this->getCart();

        return $cart && $cart->items()->exists();
    }

    public function updateItemQuantity(int $cartItemId, int $quantity): void
    {
        try {
            // Validate quantity
            if ($quantity < 0) {
                throw new InvalidArgumentException('Quantity cannot be negative');
            }

            if ($quantity > 100) {
                throw new InvalidArgumentException('Quantity cannot exceed 100 items');
            }

            $cart = $this->getCart();
            $cartItem = $cart->items()->findOrFail($cartItemId);

            // Handle removal
            if ($quantity === 0) {
                $cartItem->delete();

                return;
            }

            // Check stock availability
            $product = $cartItem->product;
            if (!$product) {
                throw new RuntimeException('Product no longer available');
            }

            if ($quantity > $product->stock_quantity) {
                throw new RuntimeException(
                    "Cannot update to {$quantity} items. Only {$product->stock_quantity} available in stock"
                );
            }

            $cartItem->update(['quantity' => $quantity]);
        } catch (ModelNotFoundException $e) {
            throw new RuntimeException('Cart item not found');
        } catch (InvalidArgumentException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Error updating cart item quantity', [
                'cart_item_id' => $cartItemId,
                'quantity' => $quantity,
                'error' => $e->getMessage(),
            ]);
            throw new RuntimeException('Unable to update cart. Please try again.');
        }
    }


    public function removeItem(int $cartItemId): void
    {
        try {
            $cart = $this->getCart();
            $cartItem = $cart->items()->findOrFail($cartItemId);
            $cartItem->delete();
        } catch (ModelNotFoundException $e) {
            throw new RuntimeException('Cart item not found');
        } catch (Exception $e) {
            Log::error('Error removing cart item', [
                'cart_item_id' => $cartItemId,
                'error' => $e->getMessage(),
            ]);
            throw new RuntimeException('Unable to remove item from cart. Please try again.');
        }
    }

    public function clear(User $user = null): void
    {
        try {
            if ($user) {
                $user->cart->items()->delete();
                return;
            }

            $cart = $this->getCart();

            Log::info('Clearing cart', [
                'cart_id' => $cart->id,
                'user_id' => $cart->user_id,
            ]);
            $cart->items()->delete();
        } catch (Exception $e) {
            Log::error('Error clearing cart', [
                'error' => $e->getMessage(),
            ]);
            throw new RuntimeException('Unable to clear cart. Please try again.');
        }
    }

    public function getCount(): int
    {
        try {
            return $this->getCart()->items()->sum('quantity');
        } catch (Exception $e) {
            Log::error('Error getting cart count', [
                'error' => $e->getMessage(),
            ]);

            // Return 0 as a safe fallback
            return 0;
        }
    }

    /**
     * Cart Summary
     */
    public function summary(Cart $cart)
    {
        $subtotal = $cart->items->reduce(function ($carry, $item) {
            // Use variant price if available, fall back to product price
            $price = $item->variant?->final_price ?? $item->product->final_price;
            return $carry + ($price * $item->quantity);
        }, 0);

        $discount = $cart->items->reduce(function ($carry, $item) {
            $regularPrice = $item->variant?->price ?? $item->product->price;
            $salePrice    = $item->variant?->sale_price ?? $item->product->sale_price;

            if ($salePrice && $salePrice < $regularPrice) {
                return $carry + (($regularPrice - $salePrice) * $item->quantity);
            }

            return $carry;
        }, 0);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
        ];
    }

    /**
     * Get the total weight of all items in the cart in kilograms.
     *
     * Products store weight in kg (after migration from grams).
     * Products with no weight set default to 0.5 kg per item.
     *
     * Used by ShippingCalculator to find the correct rate bracket.
     */
    public function getWeight(?Cart $cart = null): float
    {
        $cart = $cart ?? $this->getCart();

        if (!$cart || !$cart->items()->exists()) {
            return 0;
        }

        $totalWeight = $cart->items()
            ->with('product')
            ->get()
            ->reduce(function (float $carry, $item) {
                // Weight in kg — default 0.5 kg if product has no weight set
                $weightKg = $item->product?->weight ?? 0.5;
                return $carry + ($weightKg * $item->quantity);
            }, 0.0);

        // Round to 3 decimal places (nearest gram)
        return round($totalWeight, 3);
    }

    /**
     * Get the cart subtotal (final price after any sale price applied).
     * Used by ShippingCalculator for free shipping rule checks.
     */
    public function getSubtotal(?Cart $cart = null): float
    {
        $cart = $cart ?? $this->getCart();

        if (!$cart || !$cart->items()->exists()) {
            return 0.0;
        }

        return (float) $cart->items()
            ->with('product')
            ->get()
            ->reduce(function (float $carry, $item) {
                return $carry + ($item->product->final_price * $item->quantity);
            }, 0.0);
    }

    public function mergeGuestCart(?string $oldSessionId = null): void
    {

        if (!Auth::check()) {
            Log::warning('Cart merge aborted: user not authenticated');
            return;
        }

        $user = Auth::user();

        // Try to find guest cart
        $guestCartId = session()->pull('guest_cart_id');
        $guestCart = null;

        if ($guestCartId) {
            $guestCart = Cart::where('id', $guestCartId)
                ->whereNull('user_id')
                ->with('items')
                ->first();
        }

        if (!$guestCart) {
            $sessionId = $oldSessionId ?? session()->getId();

            $guestCart = Cart::where('session_id', $sessionId)
                ->whereNull('user_id')
                ->with('items')
                ->first();
        }

        if (!$guestCart || $guestCart->items->isEmpty()) {
            return;
        }

        // Get or create user cart
        $userCart = Cart::firstOrCreate(
            ['user_id' => $user->id],
            ['expires_at' => now()->addDays(30)]
        );


        // Merge items
        foreach ($guestCart->items as $guestItem) {

            $existingItem = $userCart->items()
                ->where('product_id', $guestItem->product_id)
                ->where('variant_id', $guestItem->variant_id)
                ->first();

            if ($existingItem) {
                // Merge quantities
                $existingItem->increment('quantity', $guestItem->quantity);
            } else {
                // Transfer item to user cart
                $guestItem->update(['cart_id' => $userCart->id]);
            }
        }

        // Update user cart expiry
        $userCart->update(['expires_at' => now()->addDays(30)]);

        // Delete guest cart
        $guestCart->delete();
    }
}
