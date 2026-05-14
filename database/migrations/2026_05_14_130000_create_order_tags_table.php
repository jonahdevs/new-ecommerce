<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Tags table - stores available tags
        Schema::create('order_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('color', 20)->default('zinc'); // Tailwind color name
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Pivot table for order-tag relationship
        Schema::create('order_order_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_tag_id')->constrained()->cascadeOnDelete();
            $table->foreignId('added_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['order_id', 'order_tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_order_tag');
        Schema::dropIfExists('order_tags');
    }
};
