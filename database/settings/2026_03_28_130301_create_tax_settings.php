<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('tax.tax_enabled', true);
        $this->migrator->add('tax.tax_name', 'VAT');
        $this->migrator->add('tax.default_tax_class_id', null); // set after tax classes are seeded
        $this->migrator->add('tax.tax_type', 'exclusive'); // inclusive | exclusive
        $this->migrator->add('tax.tax_registration_number', null);
        $this->migrator->add('tax.taxable_shipping', false);
    }
};
