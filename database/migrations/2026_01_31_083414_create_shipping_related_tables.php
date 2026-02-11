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
        //  1. Shipping Zones (Geographic regions with different rates)
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
        //  2. Counties (47 counties in Kenya)
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
        //  3. Areas (Towns/Suburbs within counties)
        // ===============================================

        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->string('name');                  // Westlands, Rongai
            $table->foreignId('county_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('shipping_zone_id')->nullable()->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();

            $table->index(['county_id', 'shipping_zone_id']);
            $table->unique(['county_id', 'name']);
        });

        // ===============================================
        //  4. Shipping Methods (Standard, Express, Pickup)
        // ===============================================

        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');                 // Standard Delivery, Express Delivery, Pickup Station
            $table->string('code')->unique();       // standard, express, pickup
            $table->text('description')->nullable();
            $table->string('icon')->nullable();     // For UI (truck, bolt, store)
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
            $table->index('code');
        });

        // ===============================================
        //  5. Shipping Rates (Zone + Method + Weight = Price)
        // ===============================================

        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shipping_zone_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('shipping_method_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();

            // Weight range (in KG)
            $table->decimal('min_weight', 8, 2);   // e.g. 0.00
            $table->decimal('max_weight', 8, 2);   // e.g. 5.00

            // Price for this range & zone
            $table->decimal('price', 10, 2);       // e.g. 450.00

            $table->integer('estimated_days_min')->nullable();
            $table->integer('estimated_days_max')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Prevent overlapping weight ranges per zone and method
            $table->unique(
                ['shipping_zone_id', 'shipping_method_id', 'min_weight', 'max_weight'],
                'zone_method_weight_unique'
            );

            $table->index(['shipping_zone_id', 'shipping_method_id', 'is_active'], 'idx_shipping_rates_zone_method_active');
        });

        // ===============================================
        //  6. Customer Addresses
        // ===============================================

        Schema::create('addresses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();

            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone_number');
            $table->string('alternative_phone_number')->nullable();

            $table->foreignId('county_id')->constrained('counties')->restrictOnDelete();
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();

            $table->text('address');
            $table->text('additional_information')->nullable();

            // 🔑 Snapshot of derived data
            $table->foreignId('shipping_zone_id')->constrained('shipping_zones')->restrictOnDelete();
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

        // ===============================================
        // 7. Pickup Stations (For pickup method)
        // ===============================================
        Schema::create('pickup_stations', function (Blueprint $table) {
            $table->id();
            $table->string('name');                 // CBD Pickup Point, Westlands Station
            $table->string('code')->unique();       // cbd-pickup, westlands-station

            $table->foreignId('county_id')->constrained('counties')->restrictOnDelete();
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();

            $table->text('address');
            $table->string('phone')->nullable();
            $table->text('operating_hours')->nullable();

            // Location coordinates for map display
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['county_id', 'is_active']);
            $table->index(['area_id', 'is_active']);
        });

        // ===============================================
        //  8. Free Shipping Rules (Promotional free shipping)
        // ===============================================
        Schema::create('free_shipping_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // ✨ e.g., "Christmas Promo"

            // Optional: restrict free shipping to a zone
            $table->foreignId('shipping_zone_id')->nullable()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('shipping_method_id')->nullable()->constrained()->cascadeOnUpdate()->cascadeOnDelete();

            // Order subtotal threshold
            $table->decimal('min_order_amount', 10, 2); // e.g. 5000.00
            $table->decimal('max_weight', 8, 2)->nullable();

            $table->boolean('is_active')->default(true);

            // Optional scheduling
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at']);
            $table->index(['shipping_zone_id', 'is_active']);
            $table->index(['shipping_method_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('free_shipping_rules');
        Schema::dropIfExists('pickup_stations');
        Schema::dropIfExists('shipping_rates');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('shipping_methods');
        Schema::dropIfExists('areas');
        Schema::dropIfExists('counties');
        Schema::dropIfExists('shipping_zones');
    }
};
