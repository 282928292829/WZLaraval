<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->comment('Null for guest comments');
            $table->foreignId('parent_id')->nullable()->constrained('post_comments')->nullOnDelete()->comment('One level of nesting only');
            $table->string('guest_name', 100)->nullable();
            $table->string('guest_email', 191)->nullable();
            $table->text('body');
            $table->enum('status', ['pending', 'approved', 'spam'])->default('pending');
            $table->boolean('is_edited')->default(false);
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('post_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_comments');
    }
};
