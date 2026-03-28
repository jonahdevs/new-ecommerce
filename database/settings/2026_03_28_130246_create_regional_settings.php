<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {

        $this->migrator->add('regional.weight_unit', 'kg');    // kg | lb | g | oz
        $this->migrator->add('regional.dimension_unit', 'cm'); // cm | m | inch | ft
    }
};
