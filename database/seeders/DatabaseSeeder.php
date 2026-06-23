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
            TagSeeder::class,
            BrandSeeder::class,
            AttributeSeeder::class,
            TaxClassSeeder::class,
            ProductSeeder::class,
            ShowroomSeeder::class,
            PageSeeder::class,
            DeliveryZoneSeeder::class,
            LogisticsSeeder::class,
            AddressSeeder::class,
            OrderSeeder::class,
            HistoricalOrderSeeder::class,
            QuoteSeeder::class,
            ReviewSeeder::class,
            // BannedIpSeeder::class,
            // Must run last: builds image conversions for all media attached above.
            MediaSeeder::class,
        ]);
    }
}
