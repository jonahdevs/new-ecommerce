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
        // ── Nairobi & Surroundings ─────────────────────────────────────────────
        // One polygon covering:
        //   Nairobi County (all areas)
        //   Kiambu:   Ruiru, Tatu City, Kamakis, Ngoigwa, Two Rivers/Ruaka, Jomoko, Kikuyu
        //   Kajiando: Kitengela, Rongai, Ngong
        //   Machakos: Mlolongo, Syokimau, Arthi River
        //
        // Points are listed clockwise starting from the north-west corner.
        // Adjust any coordinate in the admin map editor to fine-tune the boundary.
        DeliveryZone::updateOrCreate(
            ['name' => 'Nairobi & Surroundings'],
            [
                'county' => 'Nairobi',
                'is_active' => true,
                'sort_order' => 0,
                'priority' => 10,
                'polygon' => [
                    // NW — Kikuyu / Limuru Road boundary
                    ['lat' => -1.250, 'lng' => 36.580],
                    // N — Ruaka / Two Rivers (Limuru Rd corridor)
                    ['lat' => -1.165, 'lng' => 36.760],
                    // N — Ruiru / Kiambu corridor
                    ['lat' => -1.000, 'lng' => 36.930],
                    // NE — Tatu City / Jomoko / Ngoigwa (northern limit ~lat -1.00)
                    ['lat' => -1.000, 'lng' => 37.070],
                    // E — Kamakis / Eastern Bypass junction
                    ['lat' => -1.200, 'lng' => 37.050],
                    // SE — Mlolongo / Syokimau
                    ['lat' => -1.380, 'lng' => 37.030],
                    // SE — Athi River / Mavoko
                    ['lat' => -1.510, 'lng' => 37.030],
                    // S — Kitengela south boundary
                    ['lat' => -1.555, 'lng' => 36.960],
                    // SW — Kitengela west boundary
                    ['lat' => -1.510, 'lng' => 36.820],
                    // W — Rongai
                    ['lat' => -1.430, 'lng' => 36.695],
                    // W — Ngong
                    ['lat' => -1.380, 'lng' => 36.615],
                    // NW — Ngong Hills / closing back
                    ['lat' => -1.310, 'lng' => 36.580],
                ],
            ],
        );

        // ── Upcountry ──────────────────────────────────────────────────────────
        // Covers all of Kenya outside the Nairobi metro area.
        // Priority 0 means the Nairobi zone (priority 10) always wins on overlap —
        // only addresses that fall outside the Nairobi polygon reach this zone.
        DeliveryZone::updateOrCreate(
            ['name' => 'Upcountry'],
            [
                'county' => 'Kenya',
                'is_active' => true,
                'sort_order' => 1,
                'priority' => 0,
                'polygon' => [
                    ['lat' => 5.10, 'lng' => 33.90], // NW — Kenya border
                    ['lat' => 5.10, 'lng' => 41.90], // NE — Kenya border
                    ['lat' => -4.70, 'lng' => 41.90], // SE — Kenya border
                    ['lat' => -4.70, 'lng' => 33.90], // SW — Kenya border
                ],
            ],
        );

        // ── Launch promotion ───────────────────────────────────────────────────
        // Free delivery everywhere until turned off.
        // Disable (is_active = false) or set ends_at when the promo ends.
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
