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
        Schema::create('image_cleanup_rules', function (Blueprint $table) {
            $table->id();
            $table->string('rule_type', 20); // delete | compress
            $table->json('statuses'); // order statuses to match
            $table->unsignedSmallInteger('retention_days_customer_product')->default(14);
            $table->unsignedSmallInteger('retention_days_staff_product')->default(90);
            $table->unsignedSmallInteger('retention_days_customer_comment')->default(14);
            $table->unsignedSmallInteger('retention_days_staff_comment')->default(90);
            $table->boolean('customer_product')->default(true);
            $table->boolean('staff_product')->default(false);
            $table->boolean('customer_comment')->default(true);
            $table->boolean('staff_comment')->default(false);
            $table->boolean('receipt')->default(false);
            $table->boolean('invoice')->default(false);
            $table->boolean('other')->default(false);
            $table->unsignedTinyInteger('compression_quality')->default(55); // compress only
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['rule_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('image_cleanup_rules');
    }
};
