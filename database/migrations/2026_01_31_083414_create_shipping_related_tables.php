<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Status Enum classes to create in app/Enums/:
//
//  LogisticsProviderStatus  → active | inactive | suspended
//  ShippingZoneStatus       → active | inactive
//  ShippingMethodStatus     → active | inactive | deprecated
//  ShippingRateStatus       → active | inactive | expired
//  VehicleRateStatus        → active | inactive | deprecated
//  PickupStationStatus      → active | inactive | temporarily_closed
//  ShippingRateAddonStatus  → active | inactive
//  FreeShippingRuleStatus   → scheduled | active | expired | inactive
//  DeliveryOrderStatus      → pending | picked_up | in_transit | out_for_delivery |
//                             delivered | failed | at_station | collected |
//                             returning | returned | cancelled

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ================================================================
        //  1. LOGISTICS PROVIDERS
        //     Who fulfills deliveries. Starts with just one row: you.
        //
        //     type:
        //       internal → you own and operate the logistics
        //       external → third-party provider (Sendy, DHL, etc.)
        //
        //     Adding a new provider later = insert a row + add their
        //     methods/rates. Nothing else in the schema changes.
        //
        //     Cast: LogisticsProviderStatus
        //       active    → operating normally
        //       inactive  → disabled, hidden from checkout
        //       suspended → operational/billing issue; still referenced
        //                   in historical orders but unavailable at checkout
        // ================================================================

        Schema::create('logistics_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->enum('type', ['internal', 'external'])->default('internal');
            $table->text('description')->nullable();

            // Cast: LogisticsProviderStatus
            $table->string('status')->default('active');

            $table->timestamps();

            $table->index(['status', 'type']);
        });

        // ================================================================
        //  2. SHIPPING ZONES
        //     Geographic regions that determine which rate bracket applies.
        //     e.g. "Within Nairobi", "Outside Nairobi"
        //
        //     Cast: ShippingZoneStatus
        //       active   → usable, shown at checkout
        //       inactive → disabled
        // ================================================================

        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique()->nullable();
            $table->text('description')->nullable();

            // Cast: ShippingZoneStatus
            $table->string('status')->default('active');
            $table->boolean('is_delivery_available')->default(false);

            $table->timestamps();

            $table->index('status');
            $table->index('code');
        });

        // ================================================================
        //  3. COUNTIES
        //     Kenya's 47 counties. Each belongs to a shipping zone,
        //     which determines the rate bracket for deliveries to that county.
        // ================================================================

        Schema::create('counties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique()->nullable();
            $table->foreignId('shipping_zone_id')->constrained('shipping_zones')->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('lat_center', 10, 7)->nullable();
            $table->decimal('lng_center', 10, 7)->nullable();
            $table->timestamps();

            $table->index('shipping_zone_id');
        });

        // ================================================================
        //  4.5 SUB-COUNTIES
        //     Kenya's 290 sub-counties (ADM2) within each county.
        //     Seeded from geoBoundaries ADM2 GeoJSON.
        //     Like areas, can optionally override the county's shipping zone
        //     for granular pricing. Used for point-in-polygon zone resolution
        //     when a customer pins their delivery location on the map.
        // ================================================================

        Schema::create('sub_counties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('county_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();

            // If set, this sub-county uses a different zone than its parent county.
            $table->foreignId('shipping_zone_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();

            // geoBoundaries shapeID for reference / future re-seeding.
            $table->string('shape_id')->nullable();

            $table->decimal('lat_center', 10, 7)->nullable();
            $table->decimal('lng_center', 10, 7)->nullable();

            $table->timestamps();

            $table->unique(['county_id', 'name']);
            $table->index(['county_id', 'shipping_zone_id']);
        });

        // ================================================================
        //  5. SHIPPING METHODS
        //     The delivery products shown to the customer at checkout.
        //     Now provider-aware via logistics_provider_id.
        //
        //     type (pricing engine selector):
        //       flat     → weight bracket lookup    (shipping_rates)
        //       distance → vehicle + km calculation (vehicle_rates)
        //       pus      → flat line haul + addon   (shipping_rate_addons)
        //
        //     Cast: ShippingMethodStatus
        //       active     → available at checkout
        //       inactive   → hidden from checkout
        //       deprecated → no longer selectable, but old orders still
        //                    reference it — do not delete
        // ================================================================

        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();

            $table->foreignId('logistics_provider_id')
                ->constrained('logistics_providers')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('name');
            $table->string('code')->unique();

            $table->enum('type', ['flat', 'distance', 'pus'])->default('flat');

            $table->boolean('supports_returns')->default(false);

            // hours → Same-Day (e.g. 8 hours)
            // days  → Standard multi-day
            $table->enum('delivery_time_unit', ['hours', 'days'])->default('days');

            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->integer('sort_order')->default(0);

            // Cast: ShippingMethodStatus
            $table->string('status')->default('active');

            $table->timestamps();

            $table->index(['status', 'type']);
            $table->index('logistics_provider_id');
            $table->index('code');
        });

        // ================================================================
        //  6. SHIPPING RATES
        //     Weight-bracket × zone pricing for flat and pus methods.
        //
        //     Zone           | Small  | Medium | Large  | XL
        //     Within Nairobi | 400    | 800    | 1,200  | 1,800
        //     Outside Nairobi| 600    | 1,200  | 1,800  | 2,700
        //
        //     Also used as the LINE HAUL component of PUS pricing.
        //     The PUS surcharge stacks on top via shipping_rate_addons.
        //
        //     estimated_days_min/max works in hours when
        //     shipping_methods.delivery_time_unit = 'hours', otherwise days.
        //
        //     Cast: ShippingRateStatus
        //       active   → current rate, used for new orders
        //       inactive → disabled manually
        //       expired  → superseded by a newer rate; kept for
        //                  historical order reference — do not delete
        // ================================================================

        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shipping_zone_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('shipping_method_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->decimal('min_weight', 8, 2);
            $table->decimal('max_weight', 8, 2)->nullable();    // null = no upper limit (XL tier)

            // Human-readable label shown at checkout and on invoices.
            // e.g. "Small (0 – 5 Kgs)", "Extra Large (Above 60.1 Kgs)"
            $table->string('weight_label')->nullable();

            $table->decimal('price', 10, 2);

            // Works in hours or days depending on the method's delivery_time_unit
            $table->integer('estimated_days_min')->nullable();
            $table->integer('estimated_days_max')->nullable();

            // Cast: ShippingRateStatus
            $table->string('status')->default('active');

            $table->timestamps();

            $table->unique(
                ['shipping_zone_id', 'shipping_method_id', 'min_weight', 'max_weight'],
                'zone_method_weight_unique'
            );
            $table->index(
                ['shipping_zone_id', 'shipping_method_id', 'status'],
                'idx_rates_zone_method_status'
            );
        });

        // ================================================================
        //  7. VEHICLE RATES
        //     Powers the On-Demand pricing engine.
        //     Price = base_rate + max(0, actual_km − base_km) × extra_km_rate
        //
        //     Vehicle    | Base Rate | Base KM | Extra KM Rate | Max Weight
        //     Motorbike  | 800       | 30      | 40            | 5 kg
        //     Van        | 7,500     | 50      | 70            | 1,000 kg
        //     3T Truck   | 8,500     | 50      | 70            | 3,000 kg
        //     5T Truck   | 10,000    | 50      | 90            | 5,000 kg
        //     7T Truck   | 12,000    | 50      | 90            | 7,000 kg
        //     10T Truck  | 15,000    | 50      | 90            | 10,000 kg
        //
        //     actual_km comes from Google Maps Distance Matrix API at checkout.
        //
        //     Cast: VehicleRateStatus
        //       active     → available for selection
        //       inactive   → hidden from checkout
        //       deprecated → no longer selectable, kept for historical orders
        // ================================================================

        Schema::create('vehicle_rates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shipping_method_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('vehicle_type');         // Cast: VehicleType enum
            $table->string('vehicle_label');        // "Motor Bike", "3T Truck" (for UI)

            $table->decimal('base_rate', 10, 2);
            $table->integer('base_km');
            $table->decimal('extra_km_rate', 10, 2);

            $table->decimal('max_weight_kg', 8, 2)->nullable();
            $table->decimal('max_volume_m3', 8, 3)->nullable();

            // Cast: VehicleRateStatus
            $table->string('status')->default('active');

            $table->timestamps();

            $table->unique(['shipping_method_id', 'vehicle_type']);
            $table->index(['shipping_method_id', 'status']);
        });

        // ================================================================
        //  8. PICKUP STATIONS
        //     Physical collection points for the PUS model.
        //     Customers collect within holding_days (default 7).
        //     Now provider-aware: a station is operated by a provider.
        //
        //     Cast: PickupStationStatus
        //       active            → open and accepting parcels
        //       inactive          → permanently closed / removed
        //       temporarily_closed → short-term closure (holiday, renovation)
        //                           parcels are not routed here until re-opened
        // ================================================================

        Schema::create('pickup_stations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('logistics_provider_id')->constrained('logistics_providers')->cascadeOnUpdate()->restrictOnDelete();

            $table->string('name');
            $table->string('code')->unique();

            $table->foreignId('county_id')->constrained()->restrictOnDelete();

            $table->foreignId('sub_county_id')->nullable()->constrained('sub_counties')->nullOnDelete();

            $table->text('address');
            $table->string('phone')->nullable();
            $table->text('operating_hours')->nullable();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->integer('holding_days')->default(7);
            $table->boolean('is_primary')->default(false);

            // Cast: PickupStationStatus
            $table->string('status')->default('active');

            $table->timestamps();

            $table->index(['logistics_provider_id', 'status']);
            $table->index(['county_id', 'status']);
            $table->index(['sub_county_id', 'status']);
            $table->index('is_primary');
        });

        // ================================================================
        //  9. SHIPPING RATE ADDONS
        //     Rate stacking for the PUS model:
        //       Total PUS cost = line haul (shipping_rates.price)
        //                      + surcharge (this table)
        //
        //     PUS surcharges:
        //       Small  (0–5 kg)      → 100
        //       Medium (5.1–20 kg)   → 200
        //       Large  (20.1–60 kg)  → 300
        //       XL     (60.1+ kg)    → 400
        //
        //     addon_type is extensible — future surcharges (fuel,
        //     remote area) can be added without any schema changes.
        //
        //     Cast: ShippingRateAddonStatus
        //       active   → applied at checkout
        //       inactive → disabled
        // ================================================================

        Schema::create('shipping_rate_addons', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shipping_rate_id')
                ->constrained('shipping_rates')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Cast: AddonType enum
            // pus | fuel_surcharge | remote_area
            $table->string('addon_type')->default('pus');

            $table->string('label')->nullable();
            $table->decimal('addon_amount', 10, 2);

            // NULL = applies to ALL stations for this rate
            // SET  = station-specific surcharge
            $table->foreignId('pickup_station_id')
                ->nullable()
                ->constrained('pickup_stations')
                ->nullOnDelete();

            // Cast: ShippingRateAddonStatus
            $table->string('status')->default('active');

            $table->timestamps();

            $table->index(['shipping_rate_id', 'addon_type', 'status']);
            $table->index(['pickup_station_id', 'addon_type']);
        });

        // ================================================================
        //  10. FREE SHIPPING RULES
        //      Promotional free shipping thresholds.
        //      e.g. "Spend KES 5,000+ → free standard delivery"
        //
        //      Scoped to a zone and/or method (null = applies to all).
        //      Lifecycle driven by starts_at / ends_at — a scheduled
        //      job transitions status automatically:
        //        scheduled → active (when starts_at is reached)
        //        active    → expired (when ends_at is passed)
        //
        //      Cast: FreeShippingRuleStatus
        //        scheduled → created but start date not yet reached
        //        active    → currently applying at checkout
        //        expired   → end date passed, kept for reporting
        //        inactive  → manually disabled
        // ================================================================

        Schema::create('free_shipping_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            $table->foreignId('shipping_zone_id')->nullable()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('shipping_method_id')->nullable()->constrained()->cascadeOnUpdate()->cascadeOnDelete();

            $table->decimal('min_order_amount', 10, 2);
            $table->decimal('max_weight', 8, 2)->nullable();

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            // Cast: FreeShippingRuleStatus
            $table->string('status')->default('inactive');

            $table->timestamps();

            $table->index(['status', 'starts_at', 'ends_at']);
            $table->index(['shipping_zone_id', 'status']);
            $table->index(['shipping_method_id', 'status']);
        });

        // ================================================================
        //  TOWNS (ADM3)
        //     1,452 ward-level localities within each sub-county.
        //     Seeded from geoBoundaries ADM3 GeoJSON.
        //     Can optionally override the parent sub-county/county zone
        //     for hyper-local pricing (e.g. Rongai, Kiambu town).
        // ================================================================

        Schema::create('towns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('sub_county_id')->constrained()->cascadeOnDelete();
            $table->foreignId('county_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shipping_zone_id')->nullable()->constrained()->nullOnDelete();
            $table->string('shape_id')->nullable()->unique();
            $table->decimal('lat_center', 10, 7)->nullable();
            $table->decimal('lng_center', 10, 7)->nullable();
            $table->timestamps();

            $table->index(['sub_county_id', 'shipping_zone_id']);
            $table->index(['county_id', 'shipping_zone_id']);
        });

        // ================================================================
        //  TOWN BOUNDARIES (ADM3)
        //     GeoJSON polygon per town.
        //     Source: geoBoundaries ADM3 dataset.
        // ================================================================

        Schema::create('town_boundaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('town_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('geojson');
            $table->decimal('bbox_min_lat', 10, 7)->nullable();
            $table->decimal('bbox_max_lat', 10, 7)->nullable();
            $table->decimal('bbox_min_lng', 10, 7)->nullable();
            $table->decimal('bbox_max_lng', 10, 7)->nullable();
            $table->timestamps();

            $table->index(['bbox_min_lat', 'bbox_max_lat', 'bbox_min_lng', 'bbox_max_lng'], 'idx_town_bbox');
        });

        // ================================================================
        //  11. ADDRESSES
        //     Customer delivery addresses.
        //     Stores the resolved shipping zone so we don't have to
        //     re-derive it from county/area on every order.
        //
        //     NOTE: We no longer snapshot selected_shipping_method_id or
        //     selected_shipping_rate_id here — those belong on the order,
        //     not the address. An address is just a location.
        // ================================================================

        Schema::create('addresses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();

            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone_number');
            $table->string('alternative_phone_number')->nullable();

            $table->foreignId('county_id')->constrained()->restrictOnDelete();

            // Resolved via point-in-polygon when customer pins their location.
            $table->foreignId('sub_county_id')->nullable()->constrained('sub_counties')->nullOnDelete();

            // ADM3 town-level resolution (more granular than sub-county).
            $table->foreignId('town_id')->nullable()->constrained('towns')->nullOnDelete();

            $table->text('address');
            $table->text('additional_information')->nullable();

            // Resolved at save time: sub_county zone override → county zone.
            $table->foreignId('shipping_zone_id')->constrained()->restrictOnDelete();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->boolean('is_default')->default(false);

            $table->timestamps();

            $table->index('user_id');
            $table->index(['county_id', 'sub_county_id']);
            $table->index('shipping_zone_id');
            $table->index(['user_id', 'is_default']);
        });

        // ================================================================
        //  12. DELIVERY ORDERS
        //     Audit trail for every delivery in the system.
        //     Now provider-aware and leaner — no nullable god-columns.
        //
        //     cost_breakdown JSON carries all the model-specific detail:
        //
        //     Same-Day / Flat:
        //     {
        //       "model": "flat",
        //       "weight_kg": 12,
        //       "weight_tier": "Medium (5.1–20 Kgs)",
        //       "zone": "Within Nairobi",
        //       "line_haul": 800,
        //       "total": 800
        //     }
        //
        //     On-Demand:
        //     {
        //       "model": "distance",
        //       "vehicle": "3T Truck",
        //       "distance_km": 70,
        //       "base_km": 50,
        //       "base_rate": 8500,
        //       "extra_km": 20,
        //       "extra_km_rate": 70,
        //       "extra_km_cost": 1400,
        //       "total": 9900
        //     }
        //
        //     PUS:
        //     {
        //       "model": "pus",
        //       "weight_kg": 25,
        //       "weight_tier": "Large (20.1–60 Kgs)",
        //       "zone": "Outside Nairobi",
        //       "line_haul": 1800,
        //       "pus_surcharge": 300,
        //       "station": "Westlands Station",
        //       "total": 2100
        //     }
        //
        //     Cast: DeliveryOrderStatus
        //       pending          → received, not yet collected
        //       picked_up        → collected from sender
        //       in_transit       → en route to hub or destination
        //       out_for_delivery → with last-mile rider/driver
        //       delivered        → successfully delivered
        //       failed           → delivery attempt failed
        //       at_station       → arrived at PUS station, awaiting collection
        //       collected        → customer collected from PUS station
        //       returning        → failed delivery being returned to sender
        //       returned         → back with sender
        //       cancelled        → cancelled before pickup
        // ================================================================

        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->id();

            // Update to ->constrained('orders') when your orders table exists
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('logistics_provider_id')->nullable()->constrained('logistics_providers')->restrictOnDelete();
            $table->foreignId('shipping_method_id')->nullable()->constrained('shipping_methods')->nullOnDelete();
            $table->foreignId('shipping_zone_id')->nullable()->constrained('shipping_zones')->nullOnDelete();

            // -- Rate references (one will be set depending on method type) --
            // These are nullable by design: cost_breakdown JSON is the source
            // of truth. These FKs exist for querying and reporting.

            $table->foreignId('shipping_rate_id')->nullable()->constrained('shipping_rates')->nullOnDelete();  // flat + pus
            $table->foreignId('vehicle_rate_id')->nullable()->constrained('vehicle_rates')->nullOnDelete(); // distance / on-demand
            $table->foreignId('pickup_station_id')->nullable()->constrained('pickup_stations')->nullOnDelete(); // pus

            // Actual distance from Google Maps (on-demand only)
            $table->decimal('distance_km', 8, 2)->nullable();

            // -- Costing --

            // Full itemised breakdown — source of truth for invoicing
            $table->json('cost_breakdown')->nullable();

            $table->decimal('shipping_cost', 10, 2);
            $table->decimal('package_weight_kg', 8, 2)->nullable();

            // -- Logistics flags --

            // true = reverse logistics (customer → seller)
            // Cossim charges returns same as forward, so the same
            // rate engine fires either way.
            $table->boolean('is_return')->default(false);

            // For external providers: their job/tracking reference
            $table->string('provider_reference')->nullable();

            // Cast: DeliveryOrderStatus
            $table->string('status')->default('pending');

            // -- Timestamps --

            $table->timestamp('estimated_delivery_at')->nullable();
            $table->timestamp('delivered_at')->nullable();

            // PUS: deadline before the station returns the parcel
            // Typically: delivered_at + pickup_stations.holding_days
            $table->timestamp('collection_deadline_at')->nullable();

            $table->timestamps();

            $table->index('order_id');
            $table->index('logistics_provider_id');
            $table->index(['shipping_method_id', 'status']);
            $table->index(['is_return', 'status']);
            $table->index('status');
            $table->index('pickup_station_id');
            $table->index('provider_reference');
        });

        Schema::create('county_boundaries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('county_id')->unique()->constrained('counties')->cascadeOnUpdate()->cascadeOnDelete(); // one boundary row per county

            // Full GeoJSON geometry for the county polygon.
            // Source: geoBoundaries ADM1 dataset.
            $table->json('geojson');

            // Bounding box — precomputed for fast map viewport fitting.
            $table->decimal('bbox_min_lat', 10, 7)->nullable();
            $table->decimal('bbox_max_lat', 10, 7)->nullable();
            $table->decimal('bbox_min_lng', 10, 7)->nullable();
            $table->decimal('bbox_max_lng', 10, 7)->nullable();

            $table->timestamps();

            $table->index('county_id');
        });

        // ================================================================
        //  SUB-COUNTY BOUNDARIES
        //     GeoJSON polygon per sub-county.
        //     Source: geoBoundaries ADM2 dataset.
        //     Used for server-side point-in-polygon zone resolution when
        //     a customer's address includes a lat/lng pin.
        // ================================================================

        Schema::create('sub_county_boundaries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sub_county_id')->unique()->constrained('sub_counties')->cascadeOnUpdate()->cascadeOnDelete();

            // Full GeoJSON geometry for the sub-county polygon.
            $table->json('geojson');

            // Bounding box — precomputed for fast bbox pre-filter before polygon check.
            $table->decimal('bbox_min_lat', 10, 7)->nullable();
            $table->decimal('bbox_max_lat', 10, 7)->nullable();
            $table->decimal('bbox_min_lng', 10, 7)->nullable();
            $table->decimal('bbox_max_lng', 10, 7)->nullable();

            $table->timestamps();

            $table->index(['bbox_min_lat', 'bbox_max_lat', 'bbox_min_lng', 'bbox_max_lng'], 'idx_sub_county_bbox');
        });

        // ================================================================
        //  Add preferred shipping method to users
        // ================================================================

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('preferred_shipping_method_id')->nullable()->constrained('shipping_methods')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['preferred_shipping_method_id']);
            $table->dropColumn('preferred_shipping_method_id');
        });

        Schema::dropIfExists('town_boundaries');
        Schema::dropIfExists('towns');
        Schema::dropIfExists('sub_county_boundaries');
        Schema::dropIfExists('county_boundaries');
        Schema::dropIfExists('delivery_orders');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('free_shipping_rules');
        Schema::dropIfExists('shipping_rate_addons');
        Schema::dropIfExists('pickup_stations');
        Schema::dropIfExists('vehicle_rates');
        Schema::dropIfExists('shipping_rates');
        Schema::dropIfExists('shipping_methods');
        Schema::dropIfExists('sub_counties');  // towns dropped first (FK dependency)
        Schema::dropIfExists('counties');
        Schema::dropIfExists('shipping_zones');
        Schema::dropIfExists('logistics_providers');
    }
};
