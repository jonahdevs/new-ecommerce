<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ===============================================
        //  1. SHIPPING ZONES
        //      Geographic regions that determine which rate bracket applies.
        //      e.g. "Within Nairobi", "Outside Nairobi"
        // ===============================================

        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');                 // Nairobi CBD, Upcountry, etc.
            $table->string('code')->unique()->nullable();      // Optional: NAI_CBD
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
            $table->index('code');
        });

        // ===============================================
        //  2. COUNTIES
        //     Kenya's 47 counties, Each belongs to a shipping zone,
        //     which determines which rate bracket applies for deliveries going to/from that county.
        // ===============================================

        Schema::create('counties', function (Blueprint $table) {
            $table->id();
            $table->string('name');                  // Nairobi, Kiambu, etc.
            $table->string('code')->unique()->nullable();
            $table->foreignId('shipping_zone_id')->constrained('shipping_zones')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();

            $table->index('shipping_zone_id');
        });

        // ===============================================
        //  3. AREAS
        //     Towns/suburbs/estates within a county.
        //     e.g. Westlands, Rongai, Karen (under Nairobi county)
        // 
        //     An area can optionally override its county's shipping zone
        //     for more granular pricing (e.g. a border town)
        // ===============================================

        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->string('name');                  // Westlands, Rongai
            $table->foreignId('county_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();

            // Optional override: if set, this area uses a different
            // zone than its parent county (e.g. border towns).
            $table->foreignId('shipping_zone_id')->nullable()->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();

            $table->index(['county_id', 'shipping_zone_id']);
            $table->unique(['county_id', 'name']);
        });

        // ===============================================
        //  4. SHIPPING METHODS
        //     The top-level delivery product offered to the customer.
        //
        //      `type` is the CRITICAL field - it tells the rate engine
        //       which pricing strategy to use:
        //
        //        flat   -> weight * zone -> shipping_rates table
        //                  Used for: Same-Day Consolidated, PUS line haul
        //
        //         distance -> vehicle + km -> vehicle_rates table
        //                     Used for: On-Demand Deliveries
        //
        //         pus       -> flat line haul + addon -> shipping_rate_addons
        //                       Used for: Pickup Station / Last-Mile Hubs
        //
        //         `delivery_time_unit` lets Same-Day express its 8-hour
        //          window properly instead of rounding up to "1 day".
        // ===============================================

        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');                 // "Same-Day Delivery", "On-Demand", "Pickup Station"
            $table->string('code')->unique();       // same_day, on_demand, pus

            /**
             * Pricing engine selector
             * flat     = weight-bracket price lookup (shipping_rates)
             * distance = base rate * extra KM calculation (vehicle_rates)
             * pus      = flat line haul + PUS surcharge stacked on top
             */
            $table->enum('type', ['flat', 'distance', 'pus'])->default('flat');

            /**
             * Whether this method can be used for return shipments.
             * Cossim charges returns "as forward logistics" — same rate
             * engine fires, we just flag the direction.
             */
            $table->boolean('supports_returns')->default(false);

            /**
             * hours → Same-Day (8 hours)
             * days  → Standard multi-day delivery
             */
            $table->enum('delivery_time_unit', ['hours', 'days'])->default('days');

            $table->text('description')->nullable();
            $table->string('icon')->nullable();     // For UI (truck, bolt, store)
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
            $table->index('code');
            $table->index('type');
        });

        // ===============================================
        //  5. SHIPPING RATES
        //      Weight-bracket * zone pricing for flat-type methods
        //
        //      Covers Cossim's Same-Day Consolidated model:
        //
        //       Zone           | Small  | Medium | Large  | XL
        //       Within Nairobi | 400    | 800    | 1,200  | 1,800
        //       Outside Nairobi| 600    | 1,200  | 1,800  | 2,700
        //
        //     Also used as the LINE HAUL component of PUS pricing.
        //     The PUS surcharge is stored separately in shipping_rate_addons.
        //
        //     `estimated_time_min/max` works in HOURS when the parent
        //     shipping_method.delivery_time_unit = 'hours', otherwise days.
        // ===============================================

        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shipping_zone_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('shipping_method_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();

            // Weight bracket boundaries (in KG)
            $table->decimal('min_weight', 8, 2);            // e.g. 0.00
            $table->decimal('max_weight', 8, 2)->nullable(); // null = no upper limit (XL tier)

            /**
             * Human-readable label for this weight tier.
             * Displayed in the checkout UI and invoices.
             * e.g. "Small (0 - 5 Kgs)", "Extra Large (Above 60.1 Kgs)"
             */
            $table->string('weight_label')->nullable();

            // Base price for this zone + method + weight combination
            $table->decimal('price', 10, 2);                // e.g. 1200.00

            /**
             * Delivery window in hours or days depending on
             * shipping_methods.delivery_time_unit.
             * e.g. Same-Day: min=6, max=8 (hours)
             *      Standard:  min=1, max=3 (days)
             */
            $table->integer('estimated_days_min')->nullable();
            $table->integer('estimated_days_max')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // No overlapping brackets per zone + method
            $table->unique(
                ['shipping_zone_id', 'shipping_method_id', 'min_weight', 'max_weight'],
                'zone_method_weight_unique'
            );

            $table->index(['shipping_zone_id', 'shipping_method_id', 'is_active'], 'idx_shipping_rates_zone_method_active');
        });

        // ================================================================
        //  6. ADDRESSES
        //     Customer delivery addresses with a snapshot of the
        //     shipping selection at the time of save.
        //
        //     Storing the zone, method, and rate as FKs means you can
        //     recalculate or audit without re-deriving from county/area.
        // ================================================================

        Schema::create('addresses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();

            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone_number');
            $table->string('alternative_phone_number')->nullable();

            $table->foreignId('county_id')->constrained()->restrictOnDelete();
            $table->foreignId('area_id')->nullable()->constrained()->nullOnDelete();

            $table->text('address');
            $table->text('additional_information')->nullable();

            // Derived/snapshotted at address creation
            $table->foreignId('shipping_zone_id')->constrained()->restrictOnDelete();

            $table->foreignId('selected_shipping_method_id')->nullable()->constrained('shipping_methods')->nullOnDelete();
            $table->foreignId('selected_shipping_rate_id')->nullable()->constrained('shipping_rates')->nullOnDelete();

            $table->boolean('is_default')->default(false);

            $table->timestamps();

            $table->index('user_id');
            $table->index(['county_id', 'area_id']);
            $table->index('shipping_zone_id');
            $table->index(['user_id', 'is_default']);
            $table->index('selected_shipping_method_id');
        });

        // ================================================================
        //  7. PICKUP STATIONS
        //     Physical collection points for the PUS (Pickup Station
        //     Services) / Decentralized Last-Mile Hub model.
        //
        //     Customers collect within 7 days.
        //     Coordinates allow map display in the UI.
        // ================================================================
        Schema::create('pickup_stations', function (Blueprint $table) {
            $table->id();
            $table->string('name');                 // "Westlands Station"
            $table->string('code')->unique();         // westlands-station

            $table->foreignId('county_id')->constrained()->restrictOnDelete();
            $table->foreignId('area_id')->nullable()->constrained()->nullOnDelete();

            $table->text('address');
            $table->string('phone')->nullable();

            /**
             * e.g. "Mon-Fri 8am-6pm, Sat 9am-2pm"
             * Stored as text for flexibility — can be parsed/displayed as needed.
             */
            $table->text('operating_hours')->nullable();

            // For map display and distance calculation
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            /**
             * Max days the station holds a parcel before returning it.
             * Cossim's default is 7 days.
             */
            $table->integer('holding_days')->default(7);

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['county_id', 'is_active']);
            $table->index(['area_id', 'is_active']);
        });


        // ================================================================
        //  8. VEHICLE RATES
        //     Powers the On-Demand delivery pricing engine.
        //
        //     This is NOT weight-based — it's a vehicle hire model:
        //       price = base_rate + max(0, actual_km - base_km) × extra_km_rate
        //
        //     Cossim's rates:
        //       Vehicle    | Base Rate | Base KM | Extra KM Rate
        //       Motorbike  | 800       | 30      | 40
        //       Van        | 7,500     | 50      | 70
        //       3T Truck   | 8,500     | 50      | 70
        //       5T Truck   | 10,000    | 50      | 90
        //       7T Truck   | 12,000    | 50      | 90
        //       10T Truck  | 15,000    | 50      | 90
        //
        //     actual_km comes from Google Maps Distance Matrix API at checkout.
        // ================================================================
        Schema::create('vehicle_rates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shipping_method_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /**
             * Enum keeps vehicle types consistent across the system.
             * Extending: add new enum values + seed new rows.
             */
            $table->enum('vehicle_type', [
                'motorbike',
                'van',
                'truck_3t',
                'truck_5t',
                'truck_7t',
                'truck_10t',
            ]);

            $table->string('vehicle_label');                // "Motor Bike", "3T Truck" (for UI)

            $table->decimal('base_rate', 10, 2);            // e.g. 8500.00
            $table->integer('base_km');                     // KMs included in base rate e.g. 50
            $table->decimal('extra_km_rate', 10, 2);        // Per KM beyond base_km e.g. 70.00

            /**
             * Suggested max cargo weight for this vehicle.
             * Used to auto-suggest the right vehicle at checkout.
             * e.g. Motorbike = 5kg, 3T Truck = 3000kg
             */
            $table->decimal('max_weight_kg', 8, 2)->nullable();

            /**
             * Suggested max cargo volume in cubic metres.
             * Optional — for future volumetric pricing support.
             */
            $table->decimal('max_volume_m3', 8, 3)->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['shipping_method_id', 'vehicle_type']);
            $table->index(['shipping_method_id', 'is_active']);
        });


        // ================================================================
        //  9. SHIPPING RATE ADDONS
        //     Enables rate stacking for the PUS model:
        //       Total PUS cost = Line Haul (shipping_rates.price)
        //                      + PUS Surcharge (this table)
        //
        //     Cossim's PUS surcharges:
        //       Small (0-5kg)        → 100
        //       Medium (5.1-20kg)    → 200
        //       Large (20.1-60kg)    → 300
        //       Extra Large (60.1+)  → 400
        //
        //     The addon links to the specific shipping_rate row so
        //     we know exactly which weight tier + zone it applies to.
        //
        //     addon_type is extensible — future surcharges like
        //     fuel or remote area fees can be added without schema changes.
        // ================================================================

        Schema::create('shipping_rate_addons', function (Blueprint $table) {
            $table->id();

            /**
             * The base line haul rate this addon stacks on top of.
             * Deleting a rate cascades to remove its addons.
             */
            $table->foreignId('shipping_rate_id')
                ->constrained('shipping_rates')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->enum('addon_type', [
                'pus',              // Pickup Station surcharge
                'fuel_surcharge',   // Future: dynamic fuel levy
                'remote_area',      // Future: hard-to-reach locations
            ])->default('pus');

            $table->string('label')->nullable();             // "Pickup Station Surcharge"
            $table->decimal('addon_amount', 10, 2);          // e.g. 300.00

            /**
             * Optional: pin this addon to a specific pickup station.
             * NULL = addon applies to ALL stations for this rate.
             * SET  = station-specific surcharge (e.g. remote stations cost more).
             */
            $table->foreignId('pickup_station_id')
                ->nullable()
                ->constrained('pickup_stations')
                ->nullOnDelete();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['shipping_rate_id', 'addon_type', 'is_active']);
            $table->index(['pickup_station_id', 'addon_type']);
        });


        // ================================================================
        //  10. FREE SHIPPING RULES
        //      Promotional free shipping thresholds.
        //      e.g. "Spend KES 5,000+ and get free standard delivery"
        //
        //      Can be scoped to a specific zone and/or method.
        //      Can be scheduled with start/end timestamps.
        // ================================================================

        Schema::create('free_shipping_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');                         // "Christmas Promo 2024"

            // Scope: null = rule applies to all zones/methods
            $table->foreignId('shipping_zone_id')
                ->nullable()
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('shipping_method_id')
                ->nullable()
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->decimal('min_order_amount', 10, 2);     // e.g. 5000.00
            $table->decimal('max_weight', 8, 2)->nullable(); // Weight ceiling for free shipping

            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at']);
            $table->index(['shipping_zone_id', 'is_active']);
            $table->index(['shipping_method_id', 'is_active']);
        });

        // ================================================================
        //  11. DELIVERY ORDERS
        //      The audit trail for every delivery raised in the system.
        //
        //      Captures the full cost breakdown per model so you can
        //      always reconstruct how a price was calculated, even if
        //      rates change later.
        //
        //      Supports reverse logistics via `is_return` flag.
        //      Cossim charges returns "as forward logistics" — same
        //      rate engine, opposite direction.
        //
        //      cost_breakdown JSON examples:
        //
        //      Same-Day Consolidated:
        //      {
        //        "model": "flat",
        //        "weight_kg": 12,
        //        "weight_tier": "Medium (5.1-20 Kgs)",
        //        "zone": "Within Nairobi",
        //        "line_haul": 800,
        //        "total": 800
        //      }
        //
        //      On-Demand:
        //      {
        //        "model": "distance",
        //        "vehicle": "3T Truck",
        //        "distance_km": 70,
        //        "base_km": 50,
        //        "base_rate": 8500,
        //        "extra_km": 20,
        //        "extra_km_rate": 70,
        //        "extra_km_cost": 1400,
        //        "total": 9900
        //      }
        //
        //      PUS:
        //      {
        //        "model": "pus",
        //        "weight_kg": 25,
        //        "weight_tier": "Large (20.1-60 Kgs)",
        //        "zone": "Outside Nairobi",
        //        "line_haul": 1800,
        //        "pus_surcharge": 300,
        //        "station": "Westlands Station",
        //        "total": 2100
        //      }
        // ================================================================

        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->id();

            /**
             * Adjust FK to match your orders table name.
             * Uncomment the constrained() line when orders table exists.
             */
            $table->unsignedBigInteger('order_id');
            // ->constrained('orders')->cascadeOnDelete();

            $table->foreignId('shipping_method_id')
                ->constrained('shipping_methods')
                ->restrictOnDelete();

            $table->foreignId('shipping_zone_id')
                ->constrained('shipping_zones')
                ->restrictOnDelete();

            // ── Flat / PUS method fields ───────────────────────────────
            $table->foreignId('shipping_rate_id')
                ->nullable()
                ->constrained('shipping_rates')
                ->nullOnDelete();

            // ── On-Demand method fields ────────────────────────────────
            $table->foreignId('vehicle_rate_id')
                ->nullable()
                ->constrained('vehicle_rates')
                ->nullOnDelete();

            /**
             * Actual trip distance retrieved from Google Maps API.
             * Used to calculate extra KM charges for On-Demand.
             */
            $table->decimal('distance_km', 8, 2)->nullable();

            // ── PUS method fields ──────────────────────────────────────
            $table->foreignId('pickup_station_id')
                ->nullable()
                ->constrained('pickup_stations')
                ->nullOnDelete();

            // ── Common fields ──────────────────────────────────────────

            /**
             * Full itemised cost breakdown stored as JSON.
             * See docblock above for format per delivery model.
             * This is the source of truth for invoicing.
             */
            $table->json('cost_breakdown')->nullable();

            $table->decimal('shipping_cost', 10, 2);         // Final price charged
            $table->decimal('package_weight_kg', 8, 2)->nullable();

            /**
             * true  = reverse logistics (customer returning to seller)
             * false = forward delivery (seller to customer)
             *
             * Cossim charges returns "as forward logistics",
             * so the same rate engine applies either way.
             */
            $table->boolean('is_return')->default(false);

            $table->enum('status', [
                'pending',           // Order received, not yet collected
                'picked_up',         // Collected from sender
                'in_transit',        // En route to hub or destination
                'out_for_delivery',  // With last-mile rider/driver
                'delivered',         // Successfully delivered
                'failed',            // Delivery attempt failed
                'at_station',        // Arrived at PUS station (awaiting collection)
                'collected',         // Customer collected from PUS station
                'returning',         // Failed delivery being returned to sender
                'returned',          // Returned to sender
                'cancelled',         // Order cancelled before pickup
            ])->default('pending');

            /**
             * Calculated at order creation from the shipping method's
             * estimated_time_min/max and current dispatch time.
             */
            $table->timestamp('estimated_delivery_at')->nullable();
            $table->timestamp('delivered_at')->nullable();

            /**
             * For PUS: deadline before station returns the parcel.
             * Typically: delivered_at + pickup_stations.holding_days
             */
            $table->timestamp('collection_deadline_at')->nullable();

            $table->timestamps();

            $table->index('order_id');
            $table->index(['shipping_method_id', 'status']);
            $table->index(['is_return', 'status']);
            $table->index('status');
            $table->index('pickup_station_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_orders');
        Schema::dropIfExists('free_shipping_rules');
        Schema::dropIfExists('shipping_rate_addons');
        Schema::dropIfExists('vehicle_rates');
        Schema::dropIfExists('pickup_stations');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('shipping_rates');
        Schema::dropIfExists('shipping_methods');
        Schema::dropIfExists('areas');
        Schema::dropIfExists('counties');
        Schema::dropIfExists('shipping_zones');
    }
};
