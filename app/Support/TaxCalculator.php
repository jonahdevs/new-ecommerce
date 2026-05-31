<?php

namespace App\Support;

use App\Models\Product;
use App\Models\TaxClass;
use App\Settings\TaxSettings;

/**
 * Single source of truth for VAT calculation across cart, checkout and orders.
 *
 * It honours the store-wide {@see TaxSettings} (master switch, default rate and
 * whether catalog prices already include tax) together with each product's own
 * `is_taxable` flag and assigned tax class rate.
 */
class TaxCalculator
{
    private ?TaxClass $defaultTaxClass = null;

    private bool $defaultResolved = false;

    public function __construct(private TaxSettings $settings) {}

    public function enabled(): bool
    {
        return $this->settings->tax_enabled;
    }

    /** Whether catalog prices already include tax (Kenyan retail default). */
    public function pricesIncludeTax(): bool
    {
        return $this->settings->prices_include_tax;
    }

    /** The store-wide fallback tax class for products without one of their own. */
    public function defaultTaxClass(): ?TaxClass
    {
        if (! $this->defaultResolved) {
            $this->defaultTaxClass = $this->settings->default_tax_class_id
                ? TaxClass::find($this->settings->default_tax_class_id)
                : null;
            $this->defaultResolved = true;
        }

        return $this->defaultTaxClass;
    }

    /** Effective default rate (percent) — the default tax class's rate, or 0. */
    public function defaultRate(): float
    {
        return (float) ($this->defaultTaxClass()?->rate ?? 0);
    }

    /**
     * Effective tax rate (percent) for a product: 0 when tax is disabled
     * store-wide or the product is flagged non-taxable. Falls back to the
     * store default tax class when the product has no class of its own.
     */
    public function rateForProduct(Product $product): float
    {
        if (! $this->enabled() || ! $product->is_taxable) {
            return 0.0;
        }

        $class = $product->taxClass ?? $this->defaultTaxClass();

        return (float) ($class?->rate ?? 0);
    }

    /**
     * Tax portion in cents for a line at the given rate. When prices include
     * tax the amount is extracted from the line total; otherwise it is added
     * on top.
     */
    public function taxForLine(int $lineTotalCents, float $ratePercent): int
    {
        if ($ratePercent <= 0 || $lineTotalCents <= 0) {
            return 0;
        }

        $rate = $ratePercent / 100;

        if ($this->pricesIncludeTax()) {
            return (int) round($lineTotalCents - ($lineTotalCents / (1 + $rate)));
        }

        return (int) round($lineTotalCents * $rate);
    }

    /** Whether the storefront should show prices with tax included. */
    public function displayIncludesTax(): bool
    {
        return $this->settings->price_display === 'including';
    }

    /** Short label for how displayed prices are presented, e.g. "incl. VAT". */
    public function priceDisplaySuffix(): string
    {
        if (! $this->enabled()) {
            return '';
        }

        return $this->displayIncludesTax() ? 'incl. VAT' : 'excl. VAT';
    }

    /**
     * Convert a stored product price into the amount to display on the
     * storefront, honouring how prices are stored (`prices_include_tax`)
     * versus how they should be shown (`price_display`). Only adjusts when
     * the two differ; otherwise the stored price is returned unchanged.
     */
    public function displayPriceCents(Product $product, int $priceCents): int
    {
        // Short-circuit before touching the product's tax class so listing pages
        // never trigger an N+1 in the common case where display matches storage.
        if ($priceCents <= 0 || $this->pricesIncludeTax() === $this->displayIncludesTax()) {
            return $priceCents;
        }

        $rate = $this->rateForProduct($product);

        if ($rate <= 0) {
            return $priceCents;
        }

        $multiplier = 1 + ($rate / 100);

        // Stored excl. tax but shown incl. → add tax; stored incl. but shown excl. → strip it.
        return $this->displayIncludesTax()
            ? (int) round($priceCents * $multiplier)
            : (int) round($priceCents / $multiplier);
    }

    /**
     * Total tax in cents across cart lines.
     *
     * @param  iterable<array{product: Product, line_total_cents: int}>  $lines
     */
    public function taxForCart(iterable $lines): int
    {
        $total = 0;

        foreach ($lines as $line) {
            $total += $this->taxForLine(
                (int) $line['line_total_cents'],
                $this->rateForProduct($line['product']),
            );
        }

        return $total;
    }
}
