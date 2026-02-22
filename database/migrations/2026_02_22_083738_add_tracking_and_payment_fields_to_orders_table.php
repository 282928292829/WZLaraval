<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('tracking_number')->nullable()->after('payment_proof');
            $table->string('tracking_company')->nullable()->after('tracking_number');
            $table->decimal('payment_amount', 10, 2)->nullable()->after('tracking_company');
            $table->date('payment_date')->nullable()->after('payment_amount');
            $table->string('payment_method')->nullable()->after('payment_date');
            $table->string('payment_receipt')->nullable()->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'tracking_number',
                'tracking_company',
                'payment_amount',
                'payment_date',
                'payment_method',
                'payment_receipt',
            ]);
        });
    }
};
