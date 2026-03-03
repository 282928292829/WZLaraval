<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('ALTER TABLE order_files ADD COLUMN order_item_id INTEGER NULL REFERENCES order_items(id) ON DELETE SET NULL');
        } else {
            Schema::table('order_files', function (Blueprint $table) {
                $table->foreignId('order_item_id')->nullable()->after('comment_id')->constrained('order_items')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('order_files', function (Blueprint $table) {
                $table->dropColumn('order_item_id');
            });
        } else {
            Schema::table('order_files', function (Blueprint $table) {
                $table->dropForeign(['order_item_id']);
            });
        }
    }
};
