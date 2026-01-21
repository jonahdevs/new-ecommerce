<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            'email' => 'customer@sheffieldafrica.com',
            'default_payment_method' => 'mpesa',
        ])->assignRole('customer');

        User::factory()->create([
            'email' => 'admin@sheffieldafrica.com',
        ])->assignRole('admin');
    }
}
