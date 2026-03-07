<?php

namespace App\Http\Controllers;

use App\Http\Requests\Order\MergeOrdersRequest;
use App\Models\Order;

class OrderMergeController extends Controller
{
    public function __invoke(MergeOrdersRequest $request, Order $order)
    {
        $this->authorize('merge-orders');
        $validated = $request->validated();

        $source = Order::with('items')->findOrFail($validated['merge_with']);

        // Merge allowed only when user can view both source and target (same checks as orders.show).
        $this->authorize('view', $order);
        $this->authorize('view', $source);

        $source->items()->update(['order_id' => $order->id]);

        $source->update([
            'merged_into' => $order->id,
            'merged_at' => now(),
            'merged_by' => auth()->id(),
        ]);

        $order->timeline()->create([
            'user_id' => auth()->id(),
            'type' => 'merge',
            'body' => __('orders.timeline_merged_from', ['number' => $source->order_number]),
        ]);
        $source->timeline()->create([
            'user_id' => auth()->id(),
            'type' => 'merge',
            'body' => __('orders.timeline_merged_into', ['number' => $order->order_number]),
        ]);

        return redirect()->route('orders.show', $order)->with('success', __('orders.merged'));
    }
}
