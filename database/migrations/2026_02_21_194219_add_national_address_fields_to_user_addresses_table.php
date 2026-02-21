<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            $table->string('street', 255)->nullable()->after('city');
            $table->string('district', 255)->nullable()->after('street');
            $table->string('short_address', 20)->nullable()->after('district'); // الرمز الوطني المختصر
        });
    }

    public function down(): void
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            $table->dropColumn(['street', 'district', 'short_address']);
        });
    }
};
