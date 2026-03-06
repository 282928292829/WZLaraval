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
            $table->unsignedSmallInteger('pause_if_no_reply_days')->default(0)->after('hours');
            $table->unsignedSmallInteger('pause_if_no_reply_hours')->default(0)->after('pause_if_no_reply_days');
            $table->boolean('comment_is_internal')->default(false)->after('comment_template');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_status_automation_rules', function (Blueprint $table) {
            $table->dropColumn(['pause_if_no_reply_days', 'pause_if_no_reply_hours', 'comment_is_internal']);
        });
    }
};
