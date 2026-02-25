<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        Schema::dropIfExists('order_files_new');
        DB::statement('CREATE TABLE order_files_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            order_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            comment_id INTEGER NULL,
            path VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            mime_type VARCHAR(100) NULL,
            size INTEGER UNSIGNED NULL,
            type VARCHAR(255) NOT NULL DEFAULT "other" CHECK (type IN ("receipt","product_image","invoice","other","comment")),
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (comment_id) REFERENCES order_comments(id) ON DELETE SET NULL
        )');
        DB::statement('INSERT INTO order_files_new SELECT id, order_id, user_id, comment_id, path, original_name, mime_type, size, type, created_at, updated_at FROM order_files');
        Schema::drop('order_files');
        Schema::rename('order_files_new', 'order_files');
        Schema::table('order_files', function (Blueprint $table) {
            $table->index('order_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        DB::statement("UPDATE order_files SET type = 'other' WHERE type = 'comment'");
        Schema::dropIfExists('order_files_new');
        DB::statement('CREATE TABLE order_files_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            order_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            comment_id INTEGER NULL,
            path VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            mime_type VARCHAR(100) NULL,
            size INTEGER UNSIGNED NULL,
            type VARCHAR(255) NOT NULL DEFAULT "other" CHECK (type IN ("receipt","product_image","invoice","other")),
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (comment_id) REFERENCES order_comments(id) ON DELETE SET NULL
        )');
        DB::statement('INSERT INTO order_files_new SELECT id, order_id, user_id, comment_id, path, original_name, mime_type, size, type, created_at, updated_at FROM order_files');
        Schema::drop('order_files');
        Schema::rename('order_files_new', 'order_files');
        Schema::table('order_files', function (Blueprint $table) {
            $table->index('order_id');
            $table->index('user_id');
        });
    }
};
