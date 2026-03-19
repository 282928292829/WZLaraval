<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderComment;
use App\Models\OrderCommentRead;
use App\Models\Setting;
use App\Services\OrderCommentFilterService;
use Illuminate\Http\Request;

class CommentsController extends Controller
{
    public function index(Request $request)
    {
        $query = OrderComment::query()
            ->with(['order:id,order_number,user_id,status', 'user:id,name', 'files'])
            ->when(auth()->user()?->isStaffOrAbove(), fn ($q) => $q->withTrashed());

        if ($search = trim($request->get('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('body', 'like', "%{$search}%")
                    ->orWhereHas('order', fn ($o) => $o->where('order_number', 'like', "%{$search}%"))
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }

        if ($internal = $request->get('internal')) {
            if ($internal === '1') {
                $query->where('is_internal', true);
            } elseif ($internal === '0') {
                $query->where('is_internal', false);
            }
        }

        if ($orderStatus = $request->get('order_status')) {
            $query->whereHas('order', fn ($o) => $o->where('status', $orderStatus));
        }

        $authorType = $request->get('author_type');
        if ($authorType === 'customer') {
            $query->whereNotNull('user_id')
                ->whereHas('order', fn ($o) => $o->whereColumn('orders.user_id', 'order_comments.user_id'));
        } elseif ($authorType === 'staff') {
            $query->where(function ($q): void {
                $q->whereNull('user_id')
                    ->orWhereHas('user', fn ($u) => $u->staff());
            });
        }

        if ($request->get('has_attachment') === '1') {
            $query->whereHas('files');
        }

        $dateRange = $request->get('date_range');
        if ($dateRange === 'today') {
            $query->whereDate('created_at', today());
        } elseif ($dateRange === '7days') {
            $query->where('created_at', '>=', now()->subDays(7));
        }

        $awaiting = $request->get('awaiting');
        if (in_array($awaiting, \App\Services\OrderCommentFilterService::LAST_REPLY_VALUES, true)) {
            $preset = $request->get('no_response_preset');
            $customValue = $request->filled('no_response_value') ? (int) $request->get('no_response_value') : null;
            $customUnit = $request->get('no_response_unit');
            $orderIdsSubquery = OrderCommentFilterService::orderIdsAwaitingResponseSubquery(
                $awaiting,
                $preset === 'custom' ? 'custom' : $preset,
                $customValue,
                in_array($customUnit, ['hours', 'days'], true) ? $customUnit : null
            );
            $query->whereIn('order_id', $orderIdsSubquery);
        }

        $unreadCount = auth()->id()
            ? (clone $query)->whereDoesntHave('reads', fn ($r) => $r->where('user_id', auth()->id()))->count()
            : 0;

        if ($request->get('unread') === '1' && auth()->id()) {
            $query->whereDoesntHave('reads', fn ($r) => $r->where('user_id', auth()->id()));
        }

        $sort = $request->get('sort', 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy('created_at', $sort);

        $perPage = in_array((int) $request->get('per_page', 25), [10, 25, 50, 100], true)
            ? (int) $request->get('per_page', 25)
            : 25;

        $comments = $query->paginate($perPage)->withQueryString();

        $formAction = route('comments.index');
        $clearFiltersUrl = route('comments.index');
        $maxFilesPerComment = (int) Setting::get('comment_max_files', 10);
        $maxFileSizeMb = (int) Setting::get('comment_max_file_size_mb', 10);
        $acceptFileTypes = implode(',', array_map(fn (string $ext): string => '.'.$ext, explode(',', allowed_upload_mimes())));
        $statuses = Order::getStatuses();

        return view('comments.index', compact(
            'comments',
            'formAction',
            'clearFiltersUrl',
            'maxFilesPerComment',
            'maxFileSizeMb',
            'acceptFileTypes',
            'statuses',
            'unreadCount'
        ));
    }

    public function markAllRead(Request $request): \Illuminate\Http\RedirectResponse
    {
        $query = OrderComment::query()
            ->when(auth()->user()?->isStaffOrAbove(), fn ($q) => $q->withTrashed());

        if ($search = trim($request->get('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('body', 'like', "%{$search}%")
                    ->orWhereHas('order', fn ($o) => $o->where('order_number', 'like', "%{$search}%"))
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }
        if ($internal = $request->get('internal')) {
            if ($internal === '1') {
                $query->where('is_internal', true);
            } elseif ($internal === '0') {
                $query->where('is_internal', false);
            }
        }
        if ($orderStatus = $request->get('order_status')) {
            $query->whereHas('order', fn ($o) => $o->where('status', $orderStatus));
        }
        $authorType = $request->get('author_type');
        if ($authorType === 'customer') {
            $query->whereNotNull('user_id')
                ->whereHas('order', fn ($o) => $o->whereColumn('orders.user_id', 'order_comments.user_id'));
        } elseif ($authorType === 'staff') {
            $query->where(function ($q): void {
                $q->whereNull('user_id')
                    ->orWhereHas('user', fn ($u) => $u->staff());
            });
        }
        if ($request->get('has_attachment') === '1') {
            $query->whereHas('files');
        }
        $dateRange = $request->get('date_range');
        if ($dateRange === 'today') {
            $query->whereDate('created_at', today());
        } elseif ($dateRange === '7days') {
            $query->where('created_at', '>=', now()->subDays(7));
        }
        $awaiting = $request->get('awaiting');
        if (in_array($awaiting, \App\Services\OrderCommentFilterService::LAST_REPLY_VALUES, true)) {
            $preset = $request->get('no_response_preset');
            $customValue = $request->filled('no_response_value') ? (int) $request->get('no_response_value') : null;
            $customUnit = $request->get('no_response_unit');
            $orderIdsSubquery = OrderCommentFilterService::orderIdsAwaitingResponseSubquery(
                $awaiting,
                $preset === 'custom' ? 'custom' : $preset,
                $customValue,
                in_array($customUnit, ['hours', 'days'], true) ? $customUnit : null
            );
            $query->whereIn('order_id', $orderIdsSubquery);
        }
        if ($request->get('unread') === '1' && auth()->id()) {
            $query->whereDoesntHave('reads', fn ($r) => $r->where('user_id', auth()->id()));
        }
        $query->orderBy('created_at', $request->get('sort', 'desc') === 'asc' ? 'asc' : 'desc');

        $commentIds = $query->limit(5000)->pluck('id')->all();
        $user = auth()->user();
        if (! empty($commentIds) && $user) {
            $now = now();
            $rows = array_map(fn ($id) => [
                'comment_id' => $id,
                'user_id' => $user->id,
                'read_at' => $now,
            ], $commentIds);
            OrderCommentRead::upsert($rows, ['comment_id', 'user_id'], ['read_at']);
        }

        return redirect()->route('comments.index', $request->query())->with('success', __('comments.mark_all_read_done'));
    }
}
