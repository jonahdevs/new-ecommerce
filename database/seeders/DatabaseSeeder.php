<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call([
            PermissionSeeder::class,
            UserSeeder::class,
            CategorySeeder::class,
            BrandSeeder::class,
            AttributeSeeder::class,
            TaxClassSeeder::class,
            ProductSeeder::class,
            ShowroomSeeder::class,
            PageSeeder::class,
            DeliveryZoneSeeder::class,
            AddressSeeder::class,
            OrderSeeder::class,
            QuoteSeeder::class,
            ReviewSeeder::class,
        ]);
    }
}
