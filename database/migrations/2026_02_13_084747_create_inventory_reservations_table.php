<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory_reservations', function (Blueprint $table) {
            $table->id();

            // Polymorphic relation to Product or ProductVariant
            $table->morphs('reservable');

            // The order that reserved this stock
            $table->foreignId('order_id')->constrained()->onDelete('cascade');

            // Quantity reserved
            $table->integer('quantity')->unsigned();

            // When this reservation expires (typically matches payment expiry)
            $table->timestamp('expires_at');

            $table->timestamps();

            // Indexes for performance
            $table->index('expires_at');
            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_reservations');
    }
};
