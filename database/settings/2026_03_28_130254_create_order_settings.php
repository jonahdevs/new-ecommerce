<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('orders.order_id_prefix', 'ORD-');
        $this->migrator->add('orders.minimum_order_amount', null);
        $this->migrator->add('orders.guest_checkout_enabled', true);
        $this->migrator->add('orders.auto_cancel_unpaid', false);
        $this->migrator->add('orders.auto_cancel_hours', 24);
        $this->migrator->add('orders.stock_reduce_on_order', true);
        $this->migrator->add('orders.default_order_status', 'pending');
    }
};
