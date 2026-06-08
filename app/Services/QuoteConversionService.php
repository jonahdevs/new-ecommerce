<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\Quote;
use App\Support\TaxCalculator;
use Illuminate\Support\Facades\DB;

class QuoteConversionService
{
    public function __construct(private TaxCalculator $tax) {}

    /**
     * Convert a quote into a pending order, link the order back to the quote,
     * and return the new order. Does not change the quote's status — callers
     * are responsible for that.
     */
    public function convert(Quote $quote): Order
    {
        return DB::transaction(function () use ($quote) {
            $lines = $quote->items()
                ->with(['product.taxClass', 'product.images' => fn ($q) => $q->where('is_cover', true)->limit(1)])
                ->get();

            // For items where the admin didn't link a product, resolve by SKU.
            $unresolvedSkus = $lines
                ->whereNull('product_id')
                ->pluck('product_snapshot.sku')
                ->filter()
                ->unique()
                ->values();

            $resolvedBySku = $unresolvedSkus->isNotEmpty()
                ? Product::with(['images' => fn ($q) => $q->where('is_cover', true)->limit(1)])
                    ->whereIn('sku', $unresolvedSkus)
                    ->get()
                    ->keyBy('sku')
                : collect();

            $lines = $lines->map(function ($item) use ($resolvedBySku) {
                if (! $item->product_id && isset($item->product_snapshot['sku'])) {
                    $resolved = $resolvedBySku->get($item->product_snapshot['sku']);
                    if ($resolved) {
                        $item->product_id = $resolved->id;
                        $item->setRelation('product', $resolved);
                    }
                }

                $rate = $item->product
                    ? $this->tax->rateForProduct($item->product)
                    : ($this->tax->enabled() ? $this->tax->defaultRate() : 0.0);

                return [
                    'item' => $item,
                    'rate' => $rate,
                    'tax_cents' => $this->tax->taxForLine((int) $item->line_total_cents, $rate),
                ];
            });

            $subtotalCents = (int) $lines->sum(fn ($line) => $line['item']->line_total_cents);
            $vatCents = (int) $lines->sum('tax_cents');
            $totalCents = $this->tax->pricesIncludeTax() ? $subtotalCents : $subtotalCents + $vatCents;

            $order = Order::create([
                'user_id' => $quote->user_id,
                'order_number' => Order::generateNumber(),
                'status' => OrderStatus::PENDING,
                'subtotal_cents' => $subtotalCents,
                'vat_cents' => $vatCents,
                'delivery_cents' => 0,
                'installation_cents' => 0,
                'total_cents' => $totalCents,
                'notes' => 'Converted from quote '.$quote->quote_number,
            ]);

            foreach ($lines as $line) {
                $item = $line['item'];
                $snapshot = $item->product_snapshot ?? [];
                if ($item->product?->cover_url) {
                    $snapshot['cover_url'] = $item->product->cover_url;
                }

                $order->items()->create([
                    'product_id' => $item->product_id,
                    'product_snapshot' => $snapshot,
                    'unit_price_cents' => $item->unit_price_cents,
                    'quantity' => $item->quantity,
                    'line_total_cents' => $item->line_total_cents,
                    'tax_rate' => $line['rate'],
                    'tax_cents' => $line['tax_cents'],
                ]);
            }

            $quote->update(['order_id' => $order->id]);

            return $order;
        });
    }
}
