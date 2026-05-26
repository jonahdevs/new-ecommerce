<?php

use App\Enums\CategoryStatus;
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

            // SEO & Meta
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('meta_keywords')->nullable();
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

            // A category can only appear once per location
            $table->unique(['category_id', 'location']);
            $table->index(['location', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_placements');
        Schema::dropIfExists('categories');
    }
};
