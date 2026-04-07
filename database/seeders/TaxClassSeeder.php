<?php

namespace Database\Seeders;

use App\Models\TaxClass;
use App\Settings\TaxSettings;
use Illuminate\Database\Seeder;

class TaxClassSeeder extends Seeder
{
    public function run(): void
    {
        $classes = [
            [
                'name' => 'Standard Rate',
                'rate' => 16.00,
                'description' => 'Default VAT rate. Applies to most products.',
            ],
            [
                'name' => 'Zero-Rated',
                'rate' => 0.00,
                'description' => 'Taxable supply but at 0% — still VAT-registered.',
            ],
            [
                'name' => 'Exempt',
                'rate' => 0.00,
                'description' => 'Outside the scope of VAT entirely.',
            ],
        ];

        foreach ($classes as $class) {
            TaxClass::firstOrCreate(['name' => $class['name']], $class);
        }

        // Set Standard Rate as the global default
        $standardRate = TaxClass::where('name', 'Standard Rate')->first();

        if ($standardRate) {
            $settings = app(TaxSettings::class);
            $settings->default_tax_class_id = $standardRate->id;
            $settings->save();
        }
    }
}
