<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('notifications.notify_new_order', true);
        $this->migrator->add('notifications.notify_low_stock', true);
        $this->migrator->add('notifications.notify_new_review', false);
        $this->migrator->add('notifications.notify_new_user', false);
        $this->migrator->add('notifications.notify_failed_payment', true);
        $this->migrator->add('notifications.admin_notification_email', null);
    }
};
