<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;

/**
 * QuoteBasketService
 *
 * Manages the session-based quote basket — a lightweight holding area
 * for products the customer wants to include in a quotation request.
 *
 * Completely separate from CartService (sale intent vs quote intent).
 * No DB writes until QuotationService::createFromBasket() is called.
 *
 * Session key: quote_basket
 * Structure: [
 *   ['product_id' => int, 'variant_id' => int|null, 'quantity' => int],
 *   ...
 * ]
 */
class QuoteBasketService
{
    protected const SESSION_KEY = 'quote_basket';

    // =========================================================================
    // READ
    // =========================================================================

    public function items(): Collection
    {
        return collect(session(self::SESSION_KEY, []));
    }

    /**
     * Get items hydrated with product and variant models.
     * Used by the /quote page to render the basket.
     * Two queries total — no N+1.
     */
    public function hydratedItems(): Collection
    {
        $raw = $this->items();

        if ($raw->isEmpty()) {
            return collect();
        }

        $productIds = $raw->pluck('product_id')->unique()->filter()->values();
        $variantIds = $raw->pluck('variant_id')->unique()->filter()->values();

        $products = Product::whereIn('id', $productIds)
            ->with(['brand:id,name'])
            ->get()
            ->keyBy('id');

        $variants = ProductVariant::whereIn('id', $variantIds)
            ->with([
                'attributeValues:id,attribute_id,value,label',
                'attributeValues.attribute:id,name',
            ])
            ->get()
            ->keyBy('id');

        return $raw->map(function ($item) use ($products, $variants) {
            $product = $products->get($item['product_id']);
            $variant = isset($item['variant_id']) ? $variants->get($item['variant_id']) : null;

            if (!$product) {
                return null;
            }

            $unitPrice = $variant?->final_price ?? $product->final_price ?? 0;

            return [
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'] ?? null,
                'quantity'   => $item['quantity'],
                'product'    => $product,
                'variant'    => $variant,
                'unit_price' => $unitPrice,
                'line_total' => $unitPrice * $item['quantity'],
            ];
        })->filter()->values();
    }

    public function count(): int
    {
        return $this->items()->sum('quantity');
    }

    public function isEmpty(): bool
    {
        return $this->items()->isEmpty();
    }

    public function has(int $productId, ?int $variantId = null): bool
    {
        return $this->items()->contains(
            fn($item) => $item['product_id'] === $productId
                && ($item['variant_id'] ?? null) === $variantId
        );
    }

    // =========================================================================
    // WRITE
    // =========================================================================

    /**
     * Add a product to the quote basket.
     * If already exists, increments quantity.
     */
    public function add(int $productId, int $quantity = 1, ?int $variantId = null): void
    {
        $items = $this->items()->toArray();

        $index = collect($items)->search(
            fn($item) => $item['product_id'] === $productId
                && ($item['variant_id'] ?? null) === $variantId
        );

        if ($index !== false) {
            $items[$index]['quantity'] += $quantity;
        } else {
            $items[] = [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'quantity'   => $quantity,
            ];
        }

        session([self::SESSION_KEY => $items]);
    }

    public function updateQuantity(int $productId, ?int $variantId, int $quantity): void
    {
        if ($quantity < 1) {
            $this->remove($productId, $variantId);
            return;
        }

        $items = $this->items()->map(function ($item) use ($productId, $variantId, $quantity) {
            if (
                $item['product_id'] === $productId
                && ($item['variant_id'] ?? null) === $variantId
            ) {
                $item['quantity'] = $quantity;
            }
            return $item;
        })->toArray();

        session([self::SESSION_KEY => $items]);
    }

    public function remove(int $productId, ?int $variantId = null): void
    {
        $items = $this->items()
            ->reject(
                fn($item) => $item['product_id'] === $productId
                    && ($item['variant_id'] ?? null) === $variantId
            )
            ->values()
            ->toArray();

        session([self::SESSION_KEY => $items]);
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }
}
