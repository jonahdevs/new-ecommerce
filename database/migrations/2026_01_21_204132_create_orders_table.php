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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference')->unique();
            $table->string('status')->default('pending');
            $table->string('currency', 3)->default('KES');

            $table->bigInteger('subtotal_cents')->default(0);
            $table->bigInteger('discount_cents')->default(0);
            $table->bigInteger('shipping_cents')->default(0);
            $table->bigInteger('tax_cents')->default(0);
            $table->bigInteger('total_cents')->default(0);

            // Shipping Management
            // $table->foreignId('shipping_zone_id')->nullable()->constrained();
            // $table->foreignId('warehouse_id')->nullable()->constrained();
            $table->boolean('is_pickup')->default(false);
            $table->timestamp('pickup_ready_at')->nullable();
            $table->timestamp('pickup_collected_at')->nullable();
            $table->date('estimated_delivery_from')->nullable();
            $table->date('estimated_delivery_to')->nullable();
            $table->date('actual_delivery_date')->nullable();

            $table->json('shipping_address')->nullable();
            $table->json('billing_address')->nullable();

            $table->timestamp('placed_at')->nullable();
            $table->timestamps();

            // Indexes
            // $table->index('shipping_zone_id');
            // $table->index('warehouse_id');
            $table->index('is_pickup');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();

            $table->string('sku')->nullable();
            $table->string('name');
            $table->integer('quantity')->unsigned();

            $table->bigInteger('unit_price_cents');
            $table->bigInteger('unit_tax_cents')->default(0);
            $table->bigInteger('discount_cents')->default(0);
            $table->bigInteger('total_cents');

            $table->timestamps();
        });

        Schema::create('order_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 50)->nullable();
            $table->string('to_status', 50);
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('changed_by_type', ['user', 'system', 'api'])->default('user');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');

            // Indexes for performance
            $table->index('order_id');
            $table->index('created_at');
            $table->index('to_status');
            $table->index(['order_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_status_history');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
