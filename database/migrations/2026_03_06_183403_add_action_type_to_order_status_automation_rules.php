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
            $table->string('action_type', 20)->default('comment')->after('comment_is_internal');
            $table->string('action_status', 30)->nullable()->after('action_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_status_automation_rules', function (Blueprint $table) {
            $table->dropColumn(['action_type', 'action_status']);
        });
    }
};
