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
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique()->comment('ISO 4217 currency code (e.g. USD, EUR)');
            $table->string('label', 100)->nullable()->comment('Optional display label');
            $table->decimal('manual_rate', 18, 4)->nullable()->comment('Manual SAR rate override; null = use auto from API');
            $table->boolean('auto_fetch')->default(true)->comment('Include in daily rate fetch');
            $table->decimal('markup_percent', 5, 2)->nullable()->comment('Per-currency markup; null = use global');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
