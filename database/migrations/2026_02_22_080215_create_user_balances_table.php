<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->enum('type', ['credit', 'debit']);
            $table->decimal('amount', 12, 2)->unsigned();
            $table->char('currency', 3)->default('SAR');
            $table->text('note');
            $table->date('date');
            $table->timestamps();

            $table->index(['user_id', 'currency']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_balances');
    }
};
