<?php

namespace Database\Seeders;

use App\Models\CarrierRate;
use App\Models\CarrierZone;
use App\Models\DeliveryZone;
use App\Models\ShippingCarrier;
use App\Models\ShippingMethod;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

/**
 * Seeds the full logistics setup to reflect how the system works:
 *
 *  CARRIER (who delivers)  →  invisible to customer
 *     ↓ covers
 *  ZONE (where)            →  polygon resolved from customer address
 *     ↓ priced via
 *  CARRIER RATE            →  fee + ETA for that carrier + zone + method
 *     ↓ exposes
 *  SHIPPING METHOD         →  what the customer sees: "Standard", "Express", "Pickup"
 *
 *  WAREHOUSE               →  where customers collect (always available, regardless of zone)
 *
 * Checkout logic
 *  1. Customer address → point-in-polygon check → resolves a DeliveryZone (or null)
 *  2. Zone found?
 *     YES → find carriers covering that zone → show their methods (Standard, Express)
 *           + always show Pickup option
 *     NO  → show Pickup only ("No delivery to your area yet")
 *  3. Customer picks a method → carrier assigned silently by priority
 *  4. Shipment created — carrier never shown to customer
 */
class LogisticsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedMethods();
        $this->seedCarriers();
        $this->seedWarehouse();
        $this->seedCoverage();
    }

    // ==================================================
    // 1. SHIPPING METHODS (CUSTOMER-FACING LABELS)
    // ==================================================

    private function seedMethods(): void
    {
        $methods = [
            [
                'name' => 'Standard Delivery',
                'slug' => 'standard-delivery',
                'description' => 'Delivered by our team to your door.',
                'type' => 'delivery',
                'sort_order' => 1,
            ],
            [
                'name' => 'Express Delivery',
                'slug' => 'express-delivery',
                'description' => 'Priority same-day delivery.',
                'type' => 'delivery',
                'sort_order' => 2,
            ],
            [
                'name' => 'Pickup',
                'slug' => 'pickup',
                'description' => 'Collect from our warehouse at your convenience.',
                'type' => 'pickup',
                'sort_order' => 3,
            ],
        ];

        foreach ($methods as $data) {
            ShippingMethod::updateOrCreate(['slug' => $data['slug']], array_merge($data, ['is_active' => true]));
        }
    }

    // ==================================================
    // 2. CARRIERS
    // ==================================================

    private function seedCarriers(): void
    {
        // Sheffield Africa Logistics — your own fleet. Handles Nairobi & Surroundings.
        // driver = self_managed means no external API; dispatch is managed internally.
        ShippingCarrier::updateOrCreate(
            ['slug' => 'sheffield'],
            [
                'name' => 'Sheffield Africa Logistics',
                'driver' => 'self_managed',
                'credentials' => null,
                'tracking_url_template' => null,
                'priority' => 10,  // highest priority — own fleet preferred
                'is_active' => true,
                'sort_order' => 1,
            ],
        );

        // Cossim Logistics — future integration for upcountry coverage.
        // Add their zones + rates below when integration is ready.
        ShippingCarrier::updateOrCreate(
            ['slug' => 'cossim'],
            [
                'name' => 'Cossim Logistics',
                'driver' => 'cossim',
                'credentials' => null,  // configure API credentials in admin
                'tracking_url_template' => null,
                'priority' => 5,
                'is_active' => false,   // activate after credentials + zones configured
                'sort_order' => 2,
            ],
        );

        // Fargo Courier — manual waybill workflow (no public API).
        // Staff create waybills on the Fargo portal and enter the number manually.
        ShippingCarrier::updateOrCreate(
            ['slug' => 'fargo'],
            [
                'name' => 'Fargo Courier',
                'driver' => 'fargo',
                'credentials' => null,
                'tracking_url_template' => null,
                'priority' => 3,
                'is_active' => false,   // activate when ready to use
                'sort_order' => 3,
            ],
        );

    }

    // ==================================================
    // 3. WAREHOUSE
    // ==================================================

    private function seedWarehouse(): void
    {
        // Sheffield Africa Logistics HQ — the only pickup location for now.
        // When a second warehouse opens, add it here and customers will be
        // able to choose which warehouse to collect from at checkout.
        Warehouse::updateOrCreate(
            ['slug' => 'nairobi-hq'],
            [
                'name' => 'Sheffield Africa Logistics — Nairobi HQ',
                'description' => 'Our main warehouse and collection point in Nairobi.',
                'address' => 'Industrial Area, Enterprise Road',
                'city' => 'Nairobi',
                'county' => 'Nairobi',
                'latitude' => -1.3070,
                'longitude' => 36.8483,
                'phone' => '+254 700 000 000',
                'email' => 'warehouse@sheffieldafrica.com',
                'is_active' => true,
                'sort_order' => 1,
            ],
        );
    }

    // ==================================================
    // 4. ZONE COVERAGE + RATES
    // ==================================================

    private function seedCoverage(): void
    {
        $sheffield = ShippingCarrier::where('slug', 'sheffield')->first();
        $nairobi = DeliveryZone::where('name', 'Nairobi & Surroundings')->first();

        $standard = ShippingMethod::where('slug', 'standard-delivery')->first();
        $express = ShippingMethod::where('slug', 'express-delivery')->first();

        if (! $sheffield || ! $nairobi || ! $standard || ! $express) {
            $this->command->warn('LogisticsSeeder: missing carrier, zone or method — skipping coverage seed.');

            return;
        }

        // Sheffield covers the Nairobi & Surroundings zone.
        CarrierZone::updateOrCreate(
            ['carrier_id' => $sheffield->id, 'delivery_zone_id' => $nairobi->id],
            ['is_active' => true],
        );

        // Sheffield — Standard Delivery in Nairobi
        // KES 350, free over KES 15,000. Same day.
        CarrierRate::updateOrCreate(
            [
                'carrier_id' => $sheffield->id,
                'delivery_zone_id' => $nairobi->id,
                'shipping_method_id' => $standard->id,
            ],
            [
                'rate_type' => 'fixed',
                'base_rate_cents' => 35000,    // KES 350
                'free_over_cents' => 1500000,  // free over KES 15,000
                'eta_min_days' => 0,
                'eta_max_days' => 0,
                'eta_label' => 'Same day',
                'is_active' => true,
                'sort_order' => 1,
            ],
        );

        // Sheffield — Express Delivery in Nairobi
        // KES 600, no free-over. Within 4 hours.
        CarrierRate::updateOrCreate(
            [
                'carrier_id' => $sheffield->id,
                'delivery_zone_id' => $nairobi->id,
                'shipping_method_id' => $express->id,
            ],
            [
                'rate_type' => 'fixed',
                'base_rate_cents' => 60000,    // KES 600
                'free_over_cents' => null,
                'eta_min_days' => 0,
                'eta_max_days' => 0,
                'eta_label' => 'Within 4 hours',
                'is_active' => true,
                'sort_order' => 2,
            ],
        );

        // ==================================================
        // WHEN COSSIM IS INTEGRATED
        // ==================================================
        // Add their upcountry zones to delivery_zones, then uncomment:
        //
        // $cossim = ShippingCarrier::where('slug', 'cossim')->first();
        // $upCountryZone = DeliveryZone::where('name', 'Upcountry')->first();
        //
        // CarrierZone::updateOrCreate([
        //     'carrier_id' => $cossim->id, 'delivery_zone_id' => $upCountryZone->id,
        // ], ['is_active' => true]);
        //
        // CarrierRate::updateOrCreate([...], ['base_rate_cents' => 80000, ...]);
    }
}
