<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('content');
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();

            $table->index(['order_id', 'created_at']);
            $table->index(['order_id', 'is_pinned']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_notes');
    }
};
