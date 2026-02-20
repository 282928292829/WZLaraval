<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InboxController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:view-all-orders');
    }

    public function index(Request $request)
    {
        $validTypes = ['new_order', 'comment', 'payment', 'contact_form', 'status_change'];
        $filter     = $request->query('type', 'all');
        if (!in_array($filter, $validTypes)) {
            $filter = 'all';
        }

        $query = Activity::with('causer')->orderByDesc('created_at');

        if ($filter !== 'all') {
            $query->where('type', $filter);
        }

        $activities   = $query->paginate(30)->withQueryString();
        $unreadCount  = Activity::whereNull('read_at')->count();

        return view('inbox.index', compact('activities', 'filter', 'unreadCount'));
    }

    public function markAllRead(): RedirectResponse
    {
        Activity::whereNull('read_at')->update(['read_at' => now()]);

        return redirect()->route('inbox.index')->with('status', 'all-read');
    }

    public function markRead(Request $request, Activity $activity): RedirectResponse
    {
        if (!$activity->read_at) {
            $activity->update(['read_at' => now()]);
        }

        $url = $this->resolveUrl($activity);

        return $url ? redirect($url) : redirect()->route('inbox.index');
    }

    private function resolveUrl(Activity $activity): ?string
    {
        if ($activity->subject_type === 'App\\Models\\Order' && $activity->subject_id) {
            return route('orders.show', $activity->subject_id);
        }

        return null;
    }
}
