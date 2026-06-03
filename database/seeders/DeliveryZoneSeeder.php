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
        // Polygon sourced from johan/world.geo.json (simplified official boundary).
        DeliveryZone::updateOrCreate(
            ['name' => 'Upcountry'],
            [
                'county' => 'Kenya',
                'is_active' => true,
                'sort_order' => 1,
                'priority' => 0,
                'polygon' => [
                    ['lat' => -0.85829,  'lng' => 40.993],
                    ['lat' => -1.68325,  'lng' => 41.58513],
                    ['lat' => -2.08255,  'lng' => 40.88477],
                    ['lat' => -2.49979,  'lng' => 40.63785],
                    ['lat' => -2.57309,  'lng' => 40.26304],
                    ['lat' => -3.27768,  'lng' => 40.12119],
                    ['lat' => -3.68116,  'lng' => 39.80006],
                    ['lat' => -4.34653,  'lng' => 39.60489],
                    ['lat' => -4.67677,  'lng' => 39.20222],
                    ['lat' => -3.67712,  'lng' => 37.76690],
                    ['lat' => -3.09699,  'lng' => 37.69869],
                    ['lat' => -1.05982,  'lng' => 34.07262],
                    ['lat' => -0.95000,  'lng' => 33.903711],
                    ['lat' => 0.109814, 'lng' => 33.893569],
                    ['lat' => 0.515000, 'lng' => 34.18000],
                    ['lat' => 1.17694,  'lng' => 34.67210],
                    ['lat' => 1.90584,  'lng' => 35.03599],
                    ['lat' => 3.05374,  'lng' => 34.59607],
                    ['lat' => 3.55560,  'lng' => 34.47913],
                    ['lat' => 4.249885, 'lng' => 34.00500],
                    ['lat' => 4.847123, 'lng' => 34.620196],
                    ['lat' => 5.50600,  'lng' => 35.298007],
                    ['lat' => 5.338232, 'lng' => 35.817448],
                    ['lat' => 4.776966, 'lng' => 35.817448],
                    ['lat' => 4.447864, 'lng' => 36.159079],
                    ['lat' => 4.447864, 'lng' => 36.855093],
                    ['lat' => 3.598605, 'lng' => 38.120915],
                    ['lat' => 3.58851,  'lng' => 38.43697],
                    ['lat' => 3.61607,  'lng' => 38.67114],
                    ['lat' => 3.50074,  'lng' => 38.89251],
                    ['lat' => 3.42206,  'lng' => 39.559384],
                    ['lat' => 3.83879,  'lng' => 39.85494],
                    ['lat' => 4.25702,  'lng' => 40.76848],
                    ['lat' => 3.91909,  'lng' => 41.17180],
                    ['lat' => 3.918912, 'lng' => 41.855083],
                    ['lat' => 2.78452,  'lng' => 40.98105],
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
