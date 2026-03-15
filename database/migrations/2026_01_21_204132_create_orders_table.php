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

            // document type
            $table->string('document_type')->default('sale_order')->comment('sale_order | quotation');

            // delivery -> admin prices shipping only
            // product -> admin prices line items + shipping
            $table->string('quotation_type')->nullable()->comment('delivery | product - null for sales_order documents');

            // FK to orders.id - set on the converted sales order
            // Pointing back to the original quotation
            // Enables full document chain tracing SO -> QTN
            $table->string('parent_quotation_id')->nullable()->constrained('orders')->nullOnDelete();

            // Set when transitionTo(QUOTE_SENT) fires
            // Used to compute "sent X days ago" and to check whether expires_at has been breached for the QUOTE_EXPIRED transition
            $table->timestamp('quoted_at')->nullable()->comment('Set when admin sends the priced quotation to customer');

            // Path to the generated tax invoice PDF (set after payment confirmed)
            $table->string('invoice_path')->nullable()->comment('Relative path to tax invoice PDF in storage/app/');

            // Path to the generated quotation PDF (set when quote is sent)
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

            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['document_type', 'status'], 'idx_orders_doc_type_status');
            $table->index(['quotation_type', 'status'], 'idx_orders_quote_type_status');
            $table->index('parent_quotation_id', 'idx_orders_parent_quotation');
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
