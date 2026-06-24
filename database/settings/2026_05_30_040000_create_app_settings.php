<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // ==================================================
        // INVENTORY
        // ==================================================
        $this->migrator->add('inventory.track_stock_by_default', true);
        $this->migrator->add('inventory.low_stock_threshold', 5);
        $this->migrator->add('inventory.out_of_stock_behavior', 'show');
        $this->migrator->add('inventory.allow_backorders_by_default', false);

        // ==================================================
        // REVIEWS
        // ==================================================
        $this->migrator->add('reviews.reviews_enabled', true);
        $this->migrator->add('reviews.require_verified_purchase', true);
        $this->migrator->add('reviews.auto_approve', false);

        // ==================================================
        // CHECKOUT & CART
        // ==================================================
        $this->migrator->add('checkout.min_order_value', 0);
        $this->migrator->add('checkout.order_prefix', 'SHF-');

        // ==================================================
        // QUOTATIONS
        // ==================================================
        $this->migrator->add('quotations.quotes_enabled', true);
        $this->migrator->add('quotations.default_validity_days', 30);
        $this->migrator->add('quotations.quote_prefix', 'RFQ-');
        $this->migrator->add('quotations.quote_terms', 'All goods remain the property of Sheffield Africa Steel Systems Ltd until full payment has been received. Until ownership passes, the buyer is expected to keep the goods in good condition and store them responsibly. Sheffield Africa reserves the right to recover any undelivered or unpaid goods should payment not be honoured as agreed.');

        // ==================================================
        // SHIPPING & DELIVERY
        // ==================================================
        // Delivery pricing (free-over thresholds, per-area fees, promotions)
        // lives in the Delivery Zones admin; these settings only cover pickup.
        $this->migrator->add('shipping.local_pickup_enabled', true);
        $this->migrator->add('shipping.pickup_address', '');
    }
};
