<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->boolean('allow_comments')->default(true)->after('published_at');
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->boolean('allow_comments')->default(false)->after('menu_order');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('allow_comments');
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('allow_comments');
        });
    }
};
