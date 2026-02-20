<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 20)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('status', [
                'pending',
                'needs_payment',
                'processing',
                'purchasing',
                'shipped',
                'delivered',
                'completed',
                'cancelled',
                'on_hold',
            ])->default('pending');
            $table->tinyInteger('layout_option')->default(2)->comment('1=Cart, 2=Responsive, 3=Cards');
            $table->text('notes')->nullable();
            $table->boolean('is_paid')->default(false);
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_proof')->nullable();
            $table->decimal('subtotal', 10, 2)->nullable()->comment('Sum of customer-entered prices');
            $table->decimal('total_amount', 10, 2)->nullable()->comment('Staff-set final total');
            $table->string('currency', 10)->default('USD');
            $table->timestamp('can_edit_until')->nullable()->comment('Order modification deadline pre-payment');
            $table->foreignId('merged_into')->nullable()->constrained('orders')->nullOnDelete();
            $table->timestamp('merged_at')->nullable();
            $table->foreignId('merged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('status');
            $table->index('order_number');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
