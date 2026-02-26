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

        $type = $validated['type'] ?? 'other';
        if ($type === 'attachment') {
            $type = 'other';
        }

        $count = 0;
        foreach ($request->file('files', []) as $file) {
            $path = $file->store('order-files/'.$order->id, 'public');
            $order->files()->create([
                'user_id' => auth()->id(),
                'comment_id' => null,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'type' => $type,
            ]);
            $count++;
        }

        $message = $count === 1
            ? __('orders.file_uploaded')
            : __('orders.files_uploaded', ['count' => $count]);

        return redirect()->route('orders.show', $id)->withFragment('files')->with('success', $message);
    }
}
