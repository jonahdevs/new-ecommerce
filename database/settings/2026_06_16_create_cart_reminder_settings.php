<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('cart_reminders.enabled', true);
        $this->migrator->add('cart_reminders.first_delay_hours', 4);
        $this->migrator->add('cart_reminders.second_delay_hours', 24);
        $this->migrator->add('cart_reminders.min_subtotal_cents', 0);
        $this->migrator->add('cart_reminders.stop_after_hours', 168);
    }
};
