<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Notifications\LowStockNotification;
use App\Notifications\OutOfStockNotification;
use App\Settings\InventorySettings;
use App\Settings\NotificationSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Service for managing product inventory and reservations
 */
class InventoryService
{
    public function __construct(
        private readonly NotificationSettings $notificationSettings,
        private readonly InventorySettings $inventorySettings,
    ) {}

    /**
     * Check if all cart items are in stock
     *
     * @return array Array of unavailable items with details
     */
    public function checkAvailability(Cart $cart): array
    {
        if (! $this->inventorySettings->inventory_tracking_enabled) {
            return [];
        }

        $unavailable = [];

        foreach ($cart->items as $item) {
            // Check if this is a bundle product
            if ($item->product->type?->value === 'bundle') {
                $bundleUnavailable = $this->checkBundleAvailability($item);
                if (! empty($bundleUnavailable)) {
                    $unavailable = array_merge($unavailable, $bundleUnavailable);
                }

                continue;
            }

            // Get the stock item (variant or product)
            $stockItem = $this->getStockItem($item);

            if (! $stockItem) {
                $unavailable[] = [
                    'product' => $item->product->name,
                    'requested' => $item->quantity,
                    'available' => 0,
                    'reason' => 'Product not found',
                ];

                continue;
            }

            // Skip stock check if product doesn't manage stock
            if (! ($stockItem->manage_stock ?? false)) {
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
     * Check availability for a bundle product by checking all child products
     */
    private function checkBundleAvailability($cartItem): array
    {
        $unavailable = [];
        $bundleProducts = $cartItem->product->bundleProducts()
            ->withPivot('quantity')
            ->get();

        foreach ($bundleProducts as $child) {
            // Skip if child doesn't manage stock
            if (! $child->manage_stock) {
                continue;
            }

            $childQuantityNeeded = ($child->pivot->quantity ?? 1) * $cartItem->quantity;
            $availableStock = $this->getAvailableStock($child);

            if ($availableStock < $childQuantityNeeded) {
                $unavailable[] = [
                    'product' => $cartItem->product->name.' (Bundle)',
                    'child_product' => $child->name,
                    'requested' => $childQuantityNeeded,
                    'available' => $availableStock,
                    'reason' => "Bundle item '{$child->name}' has insufficient stock",
                ];
            }
        }

        return $unavailable;
    }

    /**
     * Reserve inventory for an order (soft lock)
     * This prevents overselling while payment is being processed
     *
     * @throws \Exception If stock is insufficient
     */
    public function reserveStock(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                // Check if this is a bundle product
                $productSnapshot = $item->product_snapshot ?? [];
                $isBundleProduct = ($productSnapshot['type'] ?? null) === 'bundle';

                if ($isBundleProduct && ! empty($productSnapshot['bundle_contents'])) {
                    // For bundles, reserve stock from each child product
                    foreach ($productSnapshot['bundle_contents'] as $bundleChild) {
                        $childProduct = Product::lockForUpdate()->find($bundleChild['id']);

                        if (! $childProduct) {
                            Log::warning('Bundle child product not found during reservation', [
                                'order_id' => $order->id,
                                'bundle_product' => $item->getProductName(),
                                'child_id' => $bundleChild['id'],
                            ]);

                            continue;
                        }

                        // Skip if child doesn't manage stock
                        if (! $childProduct->manage_stock) {
                            continue;
                        }

                        $childQuantity = ($bundleChild['quantity'] ?? 1) * $item->quantity;
                        $availableStock = $this->getAvailableStock($childProduct);

                        if ($availableStock < $childQuantity) {
                            throw new \Exception("Insufficient stock for bundle item '{$childProduct->name}'. Available: {$availableStock}, Requested: {$childQuantity}");
                        }

                        // Create reservation record for child product
                        $childProduct->reservations()->create([
                            'order_id' => $order->id,
                            'quantity' => $childQuantity,
                            'expires_at' => now()->addMinutes(30),
                        ]);

                        Log::info('Bundle child stock reserved', [
                            'order_id' => $order->id,
                            'bundle_product' => $item->getProductName(),
                            'child_product' => $childProduct->name,
                            'quantity' => $childQuantity,
                        ]);
                    }
                } else {
                    // Standard product/variant reservation
                    $stockItem = $item->product_variant_id
                        ? ProductVariant::lockForUpdate()->find($item->product_variant_id)
                        : Product::lockForUpdate()->find($item->product_id);

                    if (! $stockItem) {
                        throw new \Exception("Product not found: {$item->getProductName()}");
                    }

                    // Skip if product doesn't manage stock
                    if (! ($stockItem->manage_stock ?? false)) {
                        continue;
                    }

                    $availableStock = $this->getAvailableStock($stockItem);

                    if ($availableStock < $item->quantity) {
                        throw new \Exception("Insufficient stock for {$item->getProductName()}. Available: {$availableStock}, Requested: {$item->quantity}");
                    }

                    // Create reservation record
                    $stockItem->reservations()->create([
                        'order_id' => $order->id,
                        'quantity' => $item->quantity,
                        'expires_at' => now()->addMinutes(30),
                    ]);

                    Log::info('Stock reserved', [
                        'order_id' => $order->id,
                        'product' => $item->getProductName(),
                        'quantity' => $item->quantity,
                    ]);
                }
            }

            // Log activity
            activity()
                ->performedOn($order)
                ->withProperties([
                    'order_id' => $order->id,
                    'items' => $order->items->map(fn ($item) => [
                        'product_id' => $item->product_id,
                        'product_name' => $item->getProductName(),
                        'quantity' => $item->quantity,
                    ])->toArray(),
                ])
                ->log('inventory_reserved');
        });
    }

