<?php

use App\Enums\ProductType;
use App\Enums\ProductVisibility;
use App\Enums\StockStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Product types supported:
     *  - simple       → single SKU physical product
     *  - variable     → physical product with attribute-driven variants
     *  - virtual      → service / non-shippable, no file delivery
     *  - downloadable → digital file(s) delivered after purchase
     *  - grouped      → display collection of independent products on one page
     *  - bundled      → sold as one SKU composed of multiple component products
     */
    public function up(): void
    {
        // ----------------------------------------------------------------
        // PRODUCTS  (core table — all types share this)
        // ----------------------------------------------------------------
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->unique()->nullable(); // null for variable/grouped/bundled (children/variants carry SKUs)

            // Type discriminator
            $table->string('type')->default(ProductType::SIMPLE->value);

            // Content
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();

            // Pricing (base price; variants may override; grouped has no direct price)
            $table->unsignedBigInteger('price')->nullable();            // nullable: grouped products have no price of their own
            $table->unsignedBigInteger('sale_price')->nullable();
            $table->unsignedBigInteger('cost_price')->nullable();

            // Tax — references tax_classes table
            $table->boolean('is_taxable')->default(true);
            $table->foreignId('tax_class_id')->nullable()->constrained('tax_classes')->nullOnDelete();

            // Shipping (relevant for simple, variable, bundled)
            // virtual → requires_shipping=false
            // grouped → requires_shipping=false (children manage their own)
            // bundled → true if any component requires shipping (enforced in app layer)
            $table->boolean('requires_shipping')->default(true);
            $table->decimal('weight', 8, 3)->nullable(); // kg
            $table->decimal('length', 8, 3)->nullable(); // cm
            $table->decimal('width', 8, 3)->nullable();
            $table->decimal('height', 8, 3)->nullable();

            // Inventory (used by simple/virtual/downloadable; variable tracks per-variant;
            //            grouped/bundled manage stock at child/component level)
            $table->string('stock_status')->default(StockStatus::IN_STOCK->value);
            $table->unsignedInteger('stock_quantity')->nullable();
            $table->boolean('manage_stock')->default(false);
            $table->boolean('allow_backorder')->default(false);
            $table->unsignedInteger('low_stock_threshold')->nullable();

            // Visibility & status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->string('visibility')->default(ProductVisibility::VISIBLE->value);

            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();

            // Sorting
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });

        // ----------------------------------------------------------------
        // PRODUCT - CATEGORY  (many-to-many)
        // ----------------------------------------------------------------
        Schema::create('category_product', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->primary(['category_id', 'product_id']);
        });

        // ----------------------------------------------------------------
        // PRODUCT IMAGES
        // ----------------------------------------------------------------
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('alt')->nullable();
            $table->boolean('is_cover')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // ----------------------------------------------------------------
        // PRODUCT ATTRIBUTES  (which attributes a variable product uses)
        // ----------------------------------------------------------------
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();

            $table->json('values')->nullable(); // Selected attribute values for this product

            // Control how this attribute behaves for this specific product
            $table->boolean('is_variation_attribute')->default(false); // Used to create variants
            $table->boolean('is_visible')->default(true); // Show on product page
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'attribute_id']);
        });

        // ----------------------------------------------------------------
        // PRODUCT VARIANTS  (for variable products only)
        // ----------------------------------------------------------------
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            $table->string('sku')->unique();
            $table->string('barcode')->nullable();

            // Price override (null = inherit from parent product)
            $table->unsignedBigInteger('price')->nullable();
            $table->unsignedBigInteger('compare_at_price')->nullable();
            $table->unsignedBigInteger('cost_price')->nullable();

            // Inventory
            $table->string('stock_status')->default(StockStatus::IN_STOCK->value);
            $table->unsignedInteger('stock_quantity')->nullable();
            $table->boolean('manage_stock')->default(false);
            $table->boolean('allow_backorder')->default(false);

            // Shipping overrides
            $table->decimal('weight', 8, 3)->nullable();
            $table->decimal('length', 8, 3)->nullable();
            $table->decimal('width', 8, 3)->nullable();
            $table->decimal('height', 8, 3)->nullable();

            // Variant image
            $table->string('image')->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });

        // ----------------------------------------------------------------
        // VARIANT ↔ ATTRIBUTE VALUE  (which values make up a variant)
        // e.g. variant "Red / XL" → two rows here
        // ----------------------------------------------------------------
        Schema::create('product_variant_attribute_values', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_value_id')->constrained()->cascadeOnDelete();
            $table->primary(['product_variant_id', 'attribute_value_id']);
        });

        // ----------------------------------------------------------------
        // DOWNLOADABLE FILES  (for downloadable products only)
        // ----------------------------------------------------------------
        Schema::create('downloadable_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            $table->string('name');                       // display name shown to buyer
            $table->string('file_path');                  // path in private storage
            $table->string('file_name');                  // original file name
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable(); // bytes

            // Access control
            $table->unsignedInteger('download_limit')->nullable();       // null = unlimited
            $table->unsignedInteger('download_expiry_days')->nullable(); // null = never expires

            $table->string('version')->nullable();        // e.g. "2.1.0"
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });

        // ----------------------------------------------------------------
        // ORDER DOWNLOAD ACCESS  (tracks per-order download tokens)
        // ----------------------------------------------------------------
        Schema::create('order_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('downloadable_file_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('order_id');  // FK to orders (add ->constrained() once orders table exists)
            $table->unsignedBigInteger('user_id');   // FK to users

            $table->string('token', 64)->unique();   // signed download URL token
            $table->unsignedInteger('downloads_remaining')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('download_count')->default(0);

            $table->timestamps();

            $table->index(['user_id', 'order_id']);
        });

        // ----------------------------------------------------------------
        // GROUPED PRODUCT ITEMS
        // A grouped product (type=grouped) links to independent child
        // products. Each child is purchased separately at its own price —
        // the group is purely a display/discovery container.
        //
        // Rules enforced at app layer:
        //   • parent must have type=grouped
        //   • child must NOT be type=grouped (no nesting)
        //   • parent requires_shipping=false (children manage their own)
        // ----------------------------------------------------------------
        Schema::create('grouped_product_items', function (Blueprint $table) {
            $table->foreignId('group_product_id')
                ->constrained('products')
                ->cascadeOnDelete();
            $table->foreignId('child_product_id')
                ->constrained('products')
                ->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->primary(['group_product_id', 'child_product_id']);
        });

        // ----------------------------------------------------------------
        // BUNDLE ITEMS
        // A bundled product (type=bundled) is sold as a single SKU composed
        // of specific quantities of component products/variants. Stock is
        // decremented for each component atomically on purchase.
        //
        // Rules enforced at app layer:
        //   • bundle_product_id must have type=bundled
        //   • component must NOT be type=bundled (no nesting)
        //   • bundle requires_shipping=true if ANY component requires it
        //   • stock availability = min(component_stock / quantity) across all items
        // ----------------------------------------------------------------
        Schema::create('bundle_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bundle_product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            // The component product
            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            // Optionally pin to a specific variant (e.g. Blue / M only)
            $table->foreignId('product_variant_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->unsignedInteger('quantity')->default(1);

            // When true the customer can swap or remove this component
            // (supports "build-your-own-bundle" flows)
            $table->boolean('is_optional')->default(false);

            // Override the component's price within this bundle
            // null = use the component's own price (for cost calculations)
            $table->unsignedBigInteger('price_override')->nullable();

            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            // A product/variant combo can only appear once per bundle
            $table->unique(
                ['bundle_product_id', 'product_id', 'product_variant_id'],
                'bundle_items_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bundle_items');
        Schema::dropIfExists('grouped_product_items');
        Schema::dropIfExists('order_downloads');
        Schema::dropIfExists('downloadable_files');
        Schema::dropIfExists('product_variant_attribute_values');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_attributes');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('category_product');
        Schema::dropIfExists('products');
    }
};
