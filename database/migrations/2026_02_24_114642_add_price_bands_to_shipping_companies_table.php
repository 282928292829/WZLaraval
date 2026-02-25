<?php

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
        Schema::table('shipping_companies', function (Blueprint $table) {
            $table->json('price_bands')->nullable()->after('over21_per_kg')
                ->comment('Weight bands: [{max_weight: 0.5, price: 100}, ...]. Use when non-formula pricing. 500g increments.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipping_companies', function (Blueprint $table) {
            $table->dropColumn('price_bands');
        });
    }
};
