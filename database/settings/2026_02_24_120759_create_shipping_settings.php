<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('shipping.free_shipping_enabled', false);
        $this->migrator->add('shipping.free_shipping_threshold', 5000.00);
        $this->migrator->add('shipping.estimated_delivery_days_min', 1);
        $this->migrator->add('shipping.estimated_delivery_days_max', 3);
        $this->migrator->add('shipping.delivery_estimate_message', 'Delivered within 1–3 business days');
        $this->migrator->add('shipping.allow_pickup', true);
        $this->migrator->add('shipping.default_weight_unit', 'kg');
        $this->migrator->add('shipping.default_packaging_weight', 0.00);
    }
};
