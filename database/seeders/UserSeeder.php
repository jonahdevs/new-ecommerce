<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);

        $customer = User::updateOrCreate(
            ['email' => 'customer@sheffield.test'],
            [
                'name' => 'Anita Wanjiru',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]
        );

        $admin = User::updateOrCreate(
            ['email' => 'admin@sheffield.test'],
            [
                'name' => 'Sheffield Admin',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]
        );

        $admin->syncRoles(['admin']);
        $customer->syncRoles([]);
    }
}
