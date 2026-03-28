<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('localization.currency', 'KES');
        $this->migrator->add('localization.currency_symbol', 'Ksh');
        $this->migrator->add('localization.currency_position', 'before'); // before | after | before_space | after_space
        $this->migrator->add('localization.decimal_separator', '.');
        $this->migrator->add('localization.thousands_separator', ',');
        $this->migrator->add('localization.decimal_places', 2);
        $this->migrator->add('localization.timezone', 'Africa/Nairobi');
        $this->migrator->add('localization.date_format', 'd/m/Y');
        $this->migrator->add('localization.time_format', '12');
        $this->migrator->add('localization.language', 'en');
    }
};
