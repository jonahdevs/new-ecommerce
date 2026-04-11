<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained()->nullOnDelete();

            $table->string('reference')->unique();

            $table->string('invoice_path')->nullable()->comment('Relative path to tax invoice PDF in storage/app/');

            $table->string('status')->default('pending');
            $table->string('payment_status')->default('pending');
            $table->string('currency', 3)->default('KES');

            $table->bigInteger('subtotal_cents')->default(0);
            $table->bigInteger('discount_cents')->default(0);
            $table->bigInteger('shipping_cents')->default(0);
            $table->bigInteger('tax_cents')->default(0);
            $table->bigInteger('total_cents')->default(0);

            $table->json('shipping_address')->nullable();
            $table->json('billing_address')->nullable();
            $table->json('shipping_snapshot')->nullable();

            $table->json('guest_info')->nullable()->comment('Guest contact details for unauthenticated orders');
            $table->text('customer_notes')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('courier_name')->nullable();

            $table->string('preferred_county')->nullable();
            $table->string('preferred_area')->nullable();

            $table->timestamp('expires_at')->nullable();

            // ---------------------------------------------------------------
            // SAP Business One — document references (named as SAP returns them)
            // ---------------------------------------------------------------
            $table->string('sap_doc_number')->nullable()->comment('SAP DocNum — human-readable document number');
            $table->string('sap_doc_entry')->nullable()->comment('SAP DocEntry — internal SAP primary key');

            $table->string('sap_sync_status')->default('pending')
                ->comment('pending | syncing | failed | cu_pending | cu_received | returned');
            $table->timestamp('sap_synced_at')->nullable();
            $table->unsignedTinyInteger('sap_sync_attempts')->default(0);
            $table->text('sap_sync_error')->nullable();

            // ---------------------------------------------------------------
            // KRA receipt fields
            // ---------------------------------------------------------------
            $table->string('kra_cu_number')->nullable();
            $table->timestamp('kra_validated_at')->nullable();

            $table->timestamps();

            // Single-column indexes
            $table->index('status', 'idx_orders_status');
            $table->index('sap_sync_status', 'idx_orders_sap_sync_status');
            $table->index('sap_doc_entry', 'idx_orders_sap_doc_entry');

            // Composite indexes for common filter + sort patterns
            $table->index(['status', 'created_at'], 'idx_orders_status_created_at');
            $table->index(['payment_status', 'created_at'], 'idx_orders_payment_status_created_at');
            $table->index(['user_id', 'created_at'], 'idx_orders_user_id_created_at');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();

            $table->unsignedBigInteger('quantity');
            $table->bigInteger('unit_price_cents');
            $table->bigInteger('unit_tax_cents')->default(0);
            $table->bigInteger('discount_cents')->default(0);
            $table->bigInteger('total_cents');

            $table->string('uom')->default('PCS')->comment('Unit of measure e.g. PCS, KG, SET');

            $table->json('product_snapshot')->nullable();

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
            $table->timestamps();

            $table->index('order_id');
            $table->index('created_at');
            $table->index('to_status');
            $table->index(['order_id', 'created_at']);
        });

        Schema::create('sap_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();

            $table->string('operation')
                ->comment('create_order | create_invoice | create_payment | cu_webhook | cu_poll');
            $table->string('status')->comment('success | failed | pending');

            $table->string('endpoint')->nullable();
            $table->string('http_method')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->unsignedSmallInteger('http_status_code')->nullable();

            $table->text('error_message')->nullable();
            $table->text('error_trace')->nullable();

            $table->string('sap_document_number')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();

            $table->timestamps();

            $table->index(['order_id', 'operation'], 'idx_sap_logs_order_operation');
            $table->index('created_at', 'idx_sap_logs_created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sap_sync_logs');
        Schema::dropIfExists('order_status_history');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
