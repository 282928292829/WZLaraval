<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('status_changed_at')->nullable()->after('status');
        });

        $this->backfillStatusChangedAt();
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('status_changed_at');
        });
    }

    private function backfillStatusChangedAt(): void
    {
        $orders = DB::table('orders')->select(['id', 'status', 'created_at'])->get();

        foreach ($orders as $order) {
            $statusChangedAt = DB::table('order_timeline')
                ->where('order_id', $order->id)
                ->where('type', 'status_change')
                ->where('status_to', $order->status)
                ->latest('created_at')
                ->value('created_at');

            DB::table('orders')
                ->where('id', $order->id)
                ->update([
                    'status_changed_at' => $statusChangedAt ?? $order->created_at,
                ]);
        }
    }
};
