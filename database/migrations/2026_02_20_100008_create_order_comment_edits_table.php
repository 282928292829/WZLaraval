<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_comment_edits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')->constrained('order_comments')->cascadeOnDelete();
            $table->text('old_body');
            $table->foreignId('edited_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('edited_at')->useCurrent();

            $table->index('comment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_comment_edits');
    }
};
