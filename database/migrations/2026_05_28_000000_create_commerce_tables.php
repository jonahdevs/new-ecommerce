<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Atomic per-key counters backing human-facing reference numbers
        // (orders, quotes). generateNumber() locks and increments the row for
        // "{type}:{year}" so concurrent creates can never collide on a sequence
        // the way a count()+1 scan could.
        Schema::create('number_sequences', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->unsignedBigInteger('value')->default(0);
        });

        // Delivery zones are precise polygon geofences drawn by admin on a map.
        // The polygon JSON stores an ordered array of {lat, lng} coordinate pairs.
        // Serviceability is determined by a ray-casting point-in-polygon check.
        // Pricing lives on carrier_rates — the zone is geography only.
        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('county')->default('Nairobi');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->integer('priority')->default(0);
            $table->json('polygon')->nullable(); // [{lat: -1.28, lng: 36.82}, …]
            $table->timestamps();
        });

        // Time-bound overrides layered on top of zone base fees. The launch
        // "free delivery" offer is a single global promotion row.
        Schema::create('delivery_promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->string('scope')->default('global');
            $table->foreignId('zone_id')->nullable()->constrained('delivery_zones')->cascadeOnDelete();
            $table->string('effect');
            $table->integer('value_cents')->nullable();
            $table->unsignedTinyInteger('percent')->nullable();
            $table->integer('min_subtotal_cents')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });

        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('delivery_zone_id')->nullable()->constrained('delivery_zones')->nullOnDelete();
            $table->string('label')->default('Home');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('alternative_phone')->nullable();
            $table->string('line1');
            $table->text('delivery_instructions')->nullable();
            $table->boolean('is_default')->default(false);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            // Kenyan county, reverse-geocoded from the pin (see ResolveAddressCounty job).
            $table->string('county')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            // Nullable + nullOnDelete so deleting a user account preserves order history.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('address_id')->nullable()->constrained()->nullOnDelete();
            // Snapshot: copy address fields at placement so edits/deletes never alter
            // a placed order's shipping destination.
            $table->string('shipping_name')->nullable();
            $table->string('shipping_email')->nullable();
            $table->string('shipping_phone')->nullable();
            $table->string('shipping_line1')->nullable();
            $table->string('shipping_line2')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_state')->nullable();
            $table->string('shipping_postcode')->nullable();
            $table->char('shipping_country', 2)->nullable();
            $table->foreignId('delivery_zone_id')->nullable()->constrained('delivery_zones')->nullOnDelete();
            // Snapshot: preserve zone name even if the zone record is later renamed/deleted.
            $table->string('delivery_zone_name')->nullable();
            $table->string('order_number')->unique();
            $table->string('status')->default('pending');
            // bigInteger avoids the 2,147,483,647-cent (~KES 21 M) ceiling on int.
            $table->bigInteger('subtotal_cents')->default(0);
            $table->bigInteger('vat_cents')->default(0);
            $table->bigInteger('delivery_cents')->default(0);
            $table->bigInteger('installation_cents')->default(0);
            $table->bigInteger('total_cents')->default(0);
            $table->char('currency', 3)->default('KES');
            $table->string('payment_method')->nullable();
            $table->text('notes')->nullable();
            $table->text('staff_notes')->nullable();
            // Lifecycle timestamps — one per stage so queries like "orders shipped
            // today" are instant rather than relying on a status-change audit log.
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            // SAP / KRA — document references and sync lifecycle
            $table->string('sap_doc_entry')->nullable();
            $table->string('sap_doc_number')->nullable();
            $table->string('sap_sync_status')->default('pending');
            $table->timestamp('sap_synced_at')->nullable();
            $table->unsignedTinyInteger('sap_sync_attempts')->default(0);
            $table->text('sap_sync_error')->nullable();
            $table->string('kra_cu_number')->nullable();
            $table->timestamp('kra_validated_at')->nullable();
            $table->string('kra_receipt_path')->nullable();
            // Dispatch documents — generated when order moves to out_for_delivery
            $table->string('packing_list_path')->nullable();
            $table->string('delivery_note_path')->nullable();
            $table->index('sap_sync_status');
            $table->index('sap_doc_entry');
            $table->timestamps();
        });

        Schema::create('status_histories', function (Blueprint $table) {
            $table->id();
            $table->morphs('historyable'); // orders, quotes, etc.
            $table->string('from_status')->nullable(); // null = initial placement
            $table->string('to_status');
            $table->text('note')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('sap_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('operation'); // create_invoice | validate_invoice | cu_webhook | return_webhook
            $table->string('status');    // pending | success | failed
            $table->string('endpoint')->nullable();
            $table->string('http_method')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->unsignedSmallInteger('http_status_code')->nullable();
            $table->text('error_message')->nullable();
            $table->string('sap_document_number')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'operation']);
            $table->index('created_at');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->json('product_snapshot');
            $table->bigInteger('unit_price_cents');
            $table->integer('quantity');
            $table->bigInteger('line_total_cents');
            // Tax snapshot: the rate (%) applied and the tax portion in cents at
            // the time of ordering, so later changes to a tax class never alter
            // historical orders or their invoices.
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->bigInteger('tax_cents')->default(0);
            $table->timestamps();
        });

        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // FK to the order created when a quote is accepted, so conversion rate
            // and the quote↔order chain can be traced. Unique so a quote can never
            // be converted into more than one order (nullable allows many unconverted).
            $table->foreignId('order_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_company')->nullable();
            $table->string('quote_number')->unique();
            // 'draft' is the correct starting state — not 'sent'.
            $table->string('status')->default('draft');
            $table->bigInteger('subtotal_cents')->default(0);
            $table->bigInteger('vat_cents')->default(0);
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->boolean('tax_inclusive')->default(true);
            $table->bigInteger('shipping_cents')->default(0);
            $table->bigInteger('discount_cents')->default(0);
            $table->string('discount_type', 10)->nullable();
            $table->decimal('discount_value', 10, 2)->default(0);
            $table->bigInteger('total_cents')->default(0);
            $table->char('currency', 3)->default('KES');
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->text('terms')->nullable();
            $table->boolean('delivery_required')->default(false);
            $table->text('delivery_address')->nullable();
            $table->string('document_path')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->json('product_snapshot');
            $table->bigInteger('unit_price_cents');
            $table->integer('quantity');
            $table->bigInteger('line_total_cents');
            // Mirror order_items so tax context is preserved when a quote converts.
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->bigInteger('tax_cents')->default(0);
            $table->timestamps();
        });

        // One row per user/product pair; viewed_at updated on every revisit so
        // the list stays sorted by most-recently-viewed without growing unbounded.
        Schema::create('recently_viewed', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamp('viewed_at')->useCurrent()->useCurrentOnUpdate();
            $table->unique(['user_id', 'product_id']);
            $table->index(['user_id', 'viewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recently_viewed');
        Schema::dropIfExists('sap_sync_logs');
        Schema::dropIfExists('status_histories');
        Schema::dropIfExists('quote_items');
        Schema::dropIfExists('quotes');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('delivery_promotions');
        Schema::dropIfExists('delivery_zones');
        Schema::dropIfExists('number_sequences');
    }
};
