<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Attribute;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            AttributeSeeder::class,
            CategorySeeder::class,
            TagSeeder::class,
            ProductSeeder::class,
            ReviewSeeder::class,
        ]);
    }
}
