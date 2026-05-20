<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        User::factory()->create([
            'email' => 'customer@sheffieldafrica.com',
            'default_payment_method' => 'mpesa',
        ]);

        User::factory()->create([
            'email' => 'admin@sheffieldafrica.com',
            'is_staff' => true,
        ])->assignRole('admin');

        User::factory()->count(10)->create();

        User::factory()->create([
            'name' => 'Jonah Wakahiu',
            'email' => 'jonah.wakahiu@sheffieldafrica.com',
            'is_staff' => true,
        ])->assignRole('super_admin');
    }
}