    /**
     * Deduct inventory after successful payment
     *
     * @throws \Exception If stock changed since reservation
     */
    public function deductStock(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                // Check if this is a bundle product
                $productSnapshot = $item->product_snapshot ?? [];
                $isBundleProduct = ($productSnapshot['type'] ?? null) === 'bundle';

                if ($isBundleProduct && ! empty($productSnapshot['bundle_contents'])) {
                    // For bundles, deduct stock from each child product
                    foreach ($productSnapshot['bundle_contents'] as $bundleChild) {
                        $childProduct = Product::lockForUpdate()->find($bundleChild['id']);

                        if (! $childProduct) {
                            Log::warning('Bundle child product not found during stock deduction', [
                                'order_id' => $order->id,
                                'bundle_product' => $item->getProductName(),
                                'child_id' => $bundleChild['id'],
                            ]);

                            continue;
                        }

                        if (! $childProduct->manage_stock) {
                            continue;
                        }

                        $childQuantity = ($bundleChild['quantity'] ?? 1) * $item->quantity;

                        if ($childProduct->stock_quantity < $childQuantity) {
                            Log::error('Insufficient stock for bundle child during deduction', [
                                'order_id' => $order->id,
                                'bundle_product' => $item->getProductName(),
                                'child_product' => $childProduct->name,
                                'available' => $childProduct->stock_quantity,
                                'required' => $childQuantity,
                            ]);
                            throw new \Exception("Stock changed for bundle item {$childProduct->name}");
                        }

                        $childProduct->decrement('stock_quantity', $childQuantity);
                        $this->checkLowStock($childProduct);
                        $this->checkOutOfStock($childProduct);

                        Log::info('Bundle child stock deducted', [
                            'order_id' => $order->id,
                            'bundle_product' => $item->getProductName(),
                            'child_product' => $childProduct->name,
                            'quantity' => $childQuantity,
                            'remaining' => $childProduct->fresh()->stock_quantity,
                        ]);
                    }

                    // Remove the bundle's reservation (if any)
                    $bundleProduct = Product::find($item->product_id);
                    if ($bundleProduct) {
                        $bundleProduct->reservations()
                            ->where('order_id', $order->id)
                            ->delete();
                    }
                } else {
                    // Standard product/variant stock deduction
                    $stockItem = $item->product_variant_id
                        ? ProductVariant::lockForUpdate()->find($item->product_variant_id)
                        : Product::lockForUpdate()->find($item->product_id);

                    if (! $stockItem) {
                        Log::error('Stock item not found during deduction', [
                            'order_id' => $order->id,
                            'item_id' => $item->id,
                        ]);
                        throw new \Exception("Product not found: {$item->getProductName()}");
                    }

                    // Only deduct if stock is managed
                    if ($stockItem->manage_stock ?? false) {
                        // Verify stock is still sufficient
                        if ($stockItem->stock_quantity < $item->quantity) {
                            Log::error('Insufficient stock during deduction', [
                                'order_id' => $order->id,
                                'product' => $item->getProductName(),
                                'available' => $stockItem->stock_quantity,
                                'required' => $item->quantity,
                            ]);
                            throw new \Exception("Stock changed for {$item->getProductName()}");
                        }

                        // Deduct the stock
                        $stockItem->decrement('stock_quantity', $item->quantity);

                        // Check stock levels and notify admin if thresholds crossed
                        $this->checkLowStock($stockItem);
                        $this->checkOutOfStock($stockItem);

                        Log::info('Stock deducted', [
                            'order_id' => $order->id,
                            'product' => $item->getProductName(),
                            'quantity' => $item->quantity,
                            'remaining' => $stockItem->fresh()->stock_quantity,
                        ]);
                    }

                    // Remove the reservation
                    $stockItem->reservations()
                        ->where('order_id', $order->id)
                        ->delete();
                }
            }

