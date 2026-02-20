<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->text('url')->nullable()->comment('Product URL or plain text description');
            $table->boolean('is_url')->default(true)->comment('False when field contains plain text, not a URL');
            $table->unsignedSmallInteger('qty')->default(1);
            $table->string('color', 100)->nullable();
            $table->string('size', 100)->nullable();
            $table->text('notes')->nullable();
            $table->string('image_path')->nullable()->comment('1 image per product, uploaded by customer');
            $table->string('currency', 10)->nullable();
            $table->decimal('unit_price', 10, 2)->nullable()->comment('Customer-entered price per unit');
            $table->decimal('final_price', 10, 2)->nullable()->comment('Staff-confirmed final price per unit');
            $table->decimal('commission', 10, 2)->nullable();
            $table->decimal('shipping', 10, 2)->nullable();
            $table->decimal('extras', 10, 2)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
