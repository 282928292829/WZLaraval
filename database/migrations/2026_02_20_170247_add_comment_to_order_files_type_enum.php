<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE `order_files` MODIFY `type` ENUM('receipt', 'product_image', 'invoice', 'other', 'comment') NOT NULL DEFAULT 'other'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("UPDATE `order_files` SET `type` = 'other' WHERE `type` = 'comment'");
        DB::statement("ALTER TABLE `order_files` MODIFY `type` ENUM('receipt', 'product_image', 'invoice', 'other') NOT NULL DEFAULT 'other'");
    }
};
