<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Seeder;

class AddressSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'customer@sheffieldafrica.com')->firstOrFail();

        Address::factory()->default()->create([
            'user_id' => $user->id,
            'label' => 'Office',
            'name' => 'Anita Wanjiru',
            'phone' => '+254 712 345 678',
            'line1' => 'Westlands, 14 Muthangari Drive',
        ]);

        Address::factory(2)->create(['user_id' => $user->id]);
    }
}
