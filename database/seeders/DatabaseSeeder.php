<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Clear generated documents from previous seed runs so they don't accumulate.
        foreach (['packing-lists', 'delivery-notes', 'kra-receipts', 'quotations'] as $dir) {
            Storage::disk('local')->deleteDirectory($dir);
        }

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
            CouponSeeder::class,
            QuoteSeeder::class,
            ReviewSeeder::class,
            // BannedIpSeeder::class,
            // Must run last: builds image conversions for all media attached above.
            MediaSeeder::class,
        ]);
    }
}
