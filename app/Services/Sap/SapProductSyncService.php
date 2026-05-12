<?php

namespace App\Services\Sap;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SapProductSyncService
{
    /**
     * Minimum discount percentage to show as a sale.
     * Prevents tiny price fluctuations from appearing as discounts.
     */
    private const MIN_DISCOUNT_PERCENT = 1.0;

    /**
     * Batch sync multiple products/variants from SAP.
     * 
     * SKU lookup order:
     * 1. Check product_variants table first (variant SKUs are more specific)
     * 2. If not found, check products table
     * 
     * Updates price and stock for existing products or variants.
     */
    public function batchSyncProducts(array $products): array
    {
        $successful = 0;
        $failed = 0;
        $details = [];

        foreach ($products as $productData) {
            try {
                $validated = $this->validateProductData($productData);

                DB::transaction(function () use ($validated, &$successful, &$details) {
                    // First, try to find a variant with this SKU
                    $variant = ProductVariant::where('sku', $validated['sku'])->first();

                    if ($variant) {
                        $result = $this->updateVariant($variant, $validated);

                        $successful++;
                        $details[] = [
                            'success' => true,
                            'sku' => $validated['sku'],
                            'type' => 'variant',
                            'variant_id' => $variant->id,
                            'product_id' => $variant->product_id,
                            'price_action' => $result['action'],
                        ];

                        Log::info('SAP product sync: updated variant', [
                            'variant_id' => $variant->id,
                            'product_id' => $variant->product_id,
                            'sku' => $variant->sku,
                            'incoming_price' => $validated['price'],
                            'price_action' => $result['action'],
                            'price' => $variant->price,
                            'sale_price' => $variant->sale_price,
                            'stock_quantity' => $variant->stock_quantity,
                        ]);

                        return;
                    }

                    // If no variant found, try to find a product
                    $product = Product::where('sku', $validated['sku'])->first();

                    if ($product) {
                        $result = $this->updateProduct($product, $validated);

                        $successful++;
                        $details[] = [
                            'success' => true,
                            'sku' => $validated['sku'],
                            'type' => 'product',
                            'product_id' => $product->id,
                            'price_action' => $result['action'],
                        ];

                        Log::info('SAP product sync: updated product', [
                            'product_id' => $product->id,
                            'sku' => $product->sku,
                            'incoming_price' => $validated['price'],
                            'price_action' => $result['action'],
                            'price' => $product->price,
                            'sale_price' => $product->sale_price,
                            'stock_quantity' => $product->stock_quantity,
                        ]);

                        return;
                    }

                    // Neither product nor variant found
                    throw new \Exception("SKU {$validated['sku']} not found in products or variants");
                });

            } catch (\Throwable $e) {
                $failed++;
                $details[] = [
                    'success' => false,
                    'sku' => $productData['sku'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ];

                Log::error('SAP batch product sync item failed', [
                    'sku' => $productData['sku'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'successful' => $successful,
            'failed' => $failed,
            'details' => $details,
        ];
    }

    /**
     * Validate product data from SAP.
     * Focus on SKU, price, and stock.
     *
     * @throws ValidationException
     */
    private function validateProductData(array $data): array
    {
        $validator = Validator::make($data, [
            'sku' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Update product price and stock from SAP.
     *
     * Price Logic:
     * - If incoming price < current price (by at least MIN_DISCOUNT_PERCENT): 
     *   Keep price as "was" price, set sale_price to incoming (shows discount)
     * - If incoming price >= current price OR product has no price:
     *   Set price to incoming, clear sale_price (no discount shown)
     * - If incoming price equals current sale_price:
     *   No price change needed (already showing correct sale)
     *
     * @return array{action: string} Details about what price action was taken
     */
    private function updateProduct(Product $product, array $data): array
    {
        $incomingPrice = (float) $data['price'];
        $currentPrice = $product->price !== null ? (float) $product->price : null;
        $currentSalePrice = $product->sale_price !== null ? (float) $product->sale_price : null;

        $updateData = [
            'stock_quantity' => (int) $data['stock_quantity'],
            'stock_status' => $this->determineStockStatus($data['stock_quantity']),
            'sap_last_synced_at' => now(),
        ];

        $action = $this->determinePriceAction($incomingPrice, $currentPrice, $currentSalePrice);
        $this->applyPriceAction($action, $incomingPrice, $updateData);

        $product->update($updateData);

        return ['action' => $action];
    }

    /**
     * Update variant price and stock from SAP.
     * Uses the same price logic as products.
     *
     * @return array{action: string} Details about what price action was taken
     */
    private function updateVariant(ProductVariant $variant, array $data): array
    {
        $incomingPrice = (float) $data['price'];
        $currentPrice = $variant->price !== null ? (float) $variant->price : null;
        $currentSalePrice = $variant->sale_price !== null ? (float) $variant->sale_price : null;

        $updateData = [
            'stock_quantity' => (int) $data['stock_quantity'],
            'stock_status' => $this->determineStockStatus($data['stock_quantity']),
        ];

        $action = $this->determinePriceAction($incomingPrice, $currentPrice, $currentSalePrice);
        $this->applyPriceAction($action, $incomingPrice, $updateData);

        $variant->update($updateData);

        return ['action' => $action];
    }

    /**
     * Apply the price action to the update data array.
     */
    private function applyPriceAction(string $action, float $incomingPrice, array &$updateData): void
    {
        switch ($action) {
            case 'new_product':
                // First time setting price - no discount to show
                $updateData['price'] = $incomingPrice;
                $updateData['sale_price'] = null;
                break;

            case 'price_dropped':
                // Price decreased - show as sale (keep old price for strikethrough)
                $updateData['sale_price'] = $incomingPrice;
                // Don't update 'price' - it stays as the "was" price
                break;

            case 'price_increased':
                // Price increased - update base price, clear any sale
                $updateData['price'] = $incomingPrice;
                $updateData['sale_price'] = null;
                break;

            case 'sale_price_updated':
                // Already on sale, but sale price changed
                $updateData['sale_price'] = $incomingPrice;
                break;

            case 'no_change':
                // Price unchanged - only update stock
                break;
        }
    }

    /**
     * Determine what price action to take based on incoming vs current prices.
     */
    private function determinePriceAction(float $incomingPrice, ?float $currentPrice, ?float $currentSalePrice): string
    {
        // Case 1: Product/variant has no price yet (new or never synced)
        if ($currentPrice === null || $currentPrice <= 0) {
            return 'new_product';
        }

        // Case 2: Currently on sale - compare with sale_price
        if ($currentSalePrice !== null && $currentSalePrice > 0) {
            // If incoming matches current sale price exactly (within tolerance)
            if (abs($incomingPrice - $currentSalePrice) < 0.01) {
                return 'no_change';
            }

            // If incoming is higher than or equal to the "was" price - sale ended
            if ($incomingPrice >= $currentPrice) {
                return 'price_increased';
            }

            // If incoming is still lower than "was" price but different from current sale
            if ($incomingPrice < $currentPrice) {
                return 'sale_price_updated';
            }
        }

        // Case 3: NOT on sale - compare with regular price
        // If incoming matches current price exactly (within tolerance)
        if (abs($incomingPrice - $currentPrice) < 0.01) {
            return 'no_change';
        }

        // If incoming is lower than current price - check if discount is significant
        if ($incomingPrice < $currentPrice) {
            $discountPercent = (($currentPrice - $incomingPrice) / $currentPrice) * 100;

            // Only show as sale if discount is meaningful
            if ($discountPercent >= self::MIN_DISCOUNT_PERCENT) {
                return 'price_dropped';
            }

            // Tiny discount - just update the price without showing as sale
            return 'price_increased';
        }

        // If incoming is higher than current price
        return 'price_increased';
    }

    /**
     * Determine stock status based on quantity.
     */
    private function determineStockStatus(int $quantity): string
    {
        return $quantity > 0 ? 'in_stock' : 'out_of_stock';
    }
}
