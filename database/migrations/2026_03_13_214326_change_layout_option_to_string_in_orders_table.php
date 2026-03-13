<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Change layout_option from integer to string (cards, table, hybrid, wizard, cart).
     * Old: 1=Cart, 2=Responsive, 3=Cards. New: string keys.
     */
    public function up(): void
    {
        $legacyToNew = [
            1 => 'cards',
            2 => 'cart',
            3 => 'cards',
            4 => 'wizard',
        ];

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            // Must alter column type first; cannot insert 'cards' into integer column.
            DB::statement("ALTER TABLE orders MODIFY layout_option VARCHAR(50) NOT NULL DEFAULT 'cards'");
            // After alter, existing ints become '1','2','3','4'. Map them to new string keys.
            foreach ($legacyToNew as $old => $new) {
                DB::table('orders')
                    ->where('layout_option', (string) $old)
                    ->update(['layout_option' => $new]);
            }
        } else {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('layout_option', 50)->default('cards')->change();
            });
            foreach ($legacyToNew as $old => $new) {
                DB::table('orders')
                    ->where('layout_option', (string) $old)
                    ->update(['layout_option' => $new]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $newToLegacy = [
            'cards' => 1,
            'table' => 2,
            'hybrid' => 2,
            'wizard' => 4,
            'cart' => 2,
        ];

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            foreach ($newToLegacy as $new => $old) {
                DB::table('orders')
                    ->where('layout_option', $new)
                    ->update(['layout_option' => (string) $old]);
            }
            DB::statement('ALTER TABLE orders MODIFY layout_option TINYINT UNSIGNED NOT NULL DEFAULT 1');
        } else {
            foreach ($newToLegacy as $new => $old) {
                DB::table('orders')
                    ->where('layout_option', $new)
                    ->update(['layout_option' => (string) $old]);
            }
            Schema::table('orders', function (Blueprint $table) {
                $table->unsignedTinyInteger('layout_option')->default(1)->change();
            });
        }
    }
};
