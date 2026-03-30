<?php

namespace App\Services;

use App\Settings\TaxSettings;

/**
 * Calculates tax based on TaxSettings configuration.
 * Supports both inclusive (tax already in price) and exclusive (tax added on top) modes.
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
     * Get the tax rate as a decimal (e.g., 0.16 for 16%).
     */
    public function rate(): float
    {
        return $this->settings->tax_rate / 100;
    }

    /**
     * Get the tax rate as a percentage string (e.g., "16%").
     */
    public function rateLabel(): string
    {
        return rtrim(rtrim(number_format($this->settings->tax_rate, 2), '0'), '.') . '%';
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
     * Calculate tax for a given amount in cents.
     *
     * For EXCLUSIVE: tax = amount * rate (added on top)
     * For INCLUSIVE: tax = amount - (amount / (1 + rate)) (extracted from price)
     *
     * @param int $amountCents The amount in cents (price or subtotal)
     * @return int Tax amount in cents
     */
    public function calculateTax(int $amountCents): int
    {
        if (!$this->isEnabled() || $amountCents <= 0) {
            return 0;
        }

        $rate = $this->rate();

        if ($this->isInclusive()) {
            // Tax is already included in the price — extract it
            // Formula: tax = amount - (amount / (1 + rate))
            return (int) round($amountCents - ($amountCents / (1 + $rate)));
        }

        // Tax is exclusive — add on top
        // Formula: tax = amount * rate
        return (int) round($amountCents * $rate);
    }

    /**
     * Calculate the tax-inclusive total from a tax-exclusive amount.
     * Only changes the amount for exclusive tax; inclusive returns as-is.
     *
     * @param int $amountCents The base amount in cents
     * @return int The total including tax in cents
     */
    public function calculateTotal(int $amountCents): int
    {
        if (!$this->isEnabled() || $this->isInclusive()) {
            return $amountCents;
        }

        return $amountCents + $this->calculateTax($amountCents);
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
