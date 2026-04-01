<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * MySQL utf8mb4 limits a single VARCHAR to ~16,383 chars, so VARCHAR(25,000) is invalid.
     * Keep TEXT and enforce max 25,000 with a CHECK (MySQL 8.0.16+).
     */
    private const BODY_MAX = 25000;

    public function up(): void
    {
        foreach (DB::table('order_comments')->orderBy('id')->cursor() as $row) {
            $body = (string) ($row->body ?? '');
            if (mb_strlen($body) <= self::BODY_MAX) {
                continue;
            }
            DB::table('order_comments')->where('id', $row->id)->update([
                'body' => mb_substr($body, 0, self::BODY_MAX),
            ]);
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE order_comments ADD CONSTRAINT order_comments_body_max_25000 CHECK (CHAR_LENGTH(body) <= '.self::BODY_MAX.')');
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE order_comments DROP CONSTRAINT order_comments_body_max_25000');
        }
    }
};
