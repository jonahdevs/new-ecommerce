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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->cascadeOnDelete();
            $table->text('description')->nullable();

            $table->string('image_path', 500)->nullable();
            $table->string('image_webp', 500)->nullable();
            $table->string('image_icon', 500)->nullable();
            $table->string('icon_webp', 500)->nullable();
            $table->text('icon_svg')->nullable();

            $table->string('status')->default('draft');
            $table->integer('sort_order')->default(0);

            // SEO & Meta
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('meta_keywords')->nullable();
            $table->string('canonical_url', 500)->nullable();

            $table->timestamps();

            $table->index(['status', 'sort_order']);
            $table->index('slug');
        });

        Schema::create('category_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false); // main category
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['category_id', 'product_id']);
            $table->index('is_primary');
        });

        Schema::create('category_placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('section');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            // A category can only appear once per section
            $table->unique(['category_id', 'section']);
            $table->index(['section', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_placements');
        Schema::dropIfExists('category_product');
        Schema::dropIfExists('categories');
    }
};
