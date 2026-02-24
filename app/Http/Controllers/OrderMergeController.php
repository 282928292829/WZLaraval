<?php

namespace App\Http\Controllers;

use App\Http\Requests\Order\MergeOrdersRequest;
use App\Models\Order;

class OrderMergeController extends Controller
{
    public function __invoke(MergeOrdersRequest $request, int $id)
    {
        $this->authorize('merge-orders');

        $order = Order::findOrFail($id);
        $validated = $request->validated();

        $target = Order::with('items')->findOrFail($validated['merge_with']);

        $target->items()->update(['order_id' => $order->id]);

        $target->update([
            'merged_into' => $order->id,
            'merged_at' => now(),
            'merged_by' => auth()->id(),
        ]);

        $order->timeline()->create([
            'user_id' => auth()->id(),
            'type' => 'merge',
            'body' => __('orders.timeline_merged_from', ['number' => $target->order_number]),
        ]);
        $target->timeline()->create([
            'user_id' => auth()->id(),
            'type' => 'merge',
            'body' => __('orders.timeline_merged_into', ['number' => $order->order_number]),
        ]);

        return redirect()->route('orders.show', $order->id)->with('success', __('orders.merged'));
    }
}
