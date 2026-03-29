<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('reference')->unique();
            $table->string('status')->default('pending');
            $table->string('currency', 3)->default('KES');

            // Amounts — all in cents
            $table->bigInteger('subtotal_cents')->default(0);
            $table->bigInteger('discount_cents')->default(0);
            $table->bigInteger('shipping_cents')->default(0);
            $table->bigInteger('tax_cents')->default(0);
            $table->bigInteger('total_cents')->default(0);

            // Customer preferences for delivery
            $table->string('preferred_county')->nullable();
            $table->string('preferred_area')->nullable();
            $table->text('customer_notes')->nullable();

            // Guest info for unauthenticated quote requests
            $table->json('guest_info')->nullable()->comment('Guest contact: name, email, phone');

            // Admin pricing response
            $table->text('admin_notes')->nullable();
            $table->timestamp('quoted_at')->nullable()->comment('When admin sent the priced quote');
            $table->timestamp('expires_at')->nullable()->comment('Quote validity deadline');
            $table->timestamp('reminder_sent_at')->nullable()->comment('When expiring reminder was sent');

            // Resolution
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            // PDF document
            $table->string('document_path')->nullable()->comment('Path to quote PDF');

            $table->timestamps();

            $table->index('status');
            $table->index('user_id');
            $table->index(['status', 'expires_at']);
        });

        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();

            $table->unsignedBigInteger('quantity');

            // Original price from product (customer's request)
            $table->bigInteger('original_price_cents')->default(0);

            // Admin-set quoted price (may differ from original)
            $table->bigInteger('quoted_price_cents')->nullable();

            $table->bigInteger('discount_cents')->default(0);
            $table->bigInteger('total_cents')->default(0);

            // Snapshot of product at time of quote request
            $table->json('product_snapshot')->nullable();

            $table->timestamps();
        });

        Schema::create('quote_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 50)->nullable();
            $table->string('to_status', 50);
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('changed_by_type', ['user', 'system', 'admin'])->default('user');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('quote_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_status_history');
        Schema::dropIfExists('quote_items');
        Schema::dropIfExists('quotes');
    }
};
