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
        Schema::table('pages', function (Blueprint $table) {
            $table->string('og_image')->nullable()->after('seo_description_en');
            $table->string('canonical_url')->nullable()->after('og_image');
            $table->string('robots')->nullable()->after('canonical_url');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['og_image', 'canonical_url', 'robots']);
        });
    }
};
