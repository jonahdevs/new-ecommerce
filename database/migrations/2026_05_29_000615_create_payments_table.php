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
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->default('mpesa');
            $table->string('status')->default('pending');
            $table->integer('amount_cents');
            $table->string('phone');
            $table->string('account_reference');
            $table->string('merchant_request_id')->nullable();
            $table->string('checkout_request_id')->nullable()->index();
            $table->string('mpesa_receipt')->nullable();
            $table->integer('result_code')->nullable();
            $table->string('result_desc')->nullable();
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
