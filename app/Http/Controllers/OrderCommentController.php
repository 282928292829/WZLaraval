<?php

namespace App\Http\Controllers;

use App\Http\Requests\Order\StoreOrderCommentRequest;
use App\Http\Requests\Order\UpdateOrderCommentRequest;
use App\Models\Activity;
use App\Models\CommentTemplate;
use App\Models\Order;
use App\Models\OrderComment;
use App\Models\OrderCommentRead;
use App\Models\OrderTimeline;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class OrderCommentController extends Controller
{
    public function store(StoreOrderCommentRequest $request, int $id)
    {
        $user = auth()->user();
        $order = Order::findOrFail($id);

        $isOwner = $order->user_id === $user->id;
        $isStaff = $user->hasAnyRole(['editor', 'admin', 'superadmin']);

        if (! $isOwner && ! $isStaff) {
            abort(403);
        }

        $isInternal = $isStaff && $request->boolean('is_internal');

        $validated = $request->validated();

        if ($isStaff && ! empty($validated['template_id'])) {
            CommentTemplate::where('id', $validated['template_id'])->increment('usage_count');
        }

        $comment = $order->comments()->create([
            'user_id' => $user->id,
            'body' => $validated['body'],
            'is_internal' => $isInternal,
        ]);

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

        $order->timeline()->create([
            'user_id' => $user->id,
            'type' => $isInternal ? 'note' : 'comment',
            'body' => $isInternal ? __('orders.timeline_internal_note') : __('orders.timeline_comment_added'),
        ]);

        if (! $isInternal) {
            Activity::create([
                'type' => 'comment',
                'subject_type' => Order::class,
                'subject_id' => $order->id,
                'causer_id' => $user->id,
                'data' => [
                    'order_number' => $order->order_number,
                    'note' => \Illuminate\Support\Str::limit($validated['body'], 100),
                ],
                'created_at' => now(),
            ]);
        }

        return redirect()->route('orders.show', $order->id)->with('success', __('orders.comment_added'));
    }

    public function update(UpdateOrderCommentRequest $request, int $orderId, int $commentId)
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

        $validated = $request->validated();

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

    public function destroy(Request $request, int $orderId, int $commentId)
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
                ->locale($order->user->locale ?? 'ar')
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

    public function markRead(Request $request, int $orderId): \Illuminate\Http\JsonResponse
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
}
