<?php

use App\Enums\AttributeType;
use App\Enums\CategoryStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('rate', 5, 2)->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->default(AttributeType::SELECT->value);
            $table->string('watch_shape')->nullable();
            $table->string('watch_size')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('is_active');
        });

        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->string('value');
            $table->string('label');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('color_code')->nullable();
            $table->string('image')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['attribute_id', 'slug']);
            $table->index(['attribute_id', 'is_active']);
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('slug')->unique();
            $table->string('logo', 500)->nullable();
            $table->string('website_url', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('canonical_url', 500)->nullable();
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->text('description')->nullable();
            $table->string('image', 500)->nullable();
            $table->string('thumbnail', 500)->nullable();
            $table->string('icon', 500)->nullable();
            $table->text('icon_svg')->nullable();
            $table->string('status')->default('draft');
            $table->integer('sort_order')->default(0);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('canonical_url', 500)->nullable();
            $table->timestamps();

            $table->index(['status', 'sort_order']);
            $table->index('slug');
        });

        Schema::create('category_placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('location');
            $table->integer('sort_order')->default(0);
            $table->string('status')->default(CategoryStatus::DRAFT->value);
            $table->timestamps();

            $table->unique(['category_id', 'location']);
            $table->index(['location', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_placements');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('attribute_values');
        Schema::dropIfExists('attributes');
        Schema::dropIfExists('tax_classes');
    }
};
