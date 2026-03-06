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
        try {
            Schema::table('order_status_automation_logs', function (Blueprint $table) {
                $table->dropUnique(['order_id', 'order_status_automation_rule_id']);
            });
        } catch (\Throwable) {
            // Index may not exist (different MySQL version or already dropped)
        }

        Schema::table('order_status_automation_logs', function (Blueprint $table) {
            $table->foreignId('order_comment_id')->nullable()->after('order_status_automation_rule_id')
                ->constrained('order_comments')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_status_automation_logs', function (Blueprint $table) {
            $table->dropForeign(['order_comment_id']);
            $table->dropColumn('order_comment_id');
        });
        Schema::table('order_status_automation_logs', function (Blueprint $table) {
            $table->unique(['order_id', 'order_status_automation_rule_id']);
        });
    }
};
