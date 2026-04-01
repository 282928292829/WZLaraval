<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ORDER_NOTES_MAX = 5000;

    private const ITEM_URL_MAX = 4096;

    private const ITEM_NOTES_MAX = 2000;

    private const ITEM_COLOR_SIZE_MAX = 500;

    public function up(): void
    {
        $this->truncateOrdersNotes();
        $this->truncateOrderItemsFields();

        Schema::table('orders', function (Blueprint $table) {
            $table->string('notes', self::ORDER_NOTES_MAX)->nullable()->change();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('url', self::ITEM_URL_MAX)->nullable()->change();
            $table->string('color', self::ITEM_COLOR_SIZE_MAX)->nullable()->change();
            $table->string('size', self::ITEM_COLOR_SIZE_MAX)->nullable()->change();
            $table->string('notes', self::ITEM_NOTES_MAX)->nullable()->change();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('source_host', 255)->nullable()->after('is_url');
            $table->index('source_host');
        });

        $this->backfillSourceHost();
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['source_host']);
            $table->dropColumn('source_host');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->text('notes')->nullable()->change();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->text('url')->nullable()->change();
            $table->string('color', 100)->nullable()->change();
            $table->string('size', 100)->nullable()->change();
            $table->text('notes')->nullable()->change();
        });
    }

    private function truncateOrdersNotes(): void
    {
        foreach (DB::table('orders')->whereNotNull('notes')->orderBy('id')->cursor() as $row) {
            $notes = (string) $row->notes;
            if (mb_strlen($notes) <= self::ORDER_NOTES_MAX) {
                continue;
            }
            DB::table('orders')->where('id', $row->id)->update([
                'notes' => mb_substr($notes, 0, self::ORDER_NOTES_MAX),
            ]);
        }
    }

    private function truncateOrderItemsFields(): void
    {
        foreach (DB::table('order_items')->orderBy('id')->cursor() as $row) {
            $updates = [];

            $url = $row->url !== null ? (string) $row->url : '';
            if (mb_strlen($url) > self::ITEM_URL_MAX) {
                $updates['url'] = mb_substr($url, 0, self::ITEM_URL_MAX);
            }

            $notes = $row->notes !== null ? (string) $row->notes : '';
            if (mb_strlen($notes) > self::ITEM_NOTES_MAX) {
                $updates['notes'] = mb_substr($notes, 0, self::ITEM_NOTES_MAX);
            }

            $color = $row->color !== null ? (string) $row->color : '';
            if (mb_strlen($color) > self::ITEM_COLOR_SIZE_MAX) {
                $updates['color'] = mb_substr($color, 0, self::ITEM_COLOR_SIZE_MAX);
            }

            $size = $row->size !== null ? (string) $row->size : '';
            if (mb_strlen($size) > self::ITEM_COLOR_SIZE_MAX) {
                $updates['size'] = mb_substr($size, 0, self::ITEM_COLOR_SIZE_MAX);
            }

            if ($updates !== []) {
                DB::table('order_items')->where('id', $row->id)->update($updates);
            }
        }
    }

    private function backfillSourceHost(): void
    {
        foreach (DB::table('order_items')->orderBy('id')->cursor() as $row) {
            $host = order_item_source_host($row->url !== null ? (string) $row->url : null);
            $isUrl = (bool) $row->is_url;
            DB::table('order_items')->where('id', $row->id)->update([
                'source_host' => $isUrl ? $host : null,
            ]);
        }
    }
};
