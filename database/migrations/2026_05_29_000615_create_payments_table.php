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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            // RESTRICT: payment records are financial evidence and must never be
            // silently deleted when an order is removed.
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->string('provider')->default('mpesa');
            $table->string('status')->default('pending');
            $table->bigInteger('amount_cents');
            $table->char('currency', 3)->default('KES');
            $table->string('phone')->nullable();
            $table->string('account_reference');
            // M-Pesa fields
            $table->string('merchant_request_id')->nullable()->index();
            // Unique: provider-issued IDs are used for idempotency on callbacks —
            // duplicates here would allow a callback to be double-processed.
            $table->string('checkout_request_id')->nullable()->unique();
            $table->string('mpesa_receipt')->nullable();
            $table->integer('result_code')->nullable();
            $table->string('result_desc')->nullable();
            // Stripe fields
            // Note: stripe_session_id and stripe_payment_intent_id are unique per
            // payment attempt. stripe_client_secret is intentionally omitted —
            // it is a short-lived credential that must be held in session/cache
            // only, never persisted to the database.
            $table->string('stripe_session_id')->nullable()->unique();
            $table->string('stripe_payment_intent_id')->nullable();
            // Raw webhook/callback body for audit and replay purposes.
            $table->json('payload')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
