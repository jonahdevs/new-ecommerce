<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // ── Inventory ──────────────────────────────────────────────────────
        $this->migrator->add('inventory.track_stock_by_default', true);
        $this->migrator->add('inventory.low_stock_threshold', 5);
        $this->migrator->add('inventory.out_of_stock_behavior', 'show');
        $this->migrator->add('inventory.allow_backorders_by_default', false);

        // ── Reviews ────────────────────────────────────────────────────────
        $this->migrator->add('reviews.reviews_enabled', true);
        $this->migrator->add('reviews.require_verified_purchase', true);
        $this->migrator->add('reviews.auto_approve', false);

        // ── Checkout & cart ────────────────────────────────────────────────
        $this->migrator->add('checkout.min_order_value', 0);
        $this->migrator->add('checkout.order_prefix', 'SHF-');

        // ── Quotations ─────────────────────────────────────────────────────
        $this->migrator->add('quotations.quotes_enabled', true);
        $this->migrator->add('quotations.default_validity_days', 30);
        $this->migrator->add('quotations.quote_prefix', 'RFQ-');
        $this->migrator->add('quotations.quote_terms', '');

        // ── Shipping & delivery ────────────────────────────────────────────
        // Delivery pricing (free-over thresholds, per-area fees, promotions)
        // lives in the Delivery Zones admin; these settings only cover pickup.
        $this->migrator->add('shipping.local_pickup_enabled', true);
        $this->migrator->add('shipping.pickup_address', '');
    }
};
