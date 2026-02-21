<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('shipping_address_id')
                  ->nullable()
                  ->after('notes')
                  ->constrained('user_addresses')
                  ->nullOnDelete();

            $table->json('shipping_address_snapshot')
                  ->nullable()
                  ->after('shipping_address_id')
                  ->comment('Snapshot of address at order creation time');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shipping_address_id');
            $table->dropColumn('shipping_address_snapshot');
        });
    }
};
