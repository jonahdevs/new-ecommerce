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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->cascadeOnDelete();

            // Review Content
            $table->unsignedTinyInteger('rating'); // 1-5 stars
            $table->string('title', 100);
            $table->text('review_text');

            // Status and Verification
            $table->enum('status', ['pending', 'approved', 'rejected', 'flagged'])->default('pending');
            $table->boolean('is_verified_purchase')->default(true);

            // Helpfulness Counters
            $table->unsignedInteger('helpful_count')->default(0);
            $table->unsignedInteger('not_helpful_count')->default(0);

            // Moderation Tracking
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();

            $table->timestamps();

            // Unique Constraint - One review per user per product
            // $table->unique(['user_id', 'product_id'], 'unique_user_product');

            // Indexes for Performance
            $table->index(['product_id', 'status'], 'idx_product_status');
            $table->index(['status', 'created_at'], 'idx_status_created');
            $table->index('rating', 'idx_rating');
        });

        Schema::create('review_images', function (Blueprint $table) {
            $table->id();

            // Foreign Key
            $table->foreignId('review_id')->constrained()->cascadeOnDelete();

            // Image Information
            $table->string('image_path', 255);
            $table->unsignedTinyInteger('order')->default(0);

            $table->timestamps();

            // Index for Performance
            $table->index(['review_id', 'order'], 'idx_review_order');
        });

        Schema::create('review_helpfulness', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('review_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Vote Information
            $table->boolean('is_helpful');

            $table->timestamps();

            // Unique Constraint - One vote per user per review
            $table->unique(['review_id', 'user_id'], 'unique_review_user');

            // Index for Performance
            $table->index(['review_id', 'is_helpful'], 'idx_review_helpful');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_helpfulness');
        Schema::dropIfExists('review_images');
        Schema::dropIfExists('reviews');
    }
};
