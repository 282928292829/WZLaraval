<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('order_status_automation_rules', function (Blueprint $table) {
            $table->boolean('notify_customer_email')->default(false)->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_status_automation_rules', function (Blueprint $table) {
            $table->dropColumn('notify_customer_email');
        });
    }
};
