<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        // ── Customer dashboard ────────────────────────────────────────────
        if ($user->hasRole('customer')) {
            $recentOrders = Order::where('user_id', $user->id)
                ->withCount('items')
                ->latest()
                ->take(5)
                ->get();

            // Single query: get all statuses for this user, count in PHP
            $statusCounts = Order::where('user_id', $user->id)
                ->selectRaw('status, count(*) as cnt')
                ->groupBy('status')
                ->pluck('cnt', 'status');

            $stats = [
                'total'        => $statusCounts->sum(),
                'open'         => $statusCounts->filter(
                    fn ($c, $s) => ! in_array($s, ['completed', 'cancelled'])
                )->sum(),
                'needs_action' => ($statusCounts['needs_payment'] ?? 0)
                    + ($statusCounts['on_hold'] ?? 0),
            ];

            return view('dashboard', compact('recentOrders', 'stats'));
        }

        // ── Staff dashboard (editor / admin / superadmin) ─────────────────
        // Single query: aggregate all stats, group by status
        $allStatusCounts = Order::selectRaw('status, count(*) as cnt, DATE(created_at) = CURDATE() as is_today')
            ->groupBy('status', 'is_today')
            ->get();

        $orderStats = [
            'total_today'   => $allStatusCounts->where('is_today', 1)->sum('cnt'),
            'open'          => $allStatusCounts
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->sum('cnt'),
            'needs_payment' => $allStatusCounts->where('status', 'needs_payment')->sum('cnt'),
            'processing'    => $allStatusCounts
                ->whereIn('status', ['processing', 'purchasing'])
                ->sum('cnt'),
        ];

        $recentActivity = Activity::with('causer')
            ->latest('created_at')
            ->take(15)
            ->get();

        return view('dashboard', compact('orderStats', 'recentActivity'));
    }
}
