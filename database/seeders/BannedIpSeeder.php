<?php

namespace Database\Seeders;

use App\Models\BannedIp;
use App\Models\User;
use Illuminate\Database\Seeder;

class BannedIpSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first();

        $bans = [
            [
                'ip_address' => '192.168.1.50',
                'comment' => 'Repeated brute-force login attempts',
                'expires_at' => null,
            ],
            [
                'ip_address' => '10.0.0.99',
                'comment' => 'Spam order submissions',
                'expires_at' => now()->addDays(30),
            ],
            [
                'ip_address' => '203.0.113.45',
                'comment' => 'Scraping product catalogue',
                'expires_at' => null,
            ],
            [
                'ip_address' => '198.51.100.12',
                'comment' => 'Temporary block — suspicious activity',
                'expires_at' => now()->subDay(),
            ],
        ];

        foreach ($bans as $ban) {
            BannedIp::firstOrCreate(
                ['ip_address' => $ban['ip_address']],
                [
                    'comment' => $ban['comment'],
                    'expires_at' => $ban['expires_at'],
                    'created_by_id' => $admin?->id,
                ],
            );
        }
    }
}
