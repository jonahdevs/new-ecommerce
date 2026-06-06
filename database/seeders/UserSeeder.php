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
        // Roles & permissions are provisioned by PermissionSeeder.
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);

        // -------------------------------------------------------
        // Named admins & staff
        // -------------------------------------------------------
        $admins = [
            ['email' => 'admin@sheffield.test',   'name' => 'Sheffield Admin',  'role' => 'admin'],
            ['email' => 'james.mwangi@sheffield.test', 'name' => 'James Mwangi',    'role' => 'admin'],
            ['email' => 'grace.njeri@sheffield.test',  'name' => 'Grace Njeri',     'role' => 'admin'],
            ['email' => 'brian.otieno@sheffield.test', 'name' => 'Brian Otieno',    'role' => 'staff'],
            ['email' => 'linda.kamau@sheffield.test',  'name' => 'Linda Kamau',     'role' => 'staff'],
        ];

        foreach ($admins as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                ]
            );
            $user->syncRoles([$data['role']]);
        }

        // -------------------------------------------------------
        // Named customers
        // -------------------------------------------------------
        $customers = [
            ['email' => 'customer@sheffield.test',       'name' => 'Anita Wanjiru'],
            ['email' => 'peter.kimani@gmail.com',        'name' => 'Peter Kimani'],
            ['email' => 'fatuma.hassan@gmail.com',       'name' => 'Fatuma Hassan'],
            ['email' => 'david.ochieng@gmail.com',       'name' => 'David Ochieng'],
            ['email' => 'sarah.muthoni@gmail.com',       'name' => 'Sarah Muthoni'],
            ['email' => 'john.kariuki@gmail.com',        'name' => 'John Kariuki'],
        ];

        foreach ($customers as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                ]
            );
            $user->syncRoles([]);
        }

        // Extra factory-generated customers for a realistic customer list
        User::factory()->count(10)->create()->each(fn ($u) => $u->syncRoles([]));
    }
}
