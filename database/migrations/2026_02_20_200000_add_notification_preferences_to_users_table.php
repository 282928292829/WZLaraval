<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('notify_orders')->default(true)->after('last_login_at');
            $table->boolean('notify_promotions')->default(true)->after('notify_orders');
            $table->boolean('notify_whatsapp')->default(true)->after('notify_promotions');
            $table->boolean('unsubscribed_all')->default(false)->after('notify_whatsapp');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['notify_orders', 'notify_promotions', 'notify_whatsapp', 'unsubscribed_all']);
        });
    }
};
