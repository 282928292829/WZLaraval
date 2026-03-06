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
            $table->string('trigger_type', 20)->default('status')->after('id');
            $table->string('last_comment_from', 20)->nullable()->after('trigger_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_status_automation_rules', function (Blueprint $table) {
            $table->dropColumn(['trigger_type', 'last_comment_from']);
        });
    }
};
