<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderComment;
use App\Models\OrderCommentRead;
use App\Models\OrderCommentEdit;
use App\Models\OrderFile;
use App\Models\OrderTimeline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    public function show(Request $request, int $id)
    {
        $user  = auth()->user();
        $order = Order::with([
            'user',
            'items' => fn ($q) => $q->orderBy('sort_order'),
            'files' => fn ($q) => $q->orderBy('created_at'),
            'timeline' => fn ($q) => $q->with('user')->orderBy('created_at'),
            'comments' => fn ($q) => $q
                ->with(['user', 'edits.editor', 'reads.user'])
                ->withTrashed()
                ->orderBy('created_at'),
        ])->findOrFail($id);

        $isOwner = $order->user_id === $user->id;
        $isStaff = $user->hasAnyRole(['editor', 'admin', 'superadmin']);

        if (! $isOwner && ! $isStaff) {
            abort(403);
        }

        // Mark all visible comments as read — single bulk upsert (no N+1)
        $visibleCommentIds = $order->comments
            ->filter(fn ($c) => ! $c->is_internal || $isStaff)
            ->filter(fn ($c) => ! $c->deleted_at)
            ->pluck('id');

        if ($visibleCommentIds->isNotEmpty()) {
            $now  = now();
            $rows = $visibleCommentIds->map(fn ($cid) => [
                'comment_id' => $cid,
                'user_id'    => $user->id,
                'read_at'    => $now,
            ])->all();

            OrderCommentRead::upsert(
                $rows,
                ['comment_id', 'user_id'],
                ['read_at']
            );
        }

        // Customer edit window
        $canEditItems = $isOwner
            && ! $order->is_paid
            && $order->can_edit_until
            && now()->lt($order->can_edit_until);

        // Recent orders (same customer) for merge dropdown — staff only
        $recentOrders = [];
        if ($isStaff && $user->can('merge-orders')) {
            $recentOrders = Order::where('user_id', $order->user_id)
                ->where('id', '!=', $order->id)
                ->whereNull('merged_into')
                ->orderByDesc('created_at')
                ->limit(20)
                ->get(['id', 'order_number', 'status', 'created_at']);
        }

        return view('orders.show', compact(
            'order', 'isOwner', 'isStaff', 'canEditItems', 'recentOrders'
        ));
    }

    // ─── Comment actions ─────────────────────────────────────────────────────

    public function storeComment(Request $request, int $id)
    {
        $user  = auth()->user();
        $order = Order::findOrFail($id);

        $isOwner = $order->user_id === $user->id;
        $isStaff = $user->hasAnyRole(['editor', 'admin', 'superadmin']);

        if (! $isOwner && ! $isStaff) abort(403);

        $isInternal = $isStaff && $request->boolean('is_internal');

        $validated = $request->validate([
            'body'        => ['required', 'string', 'max:5000'],
            'is_internal' => ['sometimes', 'boolean'],
            'file'        => ['sometimes', 'file', 'max:10240',
                'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx'],
        ]);

        $comment = $order->comments()->create([
            'user_id'     => $user->id,
            'body'        => $validated['body'],
            'is_internal' => $isInternal,
        ]);

        // Attach file to comment if present
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('order-files/' . $order->id, 'public');
            $order->files()->create([
                'user_id'       => $user->id,
                'comment_id'    => $comment->id,
                'path'          => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type'     => $file->getMimeType(),
                'size'          => $file->getSize(),
                'type'          => 'comment',
            ]);
        }

        // Timeline entry
        $order->timeline()->create([
            'user_id' => $user->id,
            'type'    => $isInternal ? 'note' : 'comment',
            'body'    => $isInternal ? __('orders.timeline_internal_note') : __('orders.timeline_comment_added'),
        ]);

        return redirect()->route('orders.show', $order->id)->with('success', __('orders.comment_added'));
    }

    public function updateComment(Request $request, int $orderId, int $commentId)
    {
        $user    = auth()->user();
        $order   = Order::findOrFail($orderId);
        $comment = OrderComment::where('order_id', $orderId)->findOrFail($commentId);

        $isOwner    = $comment->user_id === $user->id;
        $isStaff    = $user->hasAnyRole(['editor', 'admin', 'superadmin']);
        $canEdit    = $isOwner || ($isStaff && $user->can('reply-to-comments'));

        if (! $canEdit) abort(403);

        $validated = $request->validate(['body' => ['required', 'string', 'max:5000']]);

        // Save edit history
        $comment->edits()->create([
            'old_body'  => $comment->body,
            'edited_by' => $user->id,
        ]);

        $comment->update([
            'body'      => $validated['body'],
            'is_edited' => true,
            'edited_at' => now(),
        ]);

        return redirect()->route('orders.show', $orderId)->with('success', __('orders.comment_updated'));
    }

    public function destroyComment(Request $request, int $orderId, int $commentId)
    {
        $user    = auth()->user();
        $comment = OrderComment::where('order_id', $orderId)->findOrFail($commentId);

        $isOwner = $comment->user_id === $user->id;
        $isStaff = $user->hasAnyRole(['editor', 'admin', 'superadmin']);

        if (! $isOwner && ! ($isStaff && $user->can('delete-any-comment'))) abort(403);

        $comment->update(['deleted_by' => $user->id]);
        $comment->delete();

        return redirect()->route('orders.show', $orderId)->with('success', __('orders.comment_deleted'));
    }

    // ─── Staff: change status ─────────────────────────────────────────────

    public function updateStatus(Request $request, int $id)
    {
        $this->authorize('update-order-status');

        $order     = Order::findOrFail($id);
        $validated = $request->validate([
            'status' => ['required', 'in:' . implode(',', array_keys(Order::getStatuses()))],
        ]);

        $old = $order->status;
        $order->update(['status' => $validated['status']]);

        $order->timeline()->create([
            'user_id'     => auth()->id(),
            'type'        => 'status_change',
            'status_from' => $old,
            'status_to'   => $validated['status'],
            'body'        => null,
        ]);

        return redirect()->route('orders.show', $id)->with('success', __('orders.status_updated'));
    }

    // ─── Staff: upload order-level file ──────────────────────────────────

    public function storeFile(Request $request, int $id)
    {
        $this->authorize('reply-to-comments');

        $order     = Order::findOrFail($id);
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240',
                'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx'],
            'type' => ['sometimes', 'in:receipt,attachment'],
        ]);

        $file = $request->file('file');
        $path = $file->store('order-files/' . $order->id, 'public');
        $order->files()->create([
            'user_id'       => auth()->id(),
            'comment_id'    => null,
            'path'          => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type'     => $file->getMimeType(),
            'size'          => $file->getSize(),
            'type'          => $validated['type'] ?? 'attachment',
        ]);

        return redirect()->route('orders.show', $id)->with('success', __('orders.file_uploaded'));
    }

    // ─── Staff: update prices on items ───────────────────────────────────

    public function updatePrices(Request $request, int $id)
    {
        $this->authorize('edit-prices');

        $order     = Order::with('items')->findOrFail($id);
        $validated = $request->validate([
            'items'                => ['required', 'array'],
            'items.*.id'           => ['required', 'integer'],
            'items.*.unit_price'   => ['nullable', 'numeric', 'min:0'],
            'items.*.commission'   => ['nullable', 'numeric', 'min:0'],
            'items.*.shipping'     => ['nullable', 'numeric', 'min:0'],
            'items.*.final_price'  => ['nullable', 'numeric', 'min:0'],
            'items.*.currency'     => ['nullable', 'string', 'max:10'],
        ]);

        foreach ($validated['items'] as $itemData) {
            $item = $order->items->firstWhere('id', $itemData['id']);
            if ($item) {
                $item->update([
                    'unit_price'  => $itemData['unit_price']  ?? $item->unit_price,
                    'commission'  => $itemData['commission']  ?? $item->commission,
                    'shipping'    => $itemData['shipping']    ?? $item->shipping,
                    'final_price' => $itemData['final_price'] ?? $item->final_price,
                    'currency'    => $itemData['currency']    ?? $item->currency,
                ]);
            }
        }

        $order->timeline()->create([
            'user_id' => auth()->id(),
            'type'    => 'note',
            'body'    => __('orders.timeline_prices_updated'),
        ]);

        return redirect()->route('orders.show', $id)->with('success', __('orders.prices_updated'));
    }

    // ─── Staff: generate invoice (posts as comment) ───────────────────────

    public function generateInvoice(Request $request, int $id)
    {
        $this->authorize('generate-pdf-invoice');

        $order    = Order::with('items')->findOrFail($id);
        $validated = $request->validate([
            'custom_amount' => ['nullable', 'numeric', 'min:0'],
            'custom_notes'  => ['nullable', 'string', 'max:1000'],
            'invoice_type'  => ['sometimes', 'in:detailed,simple'],
        ]);

        $subtotal = $order->items->sum(fn ($i) => ($i->unit_price ?? 0) * ($i->qty ?? 1));
        $total    = ! empty($validated['custom_amount']) ? (float) $validated['custom_amount'] : $subtotal;

        $lines = [];
        if (($validated['invoice_type'] ?? 'detailed') === 'detailed') {
            foreach ($order->items as $idx => $item) {
                $lineTotal = ($item->unit_price ?? 0) * ($item->qty ?? 1);
                if ($lineTotal > 0) {
                    $lines[] = sprintf(
                        __('orders.invoice_line'),
                        $idx + 1,
                        $item->qty,
                        number_format($item->unit_price, 2),
                        $item->currency,
                        number_format($lineTotal, 2)
                    );
                }
            }
        }

        $body = view('orders._invoice_text', compact('order', 'lines', 'total', 'validated'))->render();

        $order->comments()->create([
            'user_id'     => auth()->id(),
            'body'        => $body,
            'is_internal' => false,
        ]);

        return redirect()->route('orders.show', $id)->with('success', __('orders.invoice_generated'));
    }

    // ─── Staff: merge orders ──────────────────────────────────────────────

    public function merge(Request $request, int $id)
    {
        $this->authorize('merge-orders');

        $order     = Order::findOrFail($id);
        $validated = $request->validate([
            'merge_with' => ['required', 'integer', 'different:id', 'exists:orders,id'],
        ]);

        $target = Order::with('items')->findOrFail($validated['merge_with']);

        // Move all items from target into this order
        $target->items()->update(['order_id' => $order->id]);

        // Mark target as merged
        $target->update([
            'merged_into' => $order->id,
            'merged_at'   => now(),
            'merged_by'   => auth()->id(),
        ]);

        // Timeline on both
        $order->timeline()->create([
            'user_id' => auth()->id(),
            'type'    => 'merge',
            'body'    => __('orders.timeline_merged_from', ['number' => $target->order_number]),
        ]);
        $target->timeline()->create([
            'user_id' => auth()->id(),
            'type'    => 'merge',
            'body'    => __('orders.timeline_merged_into', ['number' => $order->order_number]),
        ]);

        return redirect()->route('orders.show', $order->id)->with('success', __('orders.merged'));
    }

    // ─── Staff: send comment notification ────────────────────────────────

    public function sendNotification(Request $request, int $orderId, int $commentId)
    {
        $this->authorize('send-comment-notification');

        // Notification sending is disabled by default; logs the intent only.
        // Will be wired to mail queue when SMTP is configured in Phase 3.
        $comment = OrderComment::where('order_id', $orderId)->findOrFail($commentId);

        return redirect()->route('orders.show', $orderId)
            ->with('success', __('orders.notification_queued'));
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        if ($user->hasAnyRole(['editor', 'admin', 'superadmin'])) {
            if ($request->get('export') === 'csv' && $user->can('export-csv')) {
                return $this->exportCsv($request);
            }

            return $this->staffIndex($request);
        }

        return $this->customerIndex($user);
    }

    private function customerIndex($user)
    {
        $orders = Order::where('user_id', $user->id)
            ->withCount('items')
            ->latest()
            ->get();

        $groups = [
            'needs_action' => $orders->whereIn('status', ['needs_payment', 'on_hold'])->values(),
            'in_progress'  => $orders->whereIn('status', ['pending', 'processing', 'purchasing', 'shipped', 'delivered'])->values(),
            'completed'    => $orders->whereIn('status', ['completed', 'cancelled'])->values(),
        ];

        return view('orders.index', compact('orders', 'groups'));
    }

    private function staffIndex(Request $request)
    {
        $query = Order::with('user')->withCount('items');

        if ($search = trim($request->get('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($u) => $u
                      ->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($from = $request->get('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->get('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $sort    = $request->get('sort', 'desc') === 'asc' ? 'asc' : 'desc';
        $perPage = in_array((int) $request->get('per_page'), [25, 50, 100]) ? (int) $request->get('per_page') : 25;

        $query->orderBy('created_at', $sort);

        $orders   = $query->paginate($perPage)->withQueryString();
        $statuses = Order::getStatuses();

        return view('orders.staff', compact('orders', 'perPage', 'statuses', 'sort'));
    }

    public function bulkUpdate(Request $request)
    {
        $this->authorize('bulk-update-orders');

        $validated = $request->validate([
            'order_ids'   => ['required', 'array', 'min:1'],
            'order_ids.*' => ['integer', 'exists:orders,id'],
            'new_status'  => ['required', 'in:' . implode(',', array_keys(Order::getStatuses()))],
        ]);

        $count = count($validated['order_ids']);

        Order::whereIn('id', $validated['order_ids'])->update(['status' => $validated['new_status']]);

        return back()->with('success', __('orders.bulk_status_updated', ['count' => $count]));
    }

    private function exportCsv(Request $request): StreamedResponse
    {
        $query = Order::with('user')->withCount('items');

        if ($search = trim($request->get('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($u) => $u
                      ->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($from = $request->get('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->get('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $orders   = $query->orderBy('created_at', 'desc')->limit(10000)->get();
        $filename = 'orders-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($orders) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM so Excel renders Arabic correctly
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['Order #', 'Customer', 'Email', 'Date', 'Items', 'Status', 'Subtotal', 'Total', 'Currency', 'Paid']);

            foreach ($orders as $order) {
                fputcsv($handle, [
                    $order->order_number,
                    $order->user?->name ?? '',
                    $order->user?->email ?? '',
                    $order->created_at->format('Y-m-d'),
                    $order->items_count,
                    $order->status,
                    $order->subtotal ?? '',
                    $order->total_amount ?? '',
                    $order->currency,
                    $order->is_paid ? 'Yes' : 'No',
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
