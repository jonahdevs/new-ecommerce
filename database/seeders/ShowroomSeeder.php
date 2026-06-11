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
                'whatsapp' => '+254 711 234 567',
                'hours' => 'Mon–Fri · 8:00 – 17:30 · Sat · 9:00 – 14:00',
                'services' => ['Showroom', 'Warehouse', 'Service & Spares', 'Trade Counter'],
                'latitude' => -1.319400,
                'longitude' => 36.884200,
            ],
            [
                'city' => 'Mombasa',
                'country' => 'Kenya',
                'is_hq' => false,
                'address' => 'Petrocity Complex 1st Floor, Off Links Road, Nyali',
                'pobox' => null,
                'phones' => ['+254 713 777 111', '+254 713 317 214'],
                'email' => 'mombasa@sheffieldafrica.com',
                'whatsapp' => '+254 713 317 214',
                'hours' => 'Mon–Fri · 8:00 – 17:00 · Sat · 9:00 – 13:00',
                'services' => ['Showroom', 'Service & Spares', 'Coastal Logistics'],
                'latitude' => -4.047300,
                'longitude' => 39.663400,
            ],
            [
                'city' => 'Kampala',
                'country' => 'Uganda',
                'is_hq' => false,
                'address' => 'Bugolobi Hardware City, Block 3 Room 102, Mulwana Road',
                'pobox' => null,
                'phones' => ['+256 741 177 711', '+256 741 177 712'],
                'email' => 'uganda@sheffieldafrica.com',
                'whatsapp' => '+256 741 177 711',
                'hours' => 'Mon–Fri · 8:30 – 17:30 · Sat · 9:00 – 13:00',
                'services' => ['Showroom', 'Service & Spares'],
                'latitude' => 0.316300,
                'longitude' => 32.582200,
            ],
            [
                'city' => 'Kigali',
                'country' => 'Rwanda',
                'is_hq' => false,
                'address' => 'Kicukiro Street, KK 500 ST',
                'pobox' => null,
                'phones' => ['+250 794 007 302'],
                'email' => 'rwanda@sheffieldafrica.com',
                'whatsapp' => '+250 794 007 302',
                'hours' => 'Mon–Fri · 8:00 – 17:00 · Sat · 9:00 – 13:00',
                'services' => ['Showroom', 'Service'],
                'latitude' => -1.949900,
                'longitude' => 30.058800,
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
