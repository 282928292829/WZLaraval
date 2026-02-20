<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('type', 60)->comment('new_order, comment, payment, contact_form, status_change');
            $table->nullableMorphs('subject');
            $table->foreignId('causer_id')->nullable()->constrained('users')->nullOnDelete()->comment('User who triggered the event');
            $table->json('data')->nullable()->comment('Extra event payload');
            $table->timestamp('read_at')->nullable()->comment('When any staff member first viewed this item');
            $table->timestamp('created_at')->useCurrent();

            $table->index('type');
            $table->index('read_at');
            $table->index('created_at');
            $table->index('causer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
