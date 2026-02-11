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
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            // Basic Information
            $table->string('name');
            $table->string('model_number')->nullable();
            $table->string('slug')->unique();
            $table->string('short_description', 500)->nullable();

            $table->enum('type', ['simple', 'variable'])->default('simple');

            // General
            $table->decimal('price', 10, 2);
            $table->decimal('sale_price', 10, 2)->nullable(); // Discount price
            $table->decimal('cost_price', 10, 2)->nullable(); // Your price

            // Inventory
            $table->string('sku')->nullable();
            $table->boolean('manage_stock')->default(true);
            $table->integer('stock_quantity')->default(0);
            $table->enum('allow_backorder', ['no', 'notify', 'yes'])->default('no');

            // Backorders
            $table->integer('max_backorder_quantity')->nullable();
            $table->date('expected_restock_date')->nullable();
            $table->text('backorder_message')->nullable();

            $table->integer('low_stock_threshold')->default(10);
            $table->enum('stock_status', ['in_stock', 'out_of_stock', 'backorder'])->default('in_stock');
            $table->boolean('sold_individually')->default(false);

            // Shipping
            $table->decimal('weight', 8, 2)->nullable();  // in gms
            $table->decimal('height', 8, 2)->nullable();
            $table->decimal('width', 8, 2)->nullable();
            $table->decimal('length', 8, 2)->nullable();

            $table->text('description')->nullable();

            // SEO fields
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('meta_keywords')->nullable();
            $table->string('canonical_url', 500)->nullable();

            // Status
            $table->enum('status', ['draft', 'scheduled', 'published', 'archived'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_featured')->default(false);

            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();

            // Primary Image
            $table->string('image_path')->nullable();

            // properties
            $table->json('technical_specification')->nullable();

            //  Shipping & Policies
            $table->string('estimated_delivery_time')->nullable();
            $table->string('shipping_information')->nullable();
            $table->string('warranty_information')->nullable();
            $table->string('return_policy')->nullable();


            // Analytics
            $table->integer('views_count')->default(0);
            $table->integer('sales_count')->default(0);
            $table->decimal('average_rating', 3, 2)->nullable();
            $table->integer('reviews_count')->default(0);

            // Quotation Settings
            $table->boolean('requires_quotation')->default(false);
            $table->decimal('min_order_quantity', 10, 2)->nullable();
            $table->text('quotation_notes')->nullable();

            // Foreign Keys

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('status');
            $table->index('type');
            $table->index('is_featured');
            $table->index('requires_quotation');
        });

        // ===============================================
        //  ATTRIBUTES TABLE
        // ===============================================

        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Color, Size, Material
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['select', 'radio', 'checkbox', 'text', 'textarea', 'color', 'swatch', 'button']);

            // Control Flags
            $table->boolean('is_active')->default(true);
            $table->boolean('is_visible')->default(true);
            $table->boolean('used_for_variations')->default(true); // used for variations or just product attributes

            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'used_for_variations']);
        });

        // ===============================================
        // ATTRIBUTE VALUES TABLE
        // ===============================================

        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();

            $table->string('value'); // Red, Blue, Small, Large
            $table->string('label');
            $table->string('slug');

            // Visual Display Options
            $table->string('color_code')->nullable(); // For color swatches (#FF0000)
            $table->string('image_path')->nullable(); // For image swatches

            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['attribute_id', 'slug']);
            $table->index(['attribute_id', 'is_active']);
        });

        // ===============================================
        // TAGS TABLE
        // ===============================================

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#3B82F6');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('is_active');
        });

        // ===============================================
        // PRODUCT VARIANTS TAB
        // ===============================================

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            // Variant identifiers
            $table->string('sku')->unique();
            $table->string('name')->nullable(); // e.g., "Large - Red"
            $table->json('attributes')->nullable(); // Store attribute combinations
            $table->string('barcode')->nullable(); // EAN, UPC, etc.

            // Pricing (can override parent product)
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->decimal('cost_price', 10, 2)->nullable();

            // Stock management
            $table->boolean('manage_stock')->default(true);
            $table->integer('stock_quantity')->default(0);
            $table->integer('low_stock_threshold')->nullable();
            $table->enum('stock_status', ['in_stock', 'out_of_stock', 'backorder'])->default('in_stock');

            // Physical properties (can override parent)
            $table->decimal('weight', 8, 2)->nullable();
            $table->decimal('height', 8, 2)->nullable();
            $table->decimal('width', 8, 2)->nullable();
            $table->decimal('length', 8, 2)->nullable();

            // Backorders
            $table->boolean('allow_backorders')->nullable();
            $table->integer('max_backorder_quantity')->nullable();
            $table->date('expected_restock_date')->nullable();

            // Primary variant image
            $table->string('image_path')->nullable();

            // Control Flags
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->text('description')->nullable();

            $table->timestamps();

            $table->index(['product_id', 'is_active']);
            $table->index('stock_status');
        });

        // ===============================================
        // PRODUCT IMAGES TABLE
        // ===============================================

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();

            $table->string('image_path');
            $table->string('thumbnail_path')->nullable();
            $table->string('alt_text')->nullable();

            $table->integer('sort_order')->default(0);

            $table->timestamps();

            $table->index(['product_id', 'variant_id']);
        });

        // ===============================================
        // PRODUCT DOCUMENTS TABLE
        // ===============================================

        Schema::create('product_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            $table->string('title');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type', 50); // pdf, doc, xls, etc.
            $table->unsignedBigInteger('file_size'); // in bytes
            $table->enum('document_type', [
                'datasheet',
                'manual',
                'warranty',
                'certification',
                'brochure',
                'specification',
                'other',
            ])->default('other');
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(true);
            $table->integer('download_count')->default(0);
            $table->integer('sort_order')->default(0);

            $table->timestamps();

            $table->index(['product_id', 'document_type']);
            $table->index(['product_id', 'is_public']);
        });

        // ===============================================
        // PRODUCT ATTRIBUTES (pivot)
        // Which attributes apply to this product
        // ===============================================

        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->json('values')->nullable(); // Selected attribute values for this product

            // Control how this attribute behaves for this specific product
            $table->boolean('is_variation_attribute')->default(false); // Used to create variants
            $table->boolean('is_visible')->default(true); // Show on product page
            $table->integer('sort_order')->default(0);

            $table->timestamps();

            $table->unique(['product_id', 'attribute_id']);
        });

        // ===============================================
        // PRODUCT ATTRIBUTE VALUES (pivot)
        // Which specific attribute values apply to this product
        // ===============================================

        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_value_id')->constrained()->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['product_id', 'attribute_value_id'], 'product_attr_value_unique');
        });

        // ===============================================
        // PRODUCT VARIANT ATTRIBUTE VALUES
        // Links variants to their specific attribute value combinations
        // ===============================================

        Schema::create('product_variant_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('attribute_value_id')->constrained()->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['variant_id', 'attribute_value_id'], 'variant_attr_value_unique');
        });

        // ===============================================
        // PRODUCT TAG (Pivot)
        // ===============================================

        Schema::create('product_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['product_id', 'tag_id']);
        });

        // ===============================================
        // PRODUCT Relationships
        // ===============================================

        Schema::create('product_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('related_product_id')->constrained('products')->cascadeOnDelete();

            $table->enum('relationship_type', [
                'upsell',         // Higher-end alternative
                'cross_sell',     // Frequently bought together
                'related',        // Related products (similar items)
            ])->default('related');

            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Prevent duplicate relationships
            $table->unique(['product_id', 'related_product_id', 'relationship_type'], 'product_relation_unique');

            // Indexes for performance
            $table->index(['product_id', 'relationship_type']);
            $table->index(['related_product_id', 'relationship_type']);
        });

        // ===============================================
        // RECENTLY VIEWED PRODUCT
        // ===============================================
        Schema::create('recently_viewed_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->timestamp('viewed_at');
            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'viewed_at']);
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recently_viewed_products');
        Schema::dropIfExists('product_related');
        Schema::dropIfExists('product_cross_sells');
        Schema::dropIfExists('product_upsells');
        Schema::dropIfExists('product_accessories');
        Schema::dropIfExists('product_relationships');
        Schema::dropIfExists('product_tag');
        Schema::dropIfExists('product_variant_attribute_values');
        Schema::dropIfExists('product_attribute_values');
        Schema::dropIfExists('product_attributes');
        Schema::dropIfExists('product_documents');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('attribute_values');
        Schema::dropIfExists('attributes');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('products');
    }
};
