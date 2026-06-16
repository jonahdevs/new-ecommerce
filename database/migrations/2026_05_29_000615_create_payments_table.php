<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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
            // Note: stripe_client_secret is intentionally omitted — it is a
            // short-lived credential that must be held in memory only, never persisted.
            $table->string('stripe_payment_intent_id')->nullable()->unique();
            $table->string('stripe_charge_id')->nullable();
            $table->string('card_brand')->nullable();
            $table->char('card_last4', 4)->nullable();

            // Paystack fields
            // Unique: the reference is our idempotency key for verify + webhook.
            $table->string('paystack_reference')->nullable()->unique();
            // The channel Paystack settled through (card, mobile_money, bank_transfer…).
            $table->string('channel')->nullable();
            $table->string('authorization_code')->nullable();

            // Refund tracking
            $table->bigInteger('refund_cents')->nullable();
            $table->timestamp('refunded_at')->nullable();

            // Raw webhook/callback body for audit and replay purposes. Encrypted
            // at rest (it holds PII) so the type is longText, not json — the
            // ciphertext is an opaque string, not queryable JSON. Pruned after
            // the statutory retention window by `payments:prune-payloads`.
            $table->longText('payload')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
