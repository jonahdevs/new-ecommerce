<?php

namespace Database\Seeders;

use App\Models\TaxClass;
use App\Settings\TaxSettings;
use Illuminate\Database\Seeder;

class TaxClassSeeder extends Seeder
{
    /**
     * Standard Kenyan VAT classes. Products with no class of their own fall
     * back to the store default tax class (TaxSettings::$default_tax_class_id),
     * which we point at "Standard rated" here.
     */
    public function run(): void
    {
        $classes = [
            ['name' => 'Standard rated', 'slug' => 'standard-rated', 'rate' => 16.0, 'description' => 'Standard VAT at 16%.'],
            ['name' => 'Zero rated', 'slug' => 'zero-rated', 'rate' => 0.0, 'description' => 'Zero-rated supplies (e.g. exports, certain foodstuffs).'],
            ['name' => 'Exempt', 'slug' => 'exempt', 'rate' => 0.0, 'description' => 'VAT-exempt supplies.'],
        ];

        foreach ($classes as $class) {
            TaxClass::updateOrCreate(['slug' => $class['slug']], $class + ['is_active' => true]);
        }

        $standard = TaxClass::where('slug', 'standard-rated')->first();
        $settings = app(TaxSettings::class);
        $settings->default_tax_class_id = $standard?->id;
        $settings->save();

        $this->command->info('Seeded '.count($classes).' tax classes (default: standard rated).');
    }
}
