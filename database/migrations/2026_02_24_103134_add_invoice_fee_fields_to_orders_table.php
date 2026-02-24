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
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('agent_fee', 10, 2)->nullable()->after('total_amount');
            $table->decimal('local_shipping', 10, 2)->nullable()->after('agent_fee');
            $table->decimal('international_shipping', 10, 2)->nullable()->after('local_shipping');
            $table->decimal('photo_fee', 10, 2)->nullable()->after('international_shipping');
            $table->decimal('extra_packing', 10, 2)->nullable()->after('photo_fee');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['agent_fee', 'local_shipping', 'international_shipping', 'photo_fee', 'extra_packing']);
        });
    }
};
