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
        $this->migrator->add('checkout.order_prefix', 'SO-');
        $this->migrator->add('checkout.order_next_number', 1001);

        // ── Quotations ─────────────────────────────────────────────────────
        $this->migrator->add('quotations.quotes_enabled', true);
        $this->migrator->add('quotations.default_validity_days', 30);
        $this->migrator->add('quotations.quote_prefix', 'QT-');
        $this->migrator->add('quotations.quote_terms', '');

        // ── Downloads ──────────────────────────────────────────────────────
        $this->migrator->add('downloads.default_download_limit', 0);
        $this->migrator->add('downloads.default_expiry_days', 0);
        $this->migrator->add('downloads.secure_downloads', true);

        // ── Shipping & delivery ────────────────────────────────────────────
        $this->migrator->add('shipping.free_shipping_threshold', 0);
        $this->migrator->add('shipping.handling_fee', 0);
        $this->migrator->add('shipping.local_pickup_enabled', true);
        $this->migrator->add('shipping.pickup_address', '');
    }
};
