<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Unify order day limit keys: max_orders_per_day → orders_per_day_customer,
     * orders_per_day_admin → orders_per_day_staff.
     */
    public function up(): void
    {
        DB::table('settings')
            ->where('key', 'max_orders_per_day')
            ->update(['key' => 'orders_per_day_customer']);

        DB::table('settings')
            ->where('key', 'orders_per_day_admin')
            ->update(['key' => 'orders_per_day_staff']);
    }

    public function down(): void
    {
        DB::table('settings')
            ->where('key', 'orders_per_day_customer')
            ->update(['key' => 'max_orders_per_day']);

        DB::table('settings')
            ->where('key', 'orders_per_day_staff')
            ->update(['key' => 'orders_per_day_admin']);
    }
};
