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
        Schema::create('shipping_companies', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar')->nullable();
            $table->string('name_en')->nullable();
            $table->string('slug', 50)->unique();
            $table->unsignedSmallInteger('first_half_kg')->nullable()->comment('SAR for first 0.5 kg; null = not in calculator');
            $table->unsignedSmallInteger('rest_half_kg')->nullable()->comment('SAR per additional 0.5 kg');
            $table->unsignedSmallInteger('over21_per_kg')->nullable()->comment('SAR per kg over 21 kg; null = no over-21 tier');
            $table->string('delivery_days', 20)->nullable()->comment('e.g. 7-10 days');
            $table->string('tracking_url_template', 500)->nullable()->comment('Use {tracking} as placeholder');
            $table->string('icon', 10)->nullable()->comment('Emoji or icon identifier');
            $table->string('note_ar', 100)->nullable()->comment('e.g. Economy, Express');
            $table->string('note_en', 100)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_companies');
    }
};
