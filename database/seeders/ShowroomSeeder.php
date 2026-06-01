<?php

namespace Database\Seeders;

use App\Models\Showroom;
use Illuminate\Database\Seeder;

class ShowroomSeeder extends Seeder
{
    public function run(): void
    {
        $showrooms = [
            [
                'city' => 'Nairobi',
                'country' => 'Kenya',
                'is_hq' => true,
                'address' => 'Off Old Mombasa Road, before the Nairobi SGR Terminus',
                'pobox' => 'P.O. Box 29 – 00606, Nairobi Kenya',
                'phones' => ['+254 713 777 111', '+254 713 444 000'],
                'email' => 'info@sheffieldafrica.com',
            ],
            [
                'city' => 'Mombasa',
                'country' => 'Kenya',
                'is_hq' => false,
                'address' => 'Petrocity Complex 1st Floor, Off Links Road, Nyali',
                'pobox' => null,
                'phones' => ['+254 713 777 111', '+254 713 317 214'],
                'email' => 'mombasa@sheffieldafrica.com',
            ],
            [
                'city' => 'Kampala',
                'country' => 'Uganda',
                'is_hq' => false,
                'address' => 'Bugolobi Hardware City, Block 3 Room 102, Mulwana Road',
                'pobox' => null,
                'phones' => ['+256 741 177 711', '+256 741 177 712'],
                'email' => 'uganda@sheffieldafrica.com',
            ],
            [
                'city' => 'Kigali',
                'country' => 'Rwanda',
                'is_hq' => false,
                'address' => 'Kicukiro Street, KK 500 ST',
                'pobox' => null,
                'phones' => ['+250 794 007 302'],
                'email' => 'rwanda@sheffieldafrica.com',
            ],
        ];

        foreach ($showrooms as $index => $showroom) {
            Showroom::updateOrCreate(
                ['city' => $showroom['city'], 'country' => $showroom['country']],
                [...$showroom, 'sort_order' => $index],
            );
        }
    }
}
