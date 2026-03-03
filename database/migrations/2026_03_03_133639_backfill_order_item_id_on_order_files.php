<?php

use App\Models\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Order::with(['items' => fn ($q) => $q->orderBy('sort_order'), 'files'])
            ->chunkById(100, function ($orders) {
                foreach ($orders as $order) {
                    $productImages = $order->files
                        ->whereNull('comment_id')
                        ->where('type', 'product_image')
                        ->whereNull('order_item_id')
                        ->values();
                    $items = $order->items->values();
                    $itemCount = $items->count();
                    if ($itemCount === 0) {
                        continue;
                    }
                    foreach ($productImages as $idx => $file) {
                        $item = $items->get($idx % $itemCount);
                        if ($item) {
                            DB::table('order_files')->where('id', $file->id)->update(['order_item_id' => $item->id]);
                        }
                    }
                }
            });
    }

    public function down(): void
    {
        DB::table('order_files')->whereNotNull('order_item_id')->update(['order_item_id' => null]);
    }
};
