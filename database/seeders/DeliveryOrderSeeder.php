<?php

namespace Database\Seeders;

use App\Models\DeliveryOrder;
use Illuminate\Database\Seeder;

class DeliveryOrderSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('📦 Seeding delivery orders...');

        // ── Forward deliveries — flat rate ────────────────────────────────────

        // Healthy pipeline spread
        DeliveryOrder::factory()->flat()->pending()->recentDays(3)->count(8)->create();
        DeliveryOrder::factory()->flat()->pickedUp()->recentDays(5)->count(6)->create();
        DeliveryOrder::factory()->flat()->inTransit()->recentDays(7)->count(10)->create();
        DeliveryOrder::factory()->flat()->outForDelivery()->recentDays(2)->count(5)->create();

        // Delivered — spread over 60 days for revenue charts
        DeliveryOrder::factory()->flat()->delivered()->recentDays(60)->count(40)->create();

        // Problem orders
        DeliveryOrder::factory()->flat()->failed()->recentDays(10)->count(4)->create();
        DeliveryOrder::factory()->flat()->returning()->recentDays(7)->count(3)->create();
        DeliveryOrder::factory()->flat()->cancelled()->recentDays(14)->count(5)->create();

        $this->command->info('  ✓ Flat rate orders created');

        // ── Forward deliveries — on-demand (distance) ─────────────────────────

        DeliveryOrder::factory()->distance()->pending()->recentDays(2)->count(3)->create();
        DeliveryOrder::factory()->distance()->inTransit()->recentDays(3)->count(4)->create();
        DeliveryOrder::factory()->distance()->delivered()->recentDays(60)->count(15)->create();
        DeliveryOrder::factory()->distance()->failed()->recentDays(7)->count(2)->create();

        $this->command->info('  ✓ Distance (on-demand) orders created');

        // ── PUS deliveries ────────────────────────────────────────────────────

        // Active at station — normal (within holding window)
        DeliveryOrder::factory()->pus()->atStation()->recentDays(5)->count(8)->create();

        // Overdue — need action (these show up red in PUS tracker)
        DeliveryOrder::factory()->pus()->overdue()->count(3)->create();

        // Collected — happy path completions
        DeliveryOrder::factory()->pus()->collected()->recentDays(30)->count(12)->create();

        // Returning from station
        DeliveryOrder::factory()->pus()->returning()->recentDays(5)->count(2)->create();

        $this->command->info('  ✓ PUS orders created');

        // ── Returns (reverse logistics) ───────────────────────────────────────

        DeliveryOrder::factory()->flat()->return()->pending()->recentDays(5)->count(3)->create();
        DeliveryOrder::factory()->flat()->return()->inTransit()->recentDays(7)->count(4)->create();
        DeliveryOrder::factory()->flat()->return()->returned()->recentDays(30)->count(8)->create();
        DeliveryOrder::factory()->flat()->return()->returning()->recentDays(5)->count(2)->create();

        $this->command->info('  ✓ Return orders created');

        // ── Summary ───────────────────────────────────────────────────────────

        $total = DeliveryOrder::count();
        $forward = DeliveryOrder::where('is_return', false)->count();
        $returns = DeliveryOrder::where('is_return', true)->count();
        $atStation = DeliveryOrder::where('status', 'at_station')->count();
        $overdue = DeliveryOrder::where('status', 'at_station')
            ->where('collection_deadline_at', '<', now())->count();

        $this->command->info('');
        $this->command->info("✅ {$total} delivery orders created");
        $this->command->info("   Forward: {$forward} | Returns: {$returns}");
        $this->command->info("   At Station: {$atStation} ({$overdue} overdue)");
    }
}