            // Log activity
            activity()
                ->performedOn($order)
                ->withProperties([
                    'order_id' => $order->id,
                    'items' => $order->items->map(fn ($item) => [
                        'product_id' => $item->product_id,
                        'product_name' => $item->getProductName(),
                        'quantity_deducted' => $item->quantity,
                    ])->toArray(),
                ])
                ->log('inventory_deducted');
        });
    }

    /**
     * Release reserved inventory (on cancellation/expiry)
     */
    public function releaseReservation(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                // Check if this is a bundle product
                $productSnapshot = $item->product_snapshot ?? [];
                $isBundleProduct = ($productSnapshot['type'] ?? null) === 'bundle';

                if ($isBundleProduct && ! empty($productSnapshot['bundle_contents'])) {
                    // For bundles, release reservations from each child product
                    foreach ($productSnapshot['bundle_contents'] as $bundleChild) {
                        $childProduct = Product::find($bundleChild['id']);

                        if (! $childProduct) {
                            continue;
                        }

                        $deleted = $childProduct->reservations()
                            ->where('order_id', $order->id)
                            ->delete();

                        if ($deleted > 0) {
                            Log::info('Bundle child stock reservation released', [
                                'order_id' => $order->id,
                                'bundle_product' => $item->getProductName(),
                                'child_product' => $childProduct->name,
                            ]);
                        }
                    }
                } else {
                    // Standard product/variant reservation release
                    $stockItem = $item->product_variant_id
                        ? ProductVariant::find($item->product_variant_id)
                        : Product::find($item->product_id);

                    if (! $stockItem) {
                        continue;
                    }

                    // Delete reservations for this order
                    $deleted = $stockItem->reservations()
                        ->where('order_id', $order->id)
                        ->delete();

                    if ($deleted > 0) {
                        Log::info('Stock reservation released', [
                            'order_id' => $order->id,
                            'product' => $item->getProductName(),
                            'quantity' => $item->quantity,
                        ]);
                    }
                }
            }
        });
    }

    /**
     * Get available stock (total - reserved)
     *
     * @param  Product|ProductVariant  $stockItem
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
     * @param  mixed  $cartItem
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

    /**
     * Check if product stock is low and send notification
     *
     * @param  Product|ProductVariant  $stockItem
     */
    private function checkLowStock($stockItem): void
    {
        if (! $this->notificationSettings->notify_low_stock) {
            return;
        }

        if ($stockItem instanceof ProductVariant) {
            return;
        }

        if (! $stockItem->manage_stock) {
            return;
        }

        $currentStock = $stockItem->stock_quantity;
        $threshold = $stockItem->low_stock_threshold ?? $this->inventorySettings->low_stock_threshold;

        if ($currentStock <= $threshold && $currentStock > 0) {
            try {
                $staffUsers = User::staff()->get();

                Notification::send($staffUsers, new LowStockNotification($stockItem));

                Log::info('Low stock notification sent to staff', [
                    'product_id' => $stockItem->id,
                    'sku' => $stockItem->sku,
                    'current_stock' => $currentStock,
                    'threshold' => $threshold,
                    'staff_count' => $staffUsers->count(),
                ]);
            } catch (\Throwable $e) {
                Log::error('Failed to send low stock notification', [
                    'product_id' => $stockItem->id,
                    'exception' => $e->getMessage(),
                ]);
            }
        }
    }

    private function checkOutOfStock($stockItem): void
    {
        if (! $this->notificationSettings->notify_out_of_stock) {
            return;
        }

        if ($stockItem instanceof ProductVariant) {
            return;
        }

        if (! $stockItem->manage_stock) {
            return;
        }

        if ($stockItem->stock_quantity <= 0) {
            try {
                $staffUsers = User::staff()->get();

                Notification::send($staffUsers, new OutOfStockNotification($stockItem));

                Log::info('Out of stock notification sent to staff', [
                    'product_id' => $stockItem->id,
                    'sku' => $stockItem->sku,
                    'staff_count' => $staffUsers->count(),
                ]);
            } catch (\Throwable $e) {
                Log::error('Failed to send out of stock notification', [
                    'product_id' => $stockItem->id,
                    'exception' => $e->getMessage(),
                ]);
            }
        }
    }
}
