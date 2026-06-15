<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    /**
     * `payments.paystack_enabled` was appended to the financial settings group
     * migration after it had already run on existing environments, leaving the
     * property absent there (Spatie never re-runs a completed migration). Add it
     * idempotently so already-seeded databases are repaired without breaking
     * fresh installs that picked it up from the base migration.
     */
    public function up(): void
    {
        if (! $this->migrator->exists('payments.paystack_enabled')) {
            $this->migrator->add('payments.paystack_enabled', true);
        }
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('payments.paystack_enabled');
    }
};
