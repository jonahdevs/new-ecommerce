<?php

namespace App\Services\Sap;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SapProductSyncService
{
    /**
     * Batch sync multiple products from SAP
     * Updates price and stock for existing products
     *
     * @param array $products
     * @return array
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

                    if (!$product) {
                        throw new \Exception("Product with SKU {$validated['sku']} not found");
                    }

                    $this->updateProduct($product, $validated);

                    $successful++;
                    $details[] = [
                        'success' => true,
                        'sku' => $validated['sku'],
                        'product_id' => $product->id,
                    ];

                    Log::info("SAP product sync: updated", [
                        'product_id' => $product->id,
                        'sku' => $product->sku,
                        'price' => $product->price,
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
     * @param array $data
     * @return array
     * @throws \Illuminate\Validation\ValidationException
     */
    private function validateProductData(array $data): array
    {
        $validator = Validator::make($data, [
            // Required fields - SKU is the key identifier
            'sku' => 'required|string|max:255',

            // Pricing - main sync focus
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',

            // Inventory - main sync focus
            'stock_quantity' => 'required|integer|min:0',
            'manage_stock' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Update product price and stock from SAP
     *
     * @param Product $product
     * @param array $data
     * @return Product
     */
    private function updateProduct(Product $product, array $data): Product
    {
        $updateData = [
            // Update price
            'price' => $data['price'],
            'sale_price' => $data['sale_price'] ?? null,
            'cost_price' => $data['cost_price'] ?? null,

            // Update stock
            'stock_quantity' => $data['stock_quantity'],
            'manage_stock' => $data['manage_stock'] ?? $product->manage_stock,
            'stock_status' => $this->determineStockStatus($data['stock_quantity']),

            // Track sync time
            'sap_last_synced_at' => now(),
        ];

        $product->update($updateData);

        return $product->fresh();
    }



    /**
     * Determine stock status based on quantity
     *
     * @param int $quantity
     * @return string
     */
    private function determineStockStatus(int $quantity): string
    {
        return $quantity > 0 ? 'in_stock' : 'out_of_stock';
    }
}
