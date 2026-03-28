<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('inventory.inventory_tracking_enabled', true);
        $this->migrator->add('inventory.low_stock_threshold', 5);
        $this->migrator->add('inventory.out_of_stock_behaviour', 'show_with_notice'); // hide | show | show_with_notice
        $this->migrator->add('inventory.backorders_allowed', false);
        $this->migrator->add('inventory.backorders_message', 'Available on backorder');
        $this->migrator->add('inventory.notify_admin_low_stock', true);
        $this->migrator->add('inventory.notify_admin_out_of_stock', true);
    }
};
