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
        Schema::table('ad_campaigns', function (Blueprint $table) {
            $table->unsignedBigInteger('orders_shipped')->default(0)->after('orders_cancelled');
            $table->unsignedBigInteger('orders_delivered')->default(0)->after('orders_shipped');
        });
    }

    public function down(): void
    {
        Schema::table('ad_campaigns', function (Blueprint $table) {
            $table->dropColumn(['orders_shipped', 'orders_delivered']);
        });
    }
};
