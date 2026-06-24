<?php

namespace Database\Seeders;

use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        // ── Percentage discounts ──────────────────────────────────────────────

        // General sitewide discount — no restrictions
        Coupon::create([
            'code' => 'SHEFFIELD10',
            'type' => CouponType::PERCENT,
            'value' => 10,
            'description' => '10% off your entire order. No minimum spend.',
            'is_active' => true,
        ]);

        // Higher discount with a minimum spend requirement
        Coupon::create([
            'code' => 'SAVE20',
            'type' => CouponType::PERCENT,
            'value' => 20,
            'min_subtotal_cents' => 5000000, // KES 50,000 minimum
            'description' => '20% off orders over KES 50,000.',
            'is_active' => true,
        ]);

        // Limited-use flash sale — first 50 customers only
        Coupon::create([
            'code' => 'FLASH50',
            'type' => CouponType::PERCENT,
            'value' => 15,
            'max_uses' => 50,
            'uses_count' => 23, // 23 already used so the limit is visible
            'description' => '15% off — limited to the first 50 customers.',
            'is_active' => true,
        ]);

        // ── Fixed amount discounts ────────────────────────────────────────────

        // Welcome coupon — one use per customer, no minimum
        Coupon::create([
            'code' => 'WELCOME500',
            'type' => CouponType::FIXED,
            'value' => 50000, // KES 500 off
            'max_uses_per_user' => 1,
            'description' => 'KES 500 off your first order. One use per customer.',
            'is_active' => true,
        ]);

        // Larger fixed discount with minimum spend
        Coupon::create([
            'code' => 'BULK2000',
            'type' => CouponType::FIXED,
            'value' => 200000, // KES 2,000 off
            'min_subtotal_cents' => 10000000, // KES 100,000 minimum
            'description' => 'KES 2,000 off orders over KES 100,000.',
            'is_active' => true,
        ]);

        // ── Time-bound coupons ────────────────────────────────────────────────

        // Upcoming — starts next week (not yet valid)
        Coupon::create([
            'code' => 'JULY15',
            'type' => CouponType::PERCENT,
            'value' => 15,
            'starts_at' => now()->addWeek(),
            'expires_at' => now()->addWeeks(3),
            'description' => '15% off — valid for two weeks starting next week.',
            'is_active' => true,
        ]);

        // Expired — validity window has passed
        Coupon::create([
            'code' => 'LAUNCH25',
            'type' => CouponType::PERCENT,
            'value' => 25,
            'starts_at' => now()->subMonths(3),
            'expires_at' => now()->subMonth(),
            'uses_count' => 87,
            'description' => 'Launch promotion — 25% off. This coupon has expired.',
            'is_active' => true,
        ]);

        // ── Inactive coupon ───────────────────────────────────────────────────

        // Manually disabled by admin
        Coupon::create([
            'code' => 'SUSPENDED',
            'type' => CouponType::PERCENT,
            'value' => 30,
            'description' => '30% off — currently disabled by admin.',
            'is_active' => false,
        ]);
    }
}
