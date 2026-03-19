<?php

namespace App\Http\Controllers;

use App\Exports\OrderExport;
use App\Models\Order;
use App\Services\OrderCommentFilterService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderExportController extends Controller
{
    public function exportCsv(Request $request): StreamedResponse
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

        $awaiting = $request->get('awaiting');
        if (in_array($awaiting, OrderCommentFilterService::LAST_REPLY_VALUES, true)) {
            $preset = $request->get('no_response_preset');
            $customValue = $request->filled('no_response_value') ? (int) $request->get('no_response_value') : null;
            $customUnit = $request->get('no_response_unit');
            $orderIdsSubquery = OrderCommentFilterService::orderIdsAwaitingResponseSubquery(
                $awaiting,
                $preset === 'custom' ? 'custom' : $preset,
                $customValue,
                in_array($customUnit, ['hours', 'days'], true) ? $customUnit : null
            );
            $query->whereIn('id', $orderIdsSubquery);
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

    public function exportExcel(Order $order)
    {
        $this->authorize('view', $order);

        $order->load(['items' => fn ($q) => $q->orderBy('sort_order'), 'files']);

        $filename = 'order-'.$order->order_number.'-'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(new OrderExport($order), $filename, \Maatwebsite\Excel\Excel::XLSX);
    }
}
