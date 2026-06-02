<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Shipping carriers ──────────────────────────────────────────────
        // The logistics companies that fulfil deliveries. Could be the business's
        // own fleet (Sheffield Africa Logistics → driver: self_managed) or a 3PL
        // integration (Cossim, Fargo, Glovo, DHL, Aramex …).
        //
        // The carrier is NEVER shown to the customer — they only see the method
        // name (Standard Delivery, Express, etc.). The carrier is an operational
        // detail assigned silently by the system.
        //
        // `priority` decides which carrier is assigned when multiple carriers
        // cover the same zone. Highest priority wins.
        Schema::create('shipping_carriers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('driver');                            // self_managed|fargo|cossim|dhl|aramex|glovo
            $table->json('credentials')->nullable();             // encrypted {api_key, secret, pickup_lat…}
            $table->string('tracking_url_template')->nullable(); // "https://track.carrier.co.ke/{number}"
            $table->unsignedTinyInteger('priority')->default(0); // higher = preferred on zone overlap
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // ── 2. Shipping methods ───────────────────────────────────────────────
        // Customer-facing method templates ONLY — no pricing, no zone/carrier
        // links here. These are the labels the customer sees and picks from.
        //
        // type = delivery  → fulfilled via carrier_rates (carrier assigned by zone)
        // type = pickup    → customer collects from a warehouse
        //
        // Examples: Standard Delivery, Express Delivery, Pickup
        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('type')->default('delivery'); // delivery|pickup
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // ── 3. Carrier zones ─────────────────────────────────────────────────
        // Declares which geographic zones a carrier covers. Sheffield covers
        // "Nairobi & Surroundings". Cossim (when added) covers upcountry zones.
        // The checkout reads this to determine which carriers can deliver to a
        // customer's resolved zone.
        Schema::create('carrier_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrier_id')
                ->constrained('shipping_carriers')
                ->cascadeOnDelete();
            $table->foreignId('delivery_zone_id')
                ->constrained('delivery_zones')
                ->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['carrier_id', 'delivery_zone_id']);
        });

        // ── 4. Carrier rates ─────────────────────────────────────────────────
        // The actual price for a specific carrier + zone + method combination.
        // Sheffield charges KES 300 for Standard in Nairobi.
        // Cossim charges KES 800 for Standard in their upcountry zones.
        // Same customer label ("Standard Delivery") — different carrier and price.
        //
        // rate_type:
        //   fixed      → always base_rate_cents (minus free-over threshold)
        //   free       → always 0
        //   calculated → call the carrier API at checkout for a live quote
        Schema::create('carrier_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrier_id')
                ->constrained('shipping_carriers')
                ->cascadeOnDelete();
            $table->foreignId('delivery_zone_id')
                ->constrained('delivery_zones')
                ->cascadeOnDelete();
            $table->foreignId('shipping_method_id')
                ->constrained('shipping_methods')
                ->cascadeOnDelete();

            $table->string('rate_type')->default('fixed'); // fixed|free|calculated
            $table->integer('base_rate_cents')->default(0);
            $table->integer('free_over_cents')->nullable(); // free when cart >= this

            $table->unsignedTinyInteger('eta_min_days')->nullable();
            $table->unsignedTinyInteger('eta_max_days')->nullable();
            $table->string('eta_label')->nullable();        // "Same day", "2–4 days"

            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['carrier_id', 'delivery_zone_id', 'shipping_method_id'], 'carrier_zone_method_unique');
        });

        // ── 5. Warehouses ────────────────────────────────────────────────────
        // Physical stock locations where customers can collect orders.
        // Separate from showrooms (sales/display offices).
        // Currently one: Sheffield Africa Logistics, Nairobi.
        // When a second warehouse opens the customer can choose which to collect from.
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('address');
            $table->string('city')->default('Nairobi');
            $table->string('county')->default('Nairobi');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // ── 6. Shipments ─────────────────────────────────────────────────────
        // One row per order. Tracks the full lifecycle:
        //   pending → booked → picked_up → in_transit → delivered | failed | returned
        // For pickup orders: warehouse_id is set, carrier_id is null.
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shipping_method_id')
                ->nullable()
                ->constrained('shipping_methods')
                ->nullOnDelete();
            $table->foreignId('carrier_id')
                ->nullable()
                ->constrained('shipping_carriers')
                ->nullOnDelete();
            $table->foreignId('warehouse_id')
                ->nullable()
                ->constrained('warehouses')
                ->nullOnDelete();

            $table->string('tracking_number')->nullable();
            $table->string('tracking_url', 500)->nullable();
            $table->string('status')->default('pending');

            $table->string('carrier_booking_ref')->nullable();
            $table->json('carrier_payload')->nullable();

            $table->timestamp('estimated_delivery_at')->nullable();
            $table->timestamp('booked_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ── 7. Wire orders to shipping ────────────────────────────────────────
        // shipping_method_id: what the customer chose (Standard, Express, Pickup)
        // warehouse_id:       which warehouse, set when method type = pickup
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('shipping_method_id')
                ->nullable()
                ->after('delivery_zone_id')
                ->constrained('shipping_methods')
                ->nullOnDelete();
            $table->foreignId('warehouse_id')
                ->nullable()
                ->after('shipping_method_id')
                ->constrained('warehouses')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn('warehouse_id');
            $table->dropForeign(['shipping_method_id']);
            $table->dropColumn('shipping_method_id');
        });

        Schema::dropIfExists('shipments');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('carrier_rates');
        Schema::dropIfExists('carrier_zones');
        Schema::dropIfExists('shipping_methods');
        Schema::dropIfExists('shipping_carriers');
    }
};
