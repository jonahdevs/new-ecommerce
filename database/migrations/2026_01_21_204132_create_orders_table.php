<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference')->unique();

            $table->string('document_type')->default('sale_order')->comment('sale_order | quotation');
            $table->string('quotation_type')->nullable()->comment('delivery | product - null for sale_order documents');

            $table->string('parent_quotation_id')->nullable()->constrained('orders')->nullOnDelete();

            $table->timestamp('quoted_at')->nullable()->comment('Set when admin sends the priced quotation to customer');

            $table->string('invoice_path')->nullable()->comment('Relative path to tax invoice PDF in storage/app/');
            $table->string('quotation_pdf_path')->nullable()->comment('Relative path to quotation PDF in storage/app/');

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

            $table->json('guest_info')->nullable()->comment('Guest contact details for unauthenticated quotations');
            $table->text('customer_notes')->nullable()->comment('Free-text notes from customer at quote submission');

            $table->string('preferred_county')->nullable();
            $table->string('preferred_area')->nullable();

            $table->timestamp('expires_at')->nullable();

            // LPO (Local Purchase Order from customer)
            $table->string('lpo_number')->nullable()->comment('Customer LPO reference number');

            // -------------------------------------------------------------------------
            // SAP Business One integration
            // Three separate doc numbers because SAP creates three separate documents:
            //   1. Sales Order  -> sap_order_number
            //   2. A/R Invoice  -> sap_invoice_number
            //   3. Incoming Payment -> sap_payment_number
            // -------------------------------------------------------------------------
            $table->string('sap_order_number')->nullable()->comment('SAP Sales Order DocNum');
            $table->string('sap_invoice_number')->nullable()->comment('SAP A/R Invoice DocNum');
            $table->string('sap_payment_number')->nullable()->comment('SAP Incoming Payment DocNum');

            // Sync lifecycle tracking
            $table->string('sap_sync_status')->default('pending')
                ->comment('pending | syncing | synced | failed | cu_pending | cu_received');
            $table->timestamp('sap_synced_at')->nullable()->comment('When the last successful SAP sync completed');
            $table->unsignedTinyInteger('sap_sync_attempts')->default(0)
                ->comment('Number of sync attempts made; alerts admin at 3');
            $table->text('sap_sync_error')->nullable()->comment('Last error message from a failed sync attempt');

            // -------------------------------------------------------------------------
            // eTIMS device fields — populated by SAP after the eTIMS device processes
            // the invoice. These are the raw device-level values SAP stores.
            // -------------------------------------------------------------------------
            $table->string('etims_cu_serial_no')->nullable()->comment('KRA eTIMS device serial number');
            $table->timestamp('etims_cu_datetime')->nullable()->comment('CU invoice timestamp from KRA');
            $table->text('etims_qr_code')->nullable()->comment('Base64 QR code from eTIMS device');
            $table->string('etims_status')->nullable()->comment('pending | submitted | accepted | failed');

            // -------------------------------------------------------------------------
            // KRA receipt fields — populated via webhook once eTIMS validation succeeds.
            // These drive the customer-facing KRA receipt PDF.
            // -------------------------------------------------------------------------
            $table->string('kra_cu_number')->nullable()->comment('KRA Control Unit number from eTIMS webhook');
            $table->string('kra_invoice_number')->nullable()->comment('KRA invoice reference number');
            $table->timestamp('kra_validated_at')->nullable()->comment('When KRA validation was confirmed');
            $table->string('kra_receipt_path')->nullable()->comment('Relative path to generated KRA receipt PDF');

            $table->timestamps();

            // Indexes
            $table->index(['document_type', 'status'], 'idx_orders_doc_type_status');
            $table->index(['quotation_type', 'status'], 'idx_orders_quote_type_status');
            $table->index('parent_quotation_id', 'idx_orders_parent_quotation');
            $table->index('sap_sync_status', 'idx_orders_sap_sync_status');
            $table->index('sap_invoice_number', 'idx_orders_sap_invoice_number');
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

        // -------------------------------------------------------------------------
        // SAP sync audit log
        // Every SAP API call (outbound) and every webhook (inbound) gets a row here.
        // Used for debugging, compliance, and retry diagnosis.
        // -------------------------------------------------------------------------
        Schema::create('sap_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();

            // Operation type — one of the five SAP operations defined in the TDD
            $table->string('operation')
                ->comment('create_order | create_invoice | create_payment | cu_webhook | cu_poll');

            $table->string('status')->comment('success | failed | pending');

            // HTTP request/response capture
            $table->string('endpoint')->nullable();
            $table->string('http_method')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->unsignedSmallInteger('http_status_code')->nullable();

            // Error detail (populated on failed rows only)
            $table->text('error_message')->nullable();
            $table->text('error_trace')->nullable();

            // The SAP document number returned on success (DocNum)
            $table->string('sap_document_number')->nullable();

            // How long the call took — useful for spotting slow SAP responses
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
