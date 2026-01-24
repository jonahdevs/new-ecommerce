<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
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

    public function getCart()
    {
        if (Auth::check()) {
            // Authenticated user
            Cart::firstOrCreate(
                [
                    'user_id' => Auth::id(),
                    ['expires_at' => now()->addDays(30)]
                ]
            );
        }

        $sessionId = session()->getId();

        Log::info('Creating or retrieving guest cart', [
            'session_id' => $sessionId
        ]);

        $cart = Cart::firstOrCreate(
            ['session_id' => $sessionId],
            ['expires_at' => now()->addDays(7)]
        );

        // Save the guest cart in session so it can be referenced after
        try {
            session(['guest_cart_id' => $cart->id]);
        } catch (Exception $e) {
            //  ignore if session isn't available
        }

        return $cart;
    }

    /**
     * Check if a product is in cart
     */
    public function has(int $productId, ?string $variantId = null): bool
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
                'error' => $th->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Get cart item for a product
     */
    public function getCartItem(int $productId, ?string $variantId = null)
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

    public function addItem(int $productId, int $quantity = 1, ?string $variantId = null)
    {
        try {
            // Validate quantity
            if ($quantity < 1) {
                throw new InvalidArgumentException('Quantity must be at least 1!');
            }

            if ($quantity > 100) {
                throw new InvalidArgumentException('Quantity cannot exceed 100 items!');
            }

            $cart = $this->getCart();
            $product = Product::findOrFail($productId);

            // Check stock availability
            if ($product->stock_quantity <= 0) {
                throw new RuntimeException('Product is out of stock');
            }

            // Check if item already exists in cart
            $cartItem = $cart->items()
                ->where('product_id', $productId)
                ->where('variant_id', $variantId)
                ->first();

            if ($cartItem) {
                $newQuantity = $cartItem->quantity + $quantity;

                // Validate against stock
                if ($newQuantity > $product->stock_quantity) {
                    throw new RuntimeException("Cannot add {$quantity} more items. only {$product->stock_quantity} available in stock (you have {$cartItem->quantity} in cart)");
                }

                if ($newQuantity > 100) {
                    throw new RuntimeException('Cart item quantity cannot exceed 100');
                }

                $cartItem->increment('quantity', $quantity);
            } else {
                // Validate quantity against stock for new items
                if ($quantity > $product->stock_quantity) {
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

            // Update cart expiry
            $cart->update([
                'expires_at' => Auth::check() ? now()->addDays(30) : now()->addDays(7),
            ]);

            return $cart->fresh();
        } catch (ModelNotFoundException $e) {
            throw new RuntimeException('Product not found');
        } catch (InvalidArgumentException $e) {
            throw $e;
        } catch (RuntimeException $e) {
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

    public function clear(): void
    {
        try {
            $cart = $this->getCart();
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
        if (!$cart) {
            return [
                'subtotal' => 0,
                'discount' => 0,
            ];
        } else {
            $subtotal = $cart->items->reduce(function ($carry, $item) {
                return $carry + ($item->product->final_price * $item->quantity);
            }, 0);

            $discount = $cart->items->reduce(function ($carry, $item) {
                return $carry + (($item->product->price - $item->product->sale_price) * $item->quantity);
            }, 0);

            return [
                'subtotal' => $subtotal,
                'discount' => $discount,
            ];
        }
    }
}
