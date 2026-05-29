<?php

namespace Database\Seeders;

use App\Enums\DeliveryPromotionEffect;
use App\Enums\DeliveryPromotionScope;
use App\Models\DeliveryPromotion;
use App\Models\DeliveryZone;
use Illuminate\Database\Seeder;

class DeliveryZoneSeeder extends Seeder
{
    public function run(): void
    {
        // Circular zones covering Nairobi County and nearby towns. Coordinates
        // are approximate area centres; radii are sized to overlap so a slightly
        // inaccurate pin still resolves. base_fee_cents is the REAL fee that goes
        // live once the launch promotion below ends.
        $zones = [
            // [name, county, lat, lng, radius_m, base_fee_kes, eta, priority]
            ['Nairobi CBD', 'Nairobi', -1.2864, 36.8172, 4000, 300, 'Same day', 2],
            ['Westlands & Parklands', 'Nairobi', -1.2649, 36.8025, 4500, 350, 'Same day', 1],
            ['Kilimani & Kileleshwa', 'Nairobi', -1.2906, 36.7869, 4000, 350, 'Same day', 1],
            ['Karen & Langata', 'Nairobi', -1.3290, 36.7060, 6500, 500, '1–2 days', 0],
            ['Eastlands', 'Nairobi', -1.2833, 36.8833, 5500, 400, 'Same day', 0],
            ['Embakasi & JKIA', 'Nairobi', -1.3216, 36.9006, 7000, 450, '1–2 days', 0],
            ['Kasarani & Roysambu', 'Nairobi', -1.2200, 36.8960, 6500, 450, '1–2 days', 0],
            ['Ruiru', 'Kiambu', -1.1455, 36.9580, 6000, 700, '1–2 days', 0],
            ['Kiambu Town', 'Kiambu', -1.1714, 36.8356, 5500, 700, '1–2 days', 0],
            ['Kikuyu', 'Kiambu', -1.2467, 36.6636, 5500, 700, '1–2 days', 0],
            ['Thika', 'Kiambu', -1.0333, 37.0693, 7500, 1200, '2–3 days', 0],
            ['Ngong', 'Kajiado', -1.3526, 36.6557, 5500, 700, '1–2 days', 0],
            ['Rongai', 'Kajiado', -1.3833, 36.7500, 5500, 700, '1–2 days', 0],
            ['Kitengela', 'Kajiado', -1.4667, 36.9667, 6500, 900, '2–3 days', 0],
            ['Athi River & Mavoko', 'Machakos', -1.4564, 36.9785, 6500, 900, '2–3 days', 0],
        ];

        foreach ($zones as $index => [$name, $county, $lat, $lng, $radius, $feeKes, $eta, $priority]) {
            DeliveryZone::updateOrCreate(
                ['name' => $name],
                [
                    'county' => $county,
                    'is_active' => true,
                    'sort_order' => $index,
                    'priority' => $priority,
                    'center_lat' => $lat,
                    'center_lng' => $lng,
                    'radius_meters' => $radius,
                    'base_fee_cents' => $feeKes * 100,
                    'free_over_cents' => null,
                    'eta_label' => $eta,
                ],
            );
        }

        // Launch promotion: free delivery everywhere. Flip is_active off (or set
        // ends_at) when the promo ends and the per-zone base fees take over.
        DeliveryPromotion::updateOrCreate(
            ['name' => 'Launch free delivery'],
            [
                'is_active' => true,
                'priority' => 100,
                'scope' => DeliveryPromotionScope::GLOBAL,
                'zone_id' => null,
                'effect' => DeliveryPromotionEffect::FREE,
                'value_cents' => null,
                'percent' => null,
                'min_subtotal_cents' => 0,
                'starts_at' => null,
                'ends_at' => null,
            ],
        );
    }
}
