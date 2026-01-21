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
        Schema::table('users', function (Blueprint $table) {
            $table->after('email', function (Blueprint $table) {
                $table->string('phone_number', 20)->nullable()->after('email');
                $table->string('address_line1')->nullable()->after('phone_number');
                $table->string('address_line2')->nullable()->after('address_line1');
                $table->string('city')->nullable()->after('address_line2');
                $table->string('state')->nullable()->after('city');
                $table->string('postal_code', 20)->nullable()->after('state');
                $table->string('country')->nullable()->after('postal_code');
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone_number',
                'address_line1',
                'address_line2',
                'city',
                'state',
                'postal_code',
                'country',
            ]);
        });
    }
};
