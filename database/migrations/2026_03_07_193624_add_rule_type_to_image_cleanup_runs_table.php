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
        Schema::table('image_cleanup_runs', function (Blueprint $table) {
            $table->string('rule_type', 20)->nullable()->after('id'); // delete | compress
            $table->foreignId('image_cleanup_rule_id')->nullable()->after('rule_type')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('image_cleanup_runs', function (Blueprint $table) {
            $table->dropForeign(['image_cleanup_rule_id']);
            $table->dropColumn(['rule_type', 'image_cleanup_rule_id']);
        });
    }
};
