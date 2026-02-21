<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `order_files` MODIFY `type` ENUM('receipt', 'product_image', 'invoice', 'other', 'comment') NOT NULL DEFAULT 'other'");
    }

    public function down(): void
    {
        // Revert rows with 'comment' type to 'other' before removing the value
        DB::statement("UPDATE `order_files` SET `type` = 'other' WHERE `type` = 'comment'");
        DB::statement("ALTER TABLE `order_files` MODIFY `type` ENUM('receipt', 'product_image', 'invoice', 'other') NOT NULL DEFAULT 'other'");
    }
};
