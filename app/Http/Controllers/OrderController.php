<?php

namespace App\Http\Controllers;

use App\Mail\OrderConfirmation;
use App\Models\CommentTemplate;
use App\Models\EmailLog;
use App\Models\Order;
use App\Models\OrderComment;
use App\Models\OrderCommentRead;
use App\Models\OrderTimeline;
use App\Models\Setting;
use App\Models\UserActivityLog;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    public function show(Request $request, int $id)
    {
        $user = auth()->user();
        $order = Order::with([
            'user',
            'items' => fn ($q) => $q->orderBy('sort_order'),
            'files' => fn ($q) => $q->orderBy('created_at'),
            'timeline' => fn ($q) => $q->with('user')->orderBy('created_at'),
            'comments' => fn ($q) => $q
                ->with(['user', 'edits.editor', 'reads.user', 'notificationLogs.user'])
                ->withTrashed()
                ->orderBy('created_at'),
        ])->findOrFail($id);

        $isOwner = $order->user_id === $user->id;
        $isStaff = $user->hasAnyRole(['editor', 'admin', 'superadmin']);

        if (! $isOwner && ! $isStaff) {
            abort(403);
        }

        // Read state is recorded by viewport-based tracking (JS), not on page load (matches WordPress).

        if ($isOwner && ! $order->is_paid && $order->can_edit_until === null) {
            \App\Livewire\NewOrder::initEditWindow($order);
            $order->refresh();
        }

        $canEditItems = $isOwner
            && ! $order->is_paid
            && $order->can_edit_until
            && now()->lt($order->can_edit_until);

        // Recent orders (same customer) for merge dropdown — staff only
        $recentOrders = collect();
        if ($isStaff && $user->can('merge-orders')) {
            $recentOrders = Order::where('user_id', $order->user_id)
                ->where('id', '!=', $order->id)
                ->whereNull('merged_into')
                ->orderByDesc('created_at')
                ->limit(20)
                ->get(['id', 'order_number', 'status', 'created_at']);
        }

        // Customer's own recent orders for customer merge request modal
        $customerRecentOrders = collect();
        if ($isOwner) {
            $customerRecentOrders = Order::where('user_id', $user->id)
                ->where('id', '!=', $order->id)
                ->whereNull('merged_into')
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->orderByDesc('created_at')
                ->limit(20)
                ->get(['id', 'order_number', 'status', 'created_at']);
        }

        // Device/IP log from order creation — staff-only panel
        $orderCreationLog = null;
        if ($isStaff) {
            $orderCreationLog = UserActivityLog::where('event', 'order_created')
                ->where('subject_type', Order::class)
                ->where('subject_id', $order->id)
                ->first();
        }

        // Comments discovery banner: show only on first 2 visits to this order (per user, cookie)
        $cookieName = 'order_discovery_visits_'.$order->id;
        $visits = (int) $request->cookie($cookieName, 0);
        $showCommentsDiscovery = $visits < 2;
        if ($showCommentsDiscovery) {
            cookie()->queue($cookieName, (string) ($visits + 1), 60 * 24 * 365); // 1 year
        }

        return view('orders.show', compact(
            'order', 'isOwner', 'isStaff', 'canEditItems', 'recentOrders', 'customerRecentOrders', 'orderCreationLog', 'showCommentsDiscovery'
        ));
    }

    // ─── Comment actions ─────────────────────────────────────────────────────

    public function storeComment(Request $request, int $id)
    {
        $user = auth()->user();
        $order = Order::findOrFail($id);

        $isOwner = $order->user_id === $user->id;
        $isStaff = $user->hasAnyRole(['editor', 'admin', 'superadmin']);

        if (! $isOwner && ! $isStaff) {
            abort(403);
        }

        $isInternal = $isStaff && $request->boolean('is_internal');

        $maxFiles = (int) Setting::get('comment_max_files', 5);
        $maxFileMb = (int) Setting::get('comment_max_file_size_mb', 10);
        $maxFileKb = $maxFileMb * 1024;

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'is_internal' => ['sometimes', 'boolean'],
            'template_id' => ['sometimes', 'nullable', 'integer', 'exists:comment_templates,id'],
            'files' => ['sometimes', 'array', 'max:'.$maxFiles],
            'files.*' => ['file', 'max:'.$maxFileKb,
                'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx'],
        ]);

        // Track template usage when staff uses a quick-reply template
        if ($isStaff && ! empty($validated['template_id'])) {
            CommentTemplate::where('id', $validated['template_id'])->increment('usage_count');
        }

        $comment = $order->comments()->create([
            'user_id' => $user->id,
            'body' => $validated['body'],
            'is_internal' => $isInternal,
        ]);

        // Attach files to comment if present
        foreach ($request->file('files', []) as $file) {
            $path = $file->store('order-files/'.$order->id, 'public');
            $order->files()->create([
                'user_id' => $user->id,
                'comment_id' => $comment->id,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'type' => 'comment',
            ]);
        }

        // Timeline entry
        $order->timeline()->create([
            'user_id' => $user->id,
            'type' => $isInternal ? 'note' : 'comment',
            'body' => $isInternal ? __('orders.timeline_internal_note') : __('orders.timeline_comment_added'),
        ]);

        return redirect()->route('orders.show', $order->id)->with('success', __('orders.comment_added'));
    }

    public function updateComment(Request $request, int $orderId, int $commentId)
    {
        $user = auth()->user();
        $order = Order::findOrFail($orderId);
        $comment = OrderComment::where('order_id', $orderId)->findOrFail($commentId);

        $isOwner = $comment->user_id === $user->id;
        $isStaff = $user->hasAnyRole(['editor', 'admin', 'superadmin']);
        $canEdit = $isOwner || ($isStaff && $user->can('reply-to-comments'));

        if (! $canEdit) {
            abort(403);
        }

        $validated = $request->validate(['body' => ['required', 'string', 'max:5000']]);

        // Save edit history
        $comment->edits()->create([
            'old_body' => $comment->body,
            'edited_by' => $user->id,
            'edited_at' => now(),
        ]);

        $comment->update([
            'body' => $validated['body'],
            'is_edited' => true,
            'edited_at' => now(),
        ]);

        return redirect()->route('orders.show', $orderId)->with('success', __('orders.comment_updated'));
    }

    public function destroyComment(Request $request, int $orderId, int $commentId)
    {
        $user = auth()->user();
        $comment = OrderComment::where('order_id', $orderId)->findOrFail($commentId);

        $isOwner = $comment->user_id === $user->id;
        $isStaff = $user->hasAnyRole(['editor', 'admin', 'superadmin']);

        if (! $isOwner && ! ($isStaff && $user->can('delete-any-comment'))) {
            abort(403);
        }

        $comment->update(['deleted_by' => $user->id]);
        $comment->delete();

        return redirect()->route('orders.show', $orderId)->with('success', __('orders.comment_deleted'));
    }

    // ─── Staff: change status ─────────────────────────────────────────────

    public function updateStatus(Request $request, int $id)
    {
        $this->authorize('update-order-status');

        $order = Order::findOrFail($id);
        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', array_keys(Order::getStatuses()))],
        ]);

        $old = $order->status;
        $order->update(['status' => $validated['status']]);

        $order->timeline()->create([
            'user_id' => auth()->id(),
            'type' => 'status_change',
            'status_from' => $old,
            'status_to' => $validated['status'],
            'body' => null,
        ]);

        return redirect()->route('orders.show', $id)->with('success', __('orders.status_updated'));
    }

    // ─── Staff: quick-action mark as paid ────────────────────────────────

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

    // ─── Staff: upload order-level file ──────────────────────────────────

    public function storeFile(Request $request, int $id)
    {
        $this->authorize('reply-to-comments');

        $order = Order::findOrFail($id);
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240',
                'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx'],
            'type' => ['sometimes', 'in:receipt,attachment'],
        ]);

        $file = $request->file('file');
        $path = $file->store('order-files/'.$order->id, 'public');
        $order->files()->create([
            'user_id' => auth()->id(),
            'comment_id' => null,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'type' => $validated['type'] ?? 'attachment',
        ]);

        return redirect()->route('orders.show', $id)->with('success', __('orders.file_uploaded'));
    }

    // ─── Staff: update prices on items ───────────────────────────────────

    public function updatePrices(Request $request, int $id)
    {
        $this->authorize('edit-prices');

        $order = Order::with('items')->findOrFail($id);
        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.commission' => ['nullable', 'numeric', 'min:0'],
            'items.*.shipping' => ['nullable', 'numeric', 'min:0'],
            'items.*.final_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.currency' => ['nullable', 'string', 'max:10'],
        ]);

        foreach ($validated['items'] as $itemData) {
            $item = $order->items->firstWhere('id', $itemData['id']);
            if ($item) {
                $item->update([
                    'unit_price' => $itemData['unit_price'] ?? $item->unit_price,
                    'commission' => $itemData['commission'] ?? $item->commission,
                    'shipping' => $itemData['shipping'] ?? $item->shipping,
                    'final_price' => $itemData['final_price'] ?? $item->final_price,
                    'currency' => $itemData['currency'] ?? $item->currency,
                ]);
            }
        }

        $order->timeline()->create([
            'user_id' => auth()->id(),
            'type' => 'note',
            'body' => __('orders.timeline_prices_updated'),
        ]);

        return redirect()->route('orders.show', $id)->with('success', __('orders.prices_updated'));
    }

    // ─── Staff: generate invoice (posts as comment) ───────────────────────

    public function generateInvoice(Request $request, int $id)
    {
        $this->authorize('generate-pdf-invoice');

        $order = Order::with('items')->findOrFail($id);
        $validated = $request->validate([
            'custom_amount' => ['nullable', 'numeric', 'min:0'],
            'custom_notes' => ['nullable', 'string', 'max:1000'],
            'invoice_type' => ['sometimes', 'in:detailed,simple'],
        ]);

        $subtotal = $order->items->sum(fn ($i) => ($i->unit_price ?? 0) * ($i->qty ?? 1));
        $total = ! empty($validated['custom_amount']) ? (float) $validated['custom_amount'] : $subtotal;

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
            'user_id' => auth()->id(),
            'body' => $body,
            'is_internal' => false,
        ]);

        return redirect()->route('orders.show', $id)->with('success', __('orders.invoice_generated'));
    }

    // ─── Staff: merge orders ──────────────────────────────────────────────

    public function merge(Request $request, int $id)
    {
        $this->authorize('merge-orders');

        $order = Order::findOrFail($id);
        $validated = $request->validate([
            'merge_with' => ['required', 'integer', 'different:id', 'exists:orders,id'],
        ]);

        $target = Order::with('items')->findOrFail($validated['merge_with']);

        // Move all items from target into this order
        $target->items()->update(['order_id' => $order->id]);

        // Mark target as merged
        $target->update([
            'merged_into' => $order->id,
            'merged_at' => now(),
            'merged_by' => auth()->id(),
        ]);

        // Timeline on both
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

    // ─── Update shipping address on order ────────────────────────────────

    public function updateShippingAddress(Request $request, int $id)
    {
        $user = auth()->user();
        $order = Order::findOrFail($id);

        $isOwner = $order->user_id === $user->id;
        $isStaff = $user->hasAnyRole(['editor', 'admin', 'superadmin']);

        if (! $isOwner && ! $isStaff) {
            abort(403);
        }

        // Only allow change while order is in an editable state
        $editableStatuses = ['pending', 'needs_payment', 'on_hold'];
        if (! in_array($order->status, $editableStatuses)) {
            return redirect()->route('orders.show', $id)
                ->with('error', __('orders.address_change_not_allowed'));
        }

        $validated = $request->validate([
            'shipping_address_id' => ['required', 'integer'],
        ]);

        // Address must belong to the order's owner
        $address = UserAddress::where('user_id', $order->user_id)
            ->findOrFail($validated['shipping_address_id']);

        $snapshot = $address->only([
            'id', 'label', 'recipient_name', 'phone',
            'country', 'city', 'address',
        ]);

        $order->update([
            'shipping_address_id' => $address->id,
            'shipping_address_snapshot' => $snapshot,
        ]);

        $order->timeline()->create([
            'user_id' => $user->id,
            'type' => 'note',
            'body' => __('orders.timeline_address_changed', [
                'address' => $address->label ?: $address->city,
            ]),
        ]);

        return redirect()->route('orders.show', $id)
            ->with('success', __('orders.address_updated'));
    }

    // ─── Staff: send comment notification ────────────────────────────────

    public function sendNotification(Request $request, int $orderId, int $commentId)
    {
        $this->authorize('send-comment-notification');

        $comment = OrderComment::where('order_id', $orderId)->with('order.user')->findOrFail($commentId);
        $order = $comment->order;

        if (! Setting::get('email_enabled', false)) {
            return redirect()->route('orders.show', $orderId)
                ->with('error', __('orders.notification_email_disabled'));
        }

        if (! $order->user || ! $order->user->email) {
            return redirect()->route('orders.show', $orderId)
                ->with('error', __('orders.notification_no_recipient'));
        }

        try {
            Mail::to($order->user->email, $order->user->name)
                ->queue(new \App\Mail\CommentNotification($comment));

            $order->timeline()->create([
                'user_id' => auth()->id(),
                'type' => 'note',
                'body' => __('orders.timeline_notification_sent', ['email' => $order->user->email]),
            ]);

            return redirect()->route('orders.show', $orderId)
                ->with('success', __('orders.notification_queued'));
        } catch (\Throwable $e) {
            return redirect()->route('orders.show', $orderId)
                ->with('error', __('orders.notification_failed').': '.$e->getMessage());
        }
    }

    /** Staff: log that comment was sent via WhatsApp (opens wa.me in front; this records the action). */
    public function logWhatsAppSend(Request $request, int $orderId, int $commentId): \Illuminate\Http\JsonResponse
    {
        $this->authorize('send-comment-notification');

        $comment = OrderComment::where('order_id', $orderId)->findOrFail($commentId);
        if ($comment->is_internal) {
            return response()->json(['success' => false, 'message' => __('orders.cannot_notify_internal')], 422);
        }

        $order = $comment->order;
        if (! $order->user || ! preg_match('/[0-9]/', (string) $order->user->phone)) {
            return response()->json(['success' => false, 'message' => __('orders.whatsapp_no_phone')], 422);
        }

        \App\Models\OrderCommentNotificationLog::create([
            'order_id' => $orderId,
            'comment_id' => $commentId,
            'user_id' => auth()->id(),
            'channel' => 'whatsapp',
            'sent_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => __('orders.whatsapp_send_logged')]);
    }

    /** Staff: add a timeline entry as a comment (visible to customer). */
    public function addTimelineAsComment(Request $request, int $orderId, int $timelineId)
    {
        $user = auth()->user();
        if (! $user->hasAnyRole(['editor', 'admin', 'superadmin'])) {
            abort(403);
        }

        $order = Order::findOrFail($orderId);
        $entry = $order->timeline()->findOrFail($timelineId);

        $body = $this->formatTimelineEntryAsCommentText($entry);

        $order->comments()->create([
            'user_id' => $user->id,
            'body' => $body,
            'is_internal' => false,
        ]);

        return redirect()->route('orders.show', $orderId)->with('success', __('orders.timeline_added_as_comment'));
    }

    /** Viewport-based: mark comments as read (batch). */
    public function markCommentsRead(Request $request, int $orderId): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();
        $order = Order::with('comments')->findOrFail($orderId);
        $isOwner = $order->user_id === $user->id;
        $isStaff = $user->hasAnyRole(['editor', 'admin', 'superadmin']);
        if (! $isOwner && ! $isStaff) {
            return response()->json(['success' => false], 403);
        }

        $commentIds = $request->input('comment_ids', []);
        if (! is_array($commentIds)) {
            $commentIds = [];
        }
        $commentIds = array_unique(array_filter(array_map('intval', $commentIds)));

        $visibleCommentIds = $order->comments
            ->filter(fn ($c) => ! $c->is_internal || $isStaff)
            ->filter(fn ($c) => ! $c->deleted_at)
            ->pluck('id')
            ->flip();

        $toUpsert = [];
        $now = now();
        foreach ($commentIds as $cid) {
            if (! $visibleCommentIds->has($cid)) {
                continue;
            }
            $toUpsert[] = [
                'comment_id' => $cid,
                'user_id' => $user->id,
                'read_at' => $now,
            ];
        }

        if (! empty($toUpsert)) {
            OrderCommentRead::upsert(
                $toUpsert,
                ['comment_id', 'user_id'],
                ['read_at']
            );
        }

        return response()->json(['success' => true]);
    }

    private function formatTimelineEntryAsCommentText(OrderTimeline $entry): string
    {
        $prefix = '📋 ';
        if ($entry->type === 'status_change') {
            $from = $entry->status_from ? __(ucfirst(str_replace('_', ' ', $entry->status_from))) : '—';
            $to = $entry->status_to ? __(ucfirst(str_replace('_', ' ', $entry->status_to))) : '—';

            return $prefix.__('orders.timeline_label_status_change').": {$from} → {$to}\n\n— ".optional($entry->user)->name.' — '.$entry->created_at?->format('Y/m/d H:i');
        }

        $label = match ($entry->type) {
            'comment' => __('orders.timeline_label_comment'),
            'note' => __('orders.timeline_label_note'),
            'payment' => __('orders.timeline_label_payment'),
            'merge' => __('orders.timeline_label_merge'),
            'file_upload' => __('orders.timeline_label_file_upload'),
            default => $entry->type,
        };

        $text = $prefix.$label;
        if ($entry->body) {
            $text .= ': '.$entry->body;
        }
        $text .= "\n\n— ".optional($entry->user)->name.' — '.$entry->created_at?->format('Y/m/d H:i');

        return $text;
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

        return $this->customerIndex($user, $request);
    }

    private function customerIndex($user, Request $request)
    {
        $query = Order::where('user_id', $user->id)->withCount('items');

        if ($search = trim($request->get('search', ''))) {
            $query->where('order_number', 'like', "%{$search}%");
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $sort = $request->get('sort', 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy('created_at', $sort);

        $perPage = in_array((int) $request->get('per_page'), [10, 25, 50])
            ? (int) $request->get('per_page')
            : 10;

        $orders = $query->paginate($perPage)->withQueryString();
        $statuses = Order::getStatuses();

        return view('orders.index', compact('orders', 'statuses', 'sort', 'perPage'));
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

        $sort = $request->get('sort', 'desc') === 'asc' ? 'asc' : 'desc';
        $requestedPerPage = (int) $request->get('per_page');
        $perPage = in_array($requestedPerPage, [25, 50, 100, 0]) ? $requestedPerPage : 25;

        $query->orderBy('created_at', $sort);

        // per_page=0 means show all — use a large number to avoid memory issues
        $orders = $perPage === 0
            ? $query->paginate(10000)->withQueryString()
            : $query->paginate($perPage)->withQueryString();
        $statuses = Order::getStatuses();

        return view('orders.staff', compact('orders', 'perPage', 'statuses', 'sort'));
    }

    public function bulkUpdate(Request $request)
    {
        $this->authorize('bulk-update-orders');

        $validated = $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['integer', 'exists:orders,id'],
            'new_status' => ['required', 'in:'.implode(',', array_keys(Order::getStatuses()))],
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

        $orders = $query->orderBy('created_at', 'desc')->limit(10000)->get();
        $filename = 'orders-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($orders) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM so Excel renders Arabic correctly
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
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

    /**
     * POST /orders/{id}/send-email
     * Staff-only: manually send an order confirmation email for a given order.
     */
    public function sendEmail(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $staff = auth()->user();

        if (! $staff->hasAnyRole(['editor', 'admin', 'superadmin'])) {
            abort(403);
        }

        $order = Order::with('user', 'items')->findOrFail($id);

        if (! $order->user || ! $order->user->email) {
            return response()->json([
                'success' => false,
                'message' => __('No valid recipient email address on this order.'),
            ], 422);
        }

        if (! Setting::get('email_enabled', false)) {
            return response()->json([
                'success' => false,
                'message' => __('Email sending is disabled. Enable it in Settings.'),
            ], 422);
        }

        $log = EmailLog::create([
            'order_id' => $order->id,
            'sent_by' => $staff->id,
            'recipient_email' => $order->user->email,
            'recipient_name' => $order->user->name,
            'type' => 'order_confirmation',
            'subject' => __('orders.order_confirmation_email_subject', ['number' => $order->order_number]),
            'queued' => true,
            'status' => 'queued',
        ]);

        try {
            Mail::to($order->user->email, $order->user->name)
                ->queue(new OrderConfirmation($order));

            $log->update(['status' => 'queued', 'sent_at' => now()]);

            // Add a system timeline entry so staff can see the email was triggered
            OrderTimeline::create([
                'order_id' => $order->id,
                'user_id' => $staff->id,
                'type' => 'note',
                'body' => __('Email sent: Order Confirmation').' → '.$order->user->email,
            ]);

            return response()->json([
                'success' => true,
                'message' => __('Order confirmation email queued successfully.'),
            ]);
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => __('Failed to queue email: ').$e->getMessage(),
            ], 500);
        }
    }

    // ─── Customer quick actions ───────────────────────────────────────────────

    /** Customer: report a bank transfer / payment notification → creates a comment */
    public function paymentNotify(Request $request, int $id)
    {
        $user = auth()->user();
        $order = Order::findOrFail($id);

        if ($order->user_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'transfer_amount' => ['required', 'numeric', 'min:0.01'],
            'transfer_bank' => ['required', 'string', 'max:100'],
            'transfer_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $body = __('orders.payment_notify_comment', [
            'amount' => $validated['transfer_amount'],
            'bank' => $validated['transfer_bank'],
        ]);

        if (! empty($validated['transfer_notes'])) {
            $body .= "\n".__('orders.payment_notify_notes').': '.$validated['transfer_notes'];
        }

        $order->comments()->create([
            'user_id' => $user->id,
            'body' => $body,
            'is_internal' => false,
        ]);

        $order->timeline()->create([
            'user_id' => $user->id,
            'type' => 'payment',
            'body' => __('orders.timeline_payment_notify', ['amount' => $validated['transfer_amount']]),
        ]);

        return redirect()->route('orders.show', $id)
            ->with('success', __('orders.payment_notify_sent'));
    }

    /** Customer: cancel own order (only when pending or needs_payment) */
    public function cancelOrder(Request $request, int $id)
    {
        $user = auth()->user();
        $order = Order::findOrFail($id);

        if ($order->user_id !== $user->id) {
            abort(403);
        }

        if (! $order->isCancellable()) {
            return redirect()->route('orders.show', $id)
                ->with('error', __('orders.cancel_not_allowed'));
        }

        $oldStatus = $order->status;
        $order->update(['status' => 'cancelled']);

        $order->timeline()->create([
            'user_id' => $user->id,
            'type' => 'status_change',
            'status_from' => $oldStatus,
            'status_to' => 'cancelled',
        ]);

        return redirect()->route('orders.show', $id)
            ->with('success', __('orders.cancelled_by_customer'));
    }

    /** Customer: request merge with another of their own orders → posts a comment */
    public function customerMerge(Request $request, int $id)
    {
        $user = auth()->user();
        $order = Order::findOrFail($id);

        if ($order->user_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'merge_with_order' => ['required', 'integer', 'different:'.$id],
        ]);

        $targetOrder = Order::where('user_id', $user->id)
            ->where('id', $validated['merge_with_order'])
            ->firstOrFail();

        $body = __('orders.customer_merge_request_comment', ['number' => $targetOrder->order_number]);

        $order->comments()->create([
            'user_id' => $user->id,
            'body' => $body,
            'is_internal' => false,
        ]);

        $order->timeline()->create([
            'user_id' => $user->id,
            'type' => 'merge',
            'body' => __('orders.timeline_customer_merge_request', ['number' => $targetOrder->order_number]),
        ]);

        return redirect()->route('orders.show', $id)
            ->with('success', __('orders.customer_merge_sent'));
    }

    // ─── Staff quick actions ──────────────────────────────────────────────────

    /** Staff: transfer order ownership to another customer by email */
    public function transferOrder(Request $request, int $id)
    {
        $user = auth()->user();
        $order = Order::with('user')->findOrFail($id);

        if (! $user->hasAnyRole(['editor', 'admin', 'superadmin'])) {
            abort(403);
        }

        $validated = $request->validate([
            'transfer_email' => ['required', 'email', 'max:255'],
        ]);

        $targetUser = \App\Models\User::where('email', $validated['transfer_email'])->first();

        if (! $targetUser) {
            // Create a new customer account with a 5-char temporary password
            $chars = 'abcdefghjkmnpqrstuvwxyz';
            $tempPass = '';
            for ($i = 0; $i < 5; $i++) {
                $tempPass .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $targetUser = \App\Models\User::create([
                'name' => $validated['transfer_email'],
                'email' => $validated['transfer_email'],
                'password' => bcrypt($tempPass),
                'email_verified_at' => now(),
            ]);
            $targetUser->assignRole('customer');

            // Store temp credentials in cache (5 min TTL)
            $tk = \Illuminate\Support\Str::random(16);
            cache()->put("transfer_creds_{$tk}", [
                'email' => $validated['transfer_email'],
                'password' => $tempPass,
            ], 300);

            $oldOwnerName = $order->user->name;
            $order->update(['user_id' => $targetUser->id]);

            $order->timeline()->create([
                'user_id' => $user->id,
                'type' => 'note',
                'body' => __('orders.timeline_order_transferred', [
                    'from' => $oldOwnerName,
                    'to' => $targetUser->email,
                ]),
            ]);

            return redirect()->route('orders.show', $id)
                ->with('transfer_new_user', ['email' => $validated['transfer_email'], 'password' => $tempPass]);
        }

        $oldOwnerName = $order->user->name;
        $order->update(['user_id' => $targetUser->id]);

        $order->timeline()->create([
            'user_id' => $user->id,
            'type' => 'note',
            'body' => __('orders.timeline_order_transferred', [
                'from' => $oldOwnerName,
                'to' => $targetUser->name,
            ]),
        ]);

        return redirect()->route('orders.show', $id)
            ->with('success', __('orders.order_transferred'));
    }

    /** Staff: update tracking number and shipping company */
    public function updateShippingTracking(Request $request, int $id)
    {
        $user = auth()->user();
        $order = Order::findOrFail($id);

        if (! $user->hasAnyRole(['editor', 'admin', 'superadmin'])) {
            abort(403);
        }

        $validated = $request->validate([
            'tracking_number' => ['nullable', 'string', 'max:100'],
            'tracking_company' => ['nullable', 'string', 'max:50'],
        ]);

        $order->update($validated);

        if (! empty($validated['tracking_number'])) {
            $order->timeline()->create([
                'user_id' => $user->id,
                'type' => 'note',
                'body' => __('orders.timeline_tracking_updated', ['number' => $validated['tracking_number']]),
            ]);
        }

        return redirect()->route('orders.show', $id)
            ->with('success', __('orders.tracking_updated'));
    }

    /** Staff: record payment details (amount, date, method, receipt) */
    public function updatePayment(Request $request, int $id)
    {
        $user = auth()->user();
        $order = Order::findOrFail($id);

        if (! $user->hasAnyRole(['editor', 'admin', 'superadmin'])) {
            abort(403);
        }

        $validated = $request->validate([
            'payment_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_date' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'string', 'in:bank_transfer,credit_card,cash,other'],
            'payment_receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,pdf', 'max:10240'],
        ]);

        $data = collect($validated)->except('payment_receipt')->toArray();

        if ($request->hasFile('payment_receipt')) {
            $path = $request->file('payment_receipt')->store('payment-receipts', 'public');
            $data['payment_receipt'] = $path;
        }

        $order->update($data);

        $order->timeline()->create([
            'user_id' => $user->id,
            'type' => 'payment',
            'body' => __('orders.timeline_payment_updated', [
                'amount' => $validated['payment_amount'] ?? '—',
            ]),
        ]);

        return redirect()->route('orders.show', $id)
            ->with('success', __('orders.payment_updated'));
    }

    /** Staff: update internal notes about this order/customer */
    public function updateStaffNotes(Request $request, int $id)
    {
        $user = auth()->user();
        $order = Order::findOrFail($id);

        if (! $user->hasAnyRole(['editor', 'admin', 'superadmin'])) {
            abort(403);
        }

        $validated = $request->validate([
            'staff_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $order->update(['staff_notes' => $validated['staff_notes'] ?? null]);

        return redirect()->route('orders.show', $id)
            ->with('success', __('orders.staff_notes_saved'));
    }
}
