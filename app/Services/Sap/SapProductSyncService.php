<?php

namespace App\Services\Sap;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SapProductSyncService
{
    /**
     * Batch sync multiple products from SAP
     * Updates price and stock for existing products
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
                    $product = Product::where('sku', $validated['sku'])->first();

                    if (! $product) {
                        throw new \Exception("Product with SKU {$validated['sku']} not found");
                    }

                    $this->updateProduct($product, $validated);

                    $successful++;
                    $details[] = [
                        'success' => true,
                        'sku' => $validated['sku'],
                        'product_id' => $product->id,
                    ];

                    Log::info('SAP product sync: updated', [
                        'product_id' => $product->id,
                        'sku' => $product->sku,
                        'sale_price' => $product->sale_price,
                        'regular_price' => $product->price,
                        'stock_quantity' => $product->stock_quantity,
                    ]);
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
     * Validate product data from SAP
     * Focus on SKU, price, and stock
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
     * Update product price and stock from SAP
     */
    private function updateProduct(Product $product, array $data): Product
    {
        // SAP sends its current selling price as 'price' → stored in sale_price.
        // The admin-managed 'price' (original/"was" price) is never touched by SAP sync.
        $updateData = [
            'sale_price' => (float) $data['price'],
            'stock_quantity' => (int) $data['stock_quantity'],
            'stock_status' => $this->determineStockStatus($data['stock_quantity']),
            'sap_last_synced_at' => now(),
        ];

        $product->update($updateData);

        return $product->fresh();
    }

    /**
     * Determine stock status based on quantity
     */
    private function determineStockStatus(int $quantity): string
    {
        return $quantity > 0 ? 'in_stock' : 'out_of_stock';
    }
}
