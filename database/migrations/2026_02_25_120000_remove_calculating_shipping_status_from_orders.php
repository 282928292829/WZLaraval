<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("UPDATE orders SET status = 'pending' WHERE status = 'calculating_shipping'");
            DB::statement("ALTER TABLE orders MODIFY status ENUM('pending','needs_payment','processing','purchasing','shipped','delivered','completed','cancelled','on_hold') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY status ENUM('pending','needs_payment','processing','purchasing','calculating_shipping','shipped','delivered','completed','cancelled','on_hold') NOT NULL DEFAULT 'pending'");
        }
    }
};
