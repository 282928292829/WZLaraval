<?php

namespace App\Http\Controllers;

use App\Http\Requests\Order\UpdateOrderStatusRequest;
use App\Models\AdCampaign;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderStatusController extends Controller
{
    public function update(UpdateOrderStatusRequest $request, int $id)
    {
        $this->authorize('update-order-status');

        $order = Order::findOrFail($id);
        $validated = $request->validated();

        $old = $order->status;
        $order->update(['status' => $validated['status']]);

        if (in_array($validated['status'], ['cancelled', 'shipped', 'delivered'])) {
            AdCampaign::incrementForOrderStatus($order, $validated['status']);
        }

        $order->timeline()->create([
            'user_id' => auth()->id(),
            'type' => 'status_change',
            'status_from' => $old,
            'status_to' => $validated['status'],
            'body' => null,
        ]);

        return redirect()->route('orders.show', $id)->with('success', __('orders.status_updated'));
    }

    public function markPaid(Request $request, int $id)
    {
        $this->authorize('update-order-status');

        $order = Order::findOrFail($id);

        if (! $order->is_paid) {
            $order->update(['is_paid' => true]);

            $order->timeline()->create([
                'user_id' => auth()->id(),
                'type' => 'payment',
                'status_from' => null,
                'status_to' => null,
                'body' => __('orders.marked_paid_by_staff'),
            ]);
        }

        return redirect()->route('orders.show', $id)->with('success', __('orders.marked_as_paid'));
    }
}
