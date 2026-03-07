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
        Schema::create('image_cleanup_runs', function (Blueprint $table) {
            $table->id();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->boolean('dry_run')->default(false);
            $table->unsignedInteger('orders_processed')->default(0);
            $table->unsignedInteger('files_deleted')->default(0);
            $table->unsignedInteger('files_compressed')->default(0);
            $table->unsignedBigInteger('bytes_freed')->default(0);
            $table->json('details')->nullable();
            $table->string('triggered_by')->nullable(); // manual | scheduled
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('image_cleanup_runs');
    }
};
