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

    /**
     * Whether the storefront should show prices with tax included.
     * Always mirrors prices_include_tax — one source of truth.
     */
    public function displayIncludesTax(): bool
    {
        return $this->pricesIncludeTax();
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
     * Display price for a product. Storage always matches display now, so the
     * stored price is returned as-is.
     */
    public function displayPriceCents(Product $product, int $priceCents): int
    {
        return $priceCents;
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
