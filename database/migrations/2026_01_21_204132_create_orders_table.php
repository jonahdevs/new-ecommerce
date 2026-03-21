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

            // Guest contact info — populated when user_id is null (guest quotation)
            // Stores: name, email, phone
            // On registration, QuotationService::attachGuestQuotes() matches
            // by email and sets user_id on orphaned quotations
            $table->json('guest_info')->nullable()->comment('Guest contact details for unauthenticated quotations');

            // Quotation notes — customer-supplied message on submission
            $table->text('customer_notes')->nullable()->comment('Free-text notes from customer at quote submission');

            // Preferred delivery info — county + area supplied at submission
            $table->string('preferred_county')->nullable();
            $table->string('preferred_area')->nullable();

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

            $table->index('order_id');
            $table->index('created_at');
            $table->index('to_status');
            $table->index(['order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_history');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
