<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_activity_logs', function (Blueprint $table) {
            $table->string('browser', 100)->nullable()->after('user_agent');
            $table->string('browser_version', 50)->nullable()->after('browser');
            $table->string('device', 50)->nullable()->after('browser_version');
            $table->string('device_model', 100)->nullable()->after('device');
            $table->string('os', 50)->nullable()->after('device_model');
            $table->string('os_version', 50)->nullable()->after('os');
            $table->string('country', 100)->nullable()->after('os_version');
            $table->string('city', 100)->nullable()->after('country');

            $table->index('country');
            $table->index('device');
        });
    }

    public function down(): void
    {
        Schema::table('user_activity_logs', function (Blueprint $table) {
            $table->dropIndex(['country']);
            $table->dropIndex(['device']);
            $table->dropColumn([
                'browser', 'browser_version', 'device', 'device_model',
                'os', 'os_version', 'country', 'city',
            ]);
        });
    }
};
