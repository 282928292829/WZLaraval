<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Change layout_option from integer to string (cards, table, hybrid, wizard, cart).
     * layout_option has no impact on submitted orders; set all existing to default.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY layout_option VARCHAR(50) NOT NULL DEFAULT 'cards'");
        } else {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('layout_option', 50)->default('cards')->change();
            });
        }
        DB::table('orders')->update(['layout_option' => 'cards']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        DB::table('orders')->update(['layout_option' => '1']);
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE orders MODIFY layout_option TINYINT UNSIGNED NOT NULL DEFAULT 1');
        } else {
            Schema::table('orders', function (Blueprint $table) {
                $table->unsignedTinyInteger('layout_option')->default(1)->change();
            });
        }
    }
};
