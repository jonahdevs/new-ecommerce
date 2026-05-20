<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            TaxClassSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            AttributeSeeder::class,
            CategorySeeder::class,
            TagSeeder::class,
            ProductSeeder::class,
            // ReviewSeeder::class,
            ShippingSeeder::class,
            CountyCoordinatesSeeder::class,
            SalesFlowSeeder::class,
            DeliveryOrderSeeder::class,
        ]);
    }
}
