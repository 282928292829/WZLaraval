<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('ad_campaign_id')
                  ->nullable()
                  ->after('deletion_requested')
                  ->constrained('ad_campaigns')
                  ->nullOnDelete();

            $table->string('google_click_id')->nullable()->after('ad_campaign_id')
                  ->comment('Google Ads gclid parameter');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ad_campaign_id');
            $table->dropColumn('google_click_id');
        });
    }
};
