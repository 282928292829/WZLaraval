<?php

namespace App\Http\Controllers;

use App\Http\Requests\Order\AttachCommentFilesRequest;
use App\Http\Requests\Order\StoreOrderCommentRequest;
use App\Http\Requests\Order\UpdateOrderCommentRequest;
use App\Models\Activity;
use App\Models\CommentTemplate;
use App\Models\Order;
use App\Models\OrderComment;
use App\Models\OrderCommentRead;
use App\Models\OrderTimeline;
use App\Models\Setting;
use App\Services\ImageConversionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class OrderCommentController extends Controller
{
    public function store(StoreOrderCommentRequest $request, Order $order)
    {
        $this->authorize('update', $order);

        $user = auth()->user();
        $isStaff = $user->isStaffOrAbove();
        $isInternal = $isStaff && $request->boolean('is_internal');

        $validated = $request->validated();

        if ($isStaff && ! empty($validated['template_id'])) {
            CommentTemplate::where('id', $validated['template_id'])->increment('usage_count');
        }

        $comment = $order->comments()->create([
            'user_id' => $user->id,
            'body' => $validated['body'] ?? '',
            'is_internal' => $isInternal,
        ]);

        foreach ($request->file('files', []) as $file) {
            $stored = app(ImageConversionService::class)->storeForDisplay($file, 'order-files/'.$order->id, 'public');
            $order->files()->create([
                'user_id' => $user->id,
                'comment_id' => $comment->id,
                'path' => $stored['path'],
                'original_name' => $stored['original_name'],
                'mime_type' => $stored['mime_type'],
                'size' => $stored['size'],
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
                    'note' => \Illuminate\Support\Str::limit($validated['body'] ?? '', 100),
                ],
                'created_at' => now(),
            ]);
        }

        return redirect()->route('orders.show', $order)->with('success', __('orders.comment_added'));
    }

    public function update(UpdateOrderCommentRequest $request, Order $order, int $commentId)
    {
        $comment = OrderComment::where('order_id', $order->id)->findOrFail($commentId);
        $this->authorize('update', $comment);

        $user = auth()->user();
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

        foreach ($request->file('files', []) as $file) {
            $stored = app(ImageConversionService::class)->storeForDisplay($file, 'order-files/'.$order->id, 'public');
            $order->files()->create([
                'user_id' => $user->id,
                'comment_id' => $comment->id,
                'path' => $stored['path'],
                'original_name' => $stored['original_name'],
                'mime_type' => $stored['mime_type'],
                'size' => $stored['size'],
                'type' => 'comment',
            ]);
        }

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('orders.comment_updated'),
                'body' => $comment->body,
                'edited_at' => $comment->edited_at->toIso8601String(),
            ]);
        }

        return redirect()->route('orders.show', $order)->with('success', __('orders.comment_updated'));
    }

    public function attachFiles(AttachCommentFilesRequest $request, Order $order, int $commentId)
    {
        $comment = OrderComment::where('order_id', $order->id)->findOrFail($commentId);
        $this->authorize('update', $comment);

        $user = auth()->user();
        $maxFiles = (int) \App\Models\Setting::get('comment_max_files', 10);
        $existingCount = $comment->files()->count();
        $newCount = count($request->file('files', []));

        if ($existingCount + $newCount > $maxFiles) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('comments.attach_limit_exceeded', ['max' => $maxFiles]),
                ], 422);
            }

            return redirect()->back()->with('error', __('comments.attach_limit_exceeded', ['max' => $maxFiles]));
        }

        $uploaded = [];

        foreach ($request->file('files', []) as $file) {
            $stored = app(ImageConversionService::class)->storeForDisplay($file, 'order-files/'.$order->id, 'public');
            $orderFile = $order->files()->create([
                'user_id' => $user->id,
                'comment_id' => $comment->id,
                'path' => $stored['path'],
                'original_name' => $stored['original_name'],
                'mime_type' => $stored['mime_type'],
                'size' => $stored['size'],
                'type' => 'comment',
            ]);
            $uploaded[] = [
                'id' => $orderFile->id,
                'url' => $orderFile->url(),
                'original_name' => $orderFile->original_name,
                'size' => $orderFile->size,
                'human_size' => $orderFile->humanSize(),
                'is_image' => $orderFile->isImage(),
            ];
        }

        $count = count($uploaded);
        $message = $count === 1
            ? __('comments.file_attached')
            : __('comments.files_attached', ['count' => $count]);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'files' => $uploaded,
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    public function destroy(Request $request, Order $order, int $commentId)
    {
        $comment = OrderComment::where('order_id', $order->id)->findOrFail($commentId);
        $this->authorize('delete', $comment);

        $user = auth()->user();
        $comment->update(['deleted_by' => $user->id]);
        $comment->delete();

        return redirect()->route('orders.show', $order)->with('success', __('orders.comment_deleted'));
    }

    public function sendNotification(Request $request, Order $order, int $commentId)
    {
        $this->authorize('send-comment-notification');

        $comment = OrderComment::where('order_id', $order->id)->with('order.user')->findOrFail($commentId);

        if (! Setting::get('email_enabled', false)) {
            return redirect()->route('orders.show', $order)
                ->with('error', __('orders.notification_email_disabled'));
        }

        if (! Setting::get('email_comment_notification', false)) {
            return redirect()->route('orders.show', $order)
                ->with('error', __('orders.notification_email_disabled'));
        }

        if (! $order->user || ! $order->user->email) {
            return redirect()->route('orders.show', $order)
                ->with('error', __('orders.notification_no_recipient'));
        }

        try {
            Mail::to($order->user->email, $order->user->name)
                ->locale($order->user->locale ?? 'ar')
                ->queue(new \App\Mail\CommentNotification($comment));

            \App\Models\OrderCommentNotificationLog::create([
                'order_id' => $order->id,
                'comment_id' => $commentId,
                'user_id' => auth()->id(),
                'channel' => 'email',
                'sent_at' => now(),
            ]);

            $order->timeline()->create([
                'user_id' => auth()->id(),
                'type' => 'note',
                'body' => __('orders.timeline_notification_sent', ['email' => $order->user->email]),
            ]);

            return redirect()->route('orders.show', $order)
                ->with('success', __('orders.notification_queued'));
        } catch (\Throwable $e) {
            return redirect()->route('orders.show', $order)
                ->with('error', __('orders.notification_failed').': '.$e->getMessage());
        }
    }

    public function logWhatsAppSend(Request $request, Order $order, int $commentId): \Illuminate\Http\JsonResponse
    {
        $this->authorize('send-comment-notification');

        $comment = OrderComment::where('order_id', $order->id)->findOrFail($commentId);
        if ($comment->is_internal) {
            return response()->json(['success' => false, 'message' => __('orders.cannot_notify_internal')], 422);
        }

        if (! $order->user || ! preg_match('/[0-9]/', (string) $order->user->phone)) {
            return response()->json(['success' => false, 'message' => __('orders.whatsapp_no_phone')], 422);
        }

        \App\Models\OrderCommentNotificationLog::create([
            'order_id' => $order->id,
            'comment_id' => $commentId,
            'user_id' => auth()->id(),
            'channel' => 'whatsapp',
            'sent_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => __('orders.whatsapp_send_logged')]);
    }

    public function addTimelineAsComment(Request $request, Order $order, int $timelineId)
    {
        $this->authorize('view-all-orders');

        $user = auth()->user();
        $entry = $order->timeline()->findOrFail($timelineId);

        $body = $this->formatTimelineEntryAsCommentText($entry);

        $order->comments()->create([
            'user_id' => $user->id,
            'body' => $body,
            'is_internal' => false,
        ]);

        return redirect()->route('orders.show', $order)->with('success', __('orders.timeline_added_as_comment'));
    }

    public function markRead(Request $request, Order $order): \Illuminate\Http\JsonResponse
    {
        $this->authorize('view', $order);

        $user = auth()->user();
        $order->load('comments');
        $isStaff = $user->isStaffOrAbove();

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
