<?php

use App\Enums\AttributeType;
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
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Color, Size, Material
            $table->string('slug')->unique();
            $table->string('type')->default(AttributeType::SELECT->value); // select, color, button
            $table->string('watch_shape')->nullable();
            $table->string('watch_size')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active']);
        });

        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();

            $table->string('value'); // Red, Blue, Small, Large
            $table->string('label');
            $table->string('slug');
            $table->text('description')->nullable();

            // Visual Display Options
            $table->string('color_code')->nullable(); // For color swatches (#FF0000)
            $table->string('image')->nullable(); // For image swatches

            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['attribute_id', 'slug']);
            $table->index(['attribute_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attribute_values');
        Schema::dropIfExists('attributes');
    }
};
