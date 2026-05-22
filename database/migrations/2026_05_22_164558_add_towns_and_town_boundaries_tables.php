<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('towns')) {
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
        }

        if (! Schema::hasTable('town_boundaries')) {
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
        }

        if (! Schema::hasColumn('addresses', 'town_id')) {
            Schema::table('addresses', function (Blueprint $table) {
                $table->foreignId('town_id')->nullable()->constrained('towns')->nullOnDelete()->after('sub_county_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropForeign(['town_id']);
            $table->dropColumn('town_id');
        });

        Schema::dropIfExists('town_boundaries');
        Schema::dropIfExists('towns');
    }
};
