<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing product inventory and reservations
 */
class InventoryService
{
    /**
     * Check if all cart items are in stock
     * 
     * @param Cart $cart
     * @return array Array of unavailable items with details
     */
    public function checkAvailability(Cart $cart): array
    {
        $unavailable = [];

        foreach ($cart->items as $item) {
            // Get the stock item (variant or product)
            $stockItem = $this->getStockItem($item);

            if (!$stockItem) {
                $unavailable[] = [
                    'product' => $item->product->name,
                    'requested' => $item->quantity,
                    'available' => 0,
                    'reason' => 'Product not found',
                ];
                continue;
            }

            // Calculate available stock (total - reserved)
            $availableStock = $this->getAvailableStock($stockItem);

            if ($availableStock < $item->quantity) {
                $unavailable[] = [
                    'product' => $item->product->name,
                    'variant' => $item->variant?->name,
                    'requested' => $item->quantity,
                    'available' => $availableStock,
                    'reason' => 'Insufficient stock',
                ];
            }
        }

        return $unavailable;
    }

    /**
     * Reserve inventory for an order (soft lock)
     * This prevents overselling while payment is being processed
     * 
     * @param Order $order
     * @throws \Exception If stock is insufficient
     */
    public function reserveStock(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $stockItem = $item->product_variant_id
                    ? ProductVariant::lockForUpdate()->find($item->product_variant_id)
                    : Product::lockForUpdate()->find($item->product_id);

                if (!$stockItem) {
                    throw new \Exception("Product not found: {$item->name}");
                }

                $availableStock = $this->getAvailableStock($stockItem);

                if ($availableStock < $item->quantity) {
                    throw new \Exception("Insufficient stock for {$item->name}. Available: {$availableStock}, Requested: {$item->quantity}");
                }

                // Create reservation record
                $stockItem->reservations()->create([
                    'order_id' => $order->id,
                    'quantity' => $item->quantity,
                    'expires_at' => now()->addMinutes(30), // Match payment expiry
                ]);

                Log::info('Stock reserved', [
                    'order_id' => $order->id,
                    'product' => $item->name,
                    'quantity' => $item->quantity,
                ]);
            }
        });
    }

    /**
     * Deduct inventory after successful payment
     * 
     * @param Order $order
     * @throws \Exception If stock changed since reservation
     */
    public function deductStock(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $stockItem = $item->product_variant_id
                    ? ProductVariant::lockForUpdate()->find($item->product_variant_id)
                    : Product::lockForUpdate()->find($item->product_id);

                if (!$stockItem) {
                    Log::error('Stock item not found during deduction', [
                        'order_id' => $order->id,
                        'item_id' => $item->id,
                    ]);
                    throw new \Exception("Product not found: {$item->name}");
                }

                // Verify stock is still sufficient
                if ($stockItem->stock_quantity < $item->quantity) {
                    Log::error('Insufficient stock during deduction', [
                        'order_id' => $order->id,
                        'product' => $item->name,
                        'available' => $stockItem->stock_quantity,
                        'required' => $item->quantity,
                    ]);
                    throw new \Exception("Stock changed for {$item->name}");
                }

                // Deduct the stock
                $stockItem->decrement('stock_quantity', $item->quantity);

                // Remove the reservation
                $stockItem->reservations()
                    ->where('order_id', $order->id)
                    ->delete();

                Log::info('Stock deducted', [
                    'order_id' => $order->id,
                    'product' => $item->name,
                    'quantity' => $item->quantity,
                    'remaining' => $stockItem->fresh()->stock_quantity,
                ]);
            }
        });
    }

    /**
     * Release reserved inventory (on cancellation/expiry)
     * 
     * @param Order $order
     */
    public function releaseReservation(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $stockItem = $item->product_variant_id
                    ? ProductVariant::find($item->product_variant_id)
                    : Product::find($item->product_id);

                if (!$stockItem) {
                    continue;
                }

                // Delete reservations for this order
                $deleted = $stockItem->reservations()
                    ->where('order_id', $order->id)
                    ->delete();

                if ($deleted > 0) {
                    Log::info('Stock reservation released', [
                        'order_id' => $order->id,
                        'product' => $item->name,
                        'quantity' => $item->quantity,
                    ]);
                }
            }
        });
    }

    /**
     * Get available stock (total - reserved)
     * 
     * @param Product|ProductVariant $stockItem
     * @return int
     */
    private function getAvailableStock($stockItem): int
    {
        $totalStock = $stockItem->stock_quantity ?? 0;

        // Get active reservations (non-expired)
        $reserved = $stockItem->reservations()
            ->where('expires_at', '>', now())
            ->sum('quantity');

        return max(0, $totalStock - $reserved);
    }

    /**
     * Get the stock item (variant or product) from cart item
     * 
     * @param mixed $cartItem
     * @return Product|ProductVariant|null
     */
    private function getStockItem($cartItem)
    {
        if ($cartItem->variant_id) {
            return ProductVariant::find($cartItem->variant_id);
        }

        return $cartItem->product;
    }

    /**
     * Clean up expired reservations (should be run via cron)
     */
    public function cleanupExpiredReservations(): int
    {
        $deleted = DB::table('inventory_reservations')
            ->where('expires_at', '<', now())
            ->delete();

        if ($deleted > 0) {
            Log::info('Cleaned up expired reservations', ['count' => $deleted]);
        }

        return $deleted;
    }
}
