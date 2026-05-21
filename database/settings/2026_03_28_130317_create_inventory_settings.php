<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('inventory.inventory_tracking_enabled', true);
        $this->migrator->add('inventory.low_stock_threshold', 5);
    }
};
