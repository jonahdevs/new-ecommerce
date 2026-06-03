<?php

namespace App\Jobs;

use App\Enums\StockStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Settings\IntegrationSettings;
use App\Support\ActivitySource;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessSapProductSync implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<int, array{sku: string, price: float|int, stock_quantity: int}>  $products
     */
    public function __construct(public readonly array $products) {}

    public function handle(IntegrationSettings $settings): void
    {
        ActivitySource::for('SAP sync', fn () => $this->sync($settings));
    }

    private function sync(IntegrationSettings $settings): void
    {
        $skus = collect($this->products)->pluck('sku');

        $productMap = Product::whereIn('sku', $skus)->get()->keyBy('sku');

        $remainingSkus = $skus->diff($productMap->keys());

        $variantMap = $remainingSkus->isNotEmpty()
            ? ProductVariant::whereIn('sku', $remainingSkus)->get()->keyBy('sku')
            : collect();

        foreach ($this->products as $item) {
            $sku = $item['sku'];

            if ($model = $productMap->get($sku)) {
                $updates = ['sap_last_synced_at' => now()];

                if ($settings->sap_sync_price) {
                    $updates['sale_price'] = $item['price'];
                }

                if ($settings->sap_sync_quantity) {
                    $updates['stock_quantity'] = $item['stock_quantity'];
                    $updates['stock_status'] = $item['stock_quantity'] > 0
                        ? StockStatus::IN_STOCK
                        : StockStatus::OUT_OF_STOCK;
                }

                $model->update($updates);
            } elseif ($model = $variantMap->get($sku)) {
                $updates = ['sap_last_synced_at' => now()];

                if ($settings->sap_sync_price) {
                    $updates['price'] = $item['price'];
                }

                if ($settings->sap_sync_quantity) {
                    $updates['stock_quantity'] = $item['stock_quantity'];
                    $updates['stock_status'] = $item['stock_quantity'] > 0
                        ? StockStatus::IN_STOCK
                        : StockStatus::OUT_OF_STOCK;
                }

                $model->update($updates);
            } else {
                Log::warning('SAP sync: SKU not found.', ['sku' => $sku]);
            }
        }
    }
}
