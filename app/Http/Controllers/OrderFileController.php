<?php

namespace App\Http\Controllers;

use App\Http\Requests\Order\StoreOrderFileRequest;
use App\Models\Order;

class OrderFileController extends Controller
{
    public function store(StoreOrderFileRequest $request, int $id)
    {
        $this->authorize('reply-to-comments');

        $order = Order::findOrFail($id);
        $validated = $request->validated();

        $file = $request->file('file');
        $path = $file->store('order-files/'.$order->id, 'public');
        $type = $validated['type'] ?? 'other';
        if ($type === 'attachment') {
            $type = 'other';
        }

        $order->files()->create([
            'user_id' => auth()->id(),
            'comment_id' => null,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'type' => $type,
        ]);

        return redirect()->route('orders.show', $id)->withFragment('files')->with('success', __('orders.file_uploaded'));
    }
}
