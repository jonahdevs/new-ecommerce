<?php

namespace App\Services;

use App\Models\Product;
use App\Models\TaxClass;
use App\Settings\TaxSettings;

/**
 * Calculates tax based on TaxSettings configuration.
 * Supports both inclusive (tax already in price) and exclusive (tax added on top) modes.
 * Per-product rates are resolved via tax classes; the global default class is the fallback.
 */
class TaxService
{
    public function __construct(
        private readonly TaxSettings $settings,
    ) {}

    /**
     * Check if tax calculation is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->settings->tax_enabled;
    }

    /**
     * Get the tax name (e.g., "VAT", "GST").
     */
    public function name(): string
    {
        return $this->settings->tax_name;
    }

    /**
     * Check if tax type is inclusive (price already contains tax).
     */
    public function isInclusive(): bool
    {
        return $this->settings->tax_type === 'inclusive';
    }

    /**
     * Check if shipping should be taxed.
     */
    public function taxesShipping(): bool
    {
        return $this->settings->taxable_shipping;
    }

    /**
     * Resolve the default tax class from settings (the global fallback).
     */
    public function defaultTaxClass(): ?TaxClass
    {
        if (!$this->settings->default_tax_class_id) {
            return null;
        }

        return TaxClass::find($this->settings->default_tax_class_id);
    }

    /**
     * Resolve the effective tax class for a product.
     * Uses the product's own class, falls back to the global default class.
     */
    public function effectiveTaxClass(?Product $product = null): ?TaxClass
    {
        if ($product?->taxClass) {
            return $product->taxClass;
        }

        return $this->defaultTaxClass();
    }

    /**
     * Resolve the effective tax rate for a product as a decimal (e.g. 0.16).
     * Returns 0 if no class is resolved.
     */
    public function effectiveRate(?Product $product = null): float
    {
        $class = $this->effectiveTaxClass($product);

        return $class ? (float) $class->rate / 100 : 0.0;
    }

    /**
     * Get the effective rate as a percentage label (e.g. "16%") for a product.
     */
    public function effectiveRateLabel(?Product $product = null): string
    {
        $class = $this->effectiveTaxClass($product);

        if (!$class) {
            return '0%';
        }

        return $class->rateLabel();
    }

    /**
     * Get the default rate as a percentage label for display in the settings UI.
     */
    public function rateLabel(): string
    {
        return $this->effectiveRateLabel();
    }

    /**
     * Calculate tax for a given amount in cents.
     *
     * For EXCLUSIVE: tax = amount * rate (added on top)
     * For INCLUSIVE: tax = amount - (amount / (1 + rate)) (extracted from price)
     *
     * Pass a Product to use its tax class rate instead of the global default.
     *
     * @param int $amountCents The amount in cents
     * @param Product|null $product Optional product to resolve effective rate from
     * @return int Tax amount in cents
     */
    public function calculateTax(int $amountCents, ?Product $product = null): int
    {
        if (!$this->isEnabled() || $amountCents <= 0) {
            return 0;
        }

        $rate = $this->effectiveRate($product);

        if ($rate === 0.0) {
            return 0;
        }

        if ($this->isInclusive()) {
            return (int) round($amountCents - ($amountCents / (1 + $rate)));
        }

        return (int) round($amountCents * $rate);
    }

    /**
     * Calculate the tax-inclusive total from a tax-exclusive amount.
     * Only changes the amount for exclusive tax; inclusive returns as-is.
     *
     * @param int $amountCents The base amount in cents
     * @param Product|null $product Optional product to resolve effective rate from
     * @return int The total including tax in cents
     */
    public function calculateTotal(int $amountCents, ?Product $product = null): int
    {
        if (!$this->isEnabled() || $this->isInclusive()) {
            return $amountCents;
        }

        return $amountCents + $this->calculateTax($amountCents, $product);
    }

    /**
     * Calculate tax breakdown for an order.
     *
     * @param int $subtotalCents Product subtotal in cents
     * @param int $shippingCents Shipping cost in cents
     * @return array{product_tax: int, shipping_tax: int, total_tax: int}
     */
    public function calculateOrderTax(int $subtotalCents, int $shippingCents): array
    {
        $productTax = $this->calculateTax($subtotalCents);
        $shippingTax = $this->taxesShipping() ? $this->calculateTax($shippingCents) : 0;

        return [
            'product_tax' => $productTax,
            'shipping_tax' => $shippingTax,
            'total_tax' => $productTax + $shippingTax,
        ];
    }
}
