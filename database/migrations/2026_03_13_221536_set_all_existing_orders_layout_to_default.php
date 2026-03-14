<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * layout_option has no impact on submitted orders. Set all existing to default.
     */
    public function up(): void
    {
        DB::table('orders')->update(['layout_option' => 'cards']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot restore previous values.
    }
};
