<?php

namespace App\Http\Controllers;

use App\Exports\OrderExport;
use App\Http\Requests\Order\BulkUpdateOrdersRequest;
use App\Http\Requests\Order\CustomerMergeRequestRequest;
use App\Http\Requests\Order\GenerateInvoiceRequest;
use App\Http\Requests\Order\PaymentNotifyRequest;
use App\Http\Requests\Order\TransferOrderRequest;
use App\Http\Requests\Order\UpdatePaymentRequest;
use App\Http\Requests\Order\UpdatePricesRequest;
use App\Http\Requests\Order\UpdateShippingAddressRequest;
use App\Http\Requests\Order\UpdateStaffNotesRequest;
use App\Http\Requests\Order\UpdateTrackingRequest;
use App\Mail\OrderConfirmation;
use App\Models\EmailLog;
use App\Models\Order;
use App\Models\OrderFile;
use App\Models\OrderItem;
use App\Models\OrderTimeline;
use App\Models\Setting;
use App\Models\UserActivityLog;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
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
                ->when(auth()->user()?->hasAnyRole(['editor', 'admin', 'superadmin']), fn ($q) => $q->withTrashed())
                ->orderBy('created_at'),
        ])->findOrFail($id);

        $this->authorize('view', $order);

        $isOwner = $order->user_id === $user->id;
        $isStaff = $user->hasAnyRole(['editor', 'admin', 'superadmin']);

        // Read state is recorded by viewport-based tracking (JS), not on page load (matches WordPress).

        // Two-window edit flow: do NOT set can_edit_until on first view.
        // Window 1 (click): show Edit link when within click_window of submission.
        // Window 2 (resubmit): can_edit_until is set by NewOrder when user clicks Edit.

        $orderEditEnabled = (bool) Setting::get('order_edit_enabled', true);
        $clickWindowMinutes = (int) Setting::get('order_edit_click_window_minutes', 10);
        $clickEditDeadline = $order->created_at->copy()->addMinutes($clickWindowMinutes);

        $canEditItems = $orderEditEnabled
            && $isOwner
            && ! $order->is_paid
            && now()->lt($clickEditDeadline);

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

        $clickEditRemaining = $canEditItems ? now()->diffForHumans($clickEditDeadline, true) : null;

        return view('orders.show', compact(
            'order', 'isOwner', 'isStaff', 'orderEditEnabled', 'canEditItems', 'clickEditRemaining', 'recentOrders', 'customerRecentOrders', 'orderCreationLog', 'showCommentsDiscovery'
        ));
    }

    // ─── Staff: update prices on items ───────────────────────────────────

    public function updatePrices(UpdatePricesRequest $request, int $id)
    {
        $this->authorize('edit-prices');

        $order = Order::with('items')->findOrFail($id);
        $validated = $request->validated();

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

    // ─── Staff: generate invoice (PDF attached to comment) ─────────────────

    public function generateInvoice(GenerateInvoiceRequest $request, int $id)
    {
        $this->authorize('generate-pdf-invoice');

        $order = Order::with(['items', 'user'])->findOrFail($id);
        $validated = $request->validated();
        $action = $validated['action'] ?? 'publish';

        $invoiceType = $validated['invoice_type'] ?? 'detailed';
        $notes = $validated['custom_notes'] ?? '';
        $showOriginalCurrency = filter_var($validated['show_original_currency'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $lines = [];
        $itemsSubtotal = 0;

        if ($invoiceType === 'detailed') {
            foreach ($order->items as $item) {
                $priceSar = (float) ($item->final_price ?? $item->unit_price ?? 0);
                $lineTotalSar = $priceSar * ($item->qty ?? 1);
                if ($lineTotalSar > 0) {
                    $desc = $item->url
                        ? (strlen($item->url) > 60 ? substr($item->url, 0, 57).'...' : $item->url)
                        : ($item->notes ?: __('orders.item').' #'.(count($lines) + 1));
                    $currency = $item->currency ?: 'SAR';
                    $unitOriginal = (float) ($item->unit_price ?? 0);
                    $lines[] = [
                        'description' => $desc,
                        'qty' => $item->qty,
                        'unit_price' => number_format($priceSar, 2),
                        'unit_price_original' => $unitOriginal,
                        'currency' => $currency,
                        'line_total' => $lineTotalSar,
                        'show_original' => $showOriginalCurrency && $currency !== 'SAR' && $unitOriginal > 0,
                    ];
                    $itemsSubtotal += $lineTotalSar;
                }
            }

            $feeConfig = [
                'agent_fee' => ['key' => 'include_agent_fee', 'override' => 'fee_agent_fee', 'label' => 'orders.fee_agent_fee'],
                'local_shipping' => ['key' => 'include_local_shipping', 'override' => 'fee_local_shipping', 'label' => 'orders.fee_local_shipping'],
                'international_shipping' => ['key' => 'include_international_shipping', 'override' => 'fee_international_shipping', 'label' => 'orders.fee_international_shipping'],
                'photo_fee' => ['key' => 'include_photo_fee', 'override' => 'fee_photo_fee', 'label' => 'orders.fee_photo_fee'],
                'extra_packing' => ['key' => 'include_extra_packing', 'override' => 'fee_extra_packing', 'label' => 'orders.fee_extra_packing'],
            ];
            foreach ($feeConfig as $field => $cfg) {
                if (empty($validated[$cfg['key']])) {
                    continue;
                }
                $amount = isset($validated[$cfg['override']]) && $validated[$cfg['override']] !== ''
                    ? (float) $validated[$cfg['override']]
                    : (float) ($order->{$field} ?? 0);
                if ($amount > 0) {
                    $lines[] = [
                        'description' => __($cfg['label']),
                        'qty' => 1,
                        'unit_price' => number_format($amount, 2),
                        'unit_price_original' => 0,
                        'currency' => 'SAR',
                        'line_total' => $amount,
                        'show_original' => false,
                        'is_fee' => true,
                    ];
                    $itemsSubtotal += $amount;
                }
            }
        }

        $total = ! empty($validated['custom_amount']) ? (float) $validated['custom_amount'] : $itemsSubtotal;

        $invoiceCount = $order->files()->where('type', 'invoice')->count() + 1;
        $filename = $invoiceCount > 1
            ? 'Invoice-'.$order->order_number.'-'.$invoiceCount.'.pdf'
            : 'Invoice-'.$order->order_number.'.pdf';

        $pdfContent = $this->buildInvoicePdf($order, $lines, $total, $invoiceType, $notes, $filename);

        if ($action === 'preview') {
            return response()->streamDownload(
                fn () => print $pdfContent,
                $filename,
                ['Content-Type' => 'application/pdf'],
                'attachment'
            );
        }

        $dir = 'order-files/'.$order->id;
        $path = $dir.'/'.$filename;
        $fullPath = storage_path('app/public/'.$path);

        if (! is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }
        file_put_contents($fullPath, $pdfContent);

        $commentBody = $this->resolveInvoiceCommentMessage(
            $validated['comment_message'] ?? '',
            $total,
            $order
        );

        $comment = $order->comments()->create([
            'user_id' => auth()->id(),
            'body' => $commentBody,
            'is_internal' => false,
        ]);

        $order->files()->create([
            'user_id' => auth()->id(),
            'comment_id' => $comment->id,
            'path' => $path,
            'original_name' => $filename,
            'mime_type' => 'application/pdf',
            'size' => (int) strlen($pdfContent),
            'type' => 'invoice',
        ]);

        if ($invoiceType === 'detailed') {
            $feeFields = ['agent_fee', 'local_shipping', 'international_shipping', 'photo_fee', 'extra_packing'];
            $updates = [];
            foreach ($feeFields as $field) {
                if (! empty($validated['include_'.$field])) {
                    $val = isset($validated['fee_'.$field]) && $validated['fee_'.$field] !== ''
                        ? (float) $validated['fee_'.$field]
                        : (float) ($order->{$field} ?? 0);
                    $updates[$field] = $val;
                }
            }
            if (! empty($updates)) {
                $order->update($updates);
            }
        }

        return redirect()->route('orders.show', $id)->with('success', __('orders.invoice_generated'));
    }

    private function buildInvoicePdf(Order $order, array $lines, float $total, string $invoiceType, string $notes, string $filename): string
    {
        $invoiceLocale = $order->user?->locale ?? config('app.locale', 'ar');
        $isRtl = $invoiceLocale === 'ar';

        $originalLocale = app()->getLocale();
        app()->setLocale($invoiceLocale);

        $html = view('orders.invoice-pdf-mpdf', [
            'order' => $order,
            'lines' => $lines,
            'total' => $total,
            'invoiceType' => $invoiceType,
            'notes' => $notes,
            'isRtl' => $isRtl,
            'invoiceLocale' => $invoiceLocale,
        ])->render();

        app()->setLocale($originalLocale);

        $defaultConfig = (new \Mpdf\Config\ConfigVariables)->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];
        $defaultFontConfig = (new \Mpdf\Config\FontVariables)->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];
        $fontPath = realpath(base_path('storage/fonts')) ?: base_path('storage/fonts');

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'tempDir' => storage_path('framework/cache'),
            'fontDir' => array_merge($fontDirs, [$fontPath]),
            'fontdata' => $fontData + [
                'ibmplexarabic' => [
                    'R' => 'IBMPlexSansArabic-Regular.ttf',
                    'B' => 'IBMPlexSansArabic-Bold.ttf',
                    'useOTL' => 0xFF,
                    'useKashida' => 75,
                ],
            ],
            'default_font' => $isRtl ? 'ibmplexarabic' : 'dejavusans',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
        ]);
        $mpdf->SetTitle($isRtl ? 'فاتورة رقم '.$order->order_number : 'Invoice '.$order->order_number);
        if ($isRtl) {
            $mpdf->SetDirectionality('rtl');
        }
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    private function resolveInvoiceCommentMessage(string $message, float $total, Order $order): string
    {
        $default = Setting::get('invoice_comment_default') ?: __('orders.invoice_attached');
        $text = trim($message) !== '' ? $message : $default;

        $replacements = [
            '{amount}' => number_format($total, 2),
            '{order_number}' => $order->order_number,
            '{date}' => now()->format('Y-m-d H:i'),
            '{currency}' => 'SAR',
        ];

        return '📄 '.str_replace(array_keys($replacements), array_values($replacements), $text);
    }

    // ─── Update shipping address on order ────────────────────────────────

    public function updateShippingAddress(UpdateShippingAddressRequest $request, int $id)
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

        $validated = $request->validated();

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
        $query = Order::query()
            ->select(['id', 'order_number', 'user_id', 'status', 'created_at', 'subtotal', 'total_amount', 'currency', 'is_paid'])
            ->with(['user:id,name,email'])
            ->withCount('items');

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
        $allowedPerPage = [25, 50, 100, 250];
        $perPage = in_array($requestedPerPage, $allowedPerPage) ? $requestedPerPage : 25;

        $query->orderBy('created_at', $sort);

        $orders = $query->paginate($perPage)->withQueryString();
        $statuses = Order::getStatuses();

        return view('orders.staff', compact('orders', 'perPage', 'statuses', 'sort'));
    }

    public function bulkUpdate(BulkUpdateOrdersRequest $request)
    {
        $this->authorize('bulk-update-orders');

        $validated = $request->validated();

        $count = count($validated['order_ids']);

        if (in_array($validated['new_status'], ['cancelled', 'shipped', 'delivered'])) {
            $orders = Order::whereIn('id', $validated['order_ids'])->with('user')->get();
            foreach ($orders as $order) {
                \App\Models\AdCampaign::incrementForOrderStatus($order, $validated['new_status']);
            }
        }

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
                ->locale($order->user->locale ?? 'ar')
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
                'message' => __('orders.email_queue_failed', ['error' => $e->getMessage()]),
            ], 500);
        }
    }

    // ─── Customer quick actions ───────────────────────────────────────────────

    /** Customer: report a bank transfer / payment notification → creates a comment */
    public function paymentNotify(PaymentNotifyRequest $request, int $id)
    {
        $user = auth()->user();
        $order = Order::findOrFail($id);

        if ($order->user_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validated();

        $bankLabel = $validated['transfer_bank'] === 'other'
            ? __('orders.bank_other')
            : __('orders.banks.'.$validated['transfer_bank']);

        $body = __('orders.payment_notify_comment', [
            'amount' => $validated['transfer_amount'],
            'bank' => $bankLabel,
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

        \App\Models\AdCampaign::incrementCancelledForOrder($order);

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
    public function customerMerge(CustomerMergeRequestRequest $request, int $id)
    {
        $user = auth()->user();
        $order = Order::findOrFail($id);

        if ($order->user_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validated();

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
    public function transferOrder(TransferOrderRequest $request, int $id)
    {
        $user = auth()->user();
        $order = Order::with('user')->findOrFail($id);

        if (! $user->hasAnyRole(['editor', 'admin', 'superadmin'])) {
            abort(403);
        }

        $validated = $request->validated();

        $targetUser = \App\Models\User::where('email', $validated['transfer_email'])->first();

        if (! $targetUser) {
            // Create a new customer account with a 6-char temporary password
            $chars = 'abcdefghjkmnpqrstuvwxyz';
            $tempPass = '';
            for ($i = 0; $i < 6; $i++) {
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
    public function updateShippingTracking(UpdateTrackingRequest $request, int $id)
    {
        $user = auth()->user();
        $order = Order::findOrFail($id);

        if (! $user->hasAnyRole(['editor', 'admin', 'superadmin'])) {
            abort(403);
        }

        $validated = $request->validated();

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
    public function updatePayment(UpdatePaymentRequest $request, int $id)
    {
        $user = auth()->user();
        $order = Order::findOrFail($id);

        if (! $user->hasAnyRole(['editor', 'admin', 'superadmin'])) {
            abort(403);
        }

        $validated = $request->validated();

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
    public function updateStaffNotes(UpdateStaffNotesRequest $request, int $id)
    {
        $user = auth()->user();
        $order = Order::findOrFail($id);

        if (! $user->hasAnyRole(['editor', 'admin', 'superadmin'])) {
            abort(403);
        }

        $validated = $request->validated();

        $order->update(['staff_notes' => $validated['staff_notes'] ?? null]);

        return redirect()->route('orders.show', $id)
            ->with('success', __('orders.staff_notes_saved'));
    }

    /** Staff: delete product image (from order_items.image_path or order_files) */
    public function deleteProductImage(Request $request, int $orderId)
    {
        $user = auth()->user();
        if (! $user->hasAnyRole(['editor', 'admin', 'superadmin'])) {
            abort(403);
        }

        $order = Order::findOrFail($orderId);

        $itemId = $request->input('item_id');
        $fileId = $request->input('file_id');

        if ($itemId) {
            $item = OrderItem::where('order_id', $orderId)->findOrFail($itemId);
            if ($item->image_path) {
                Storage::disk('public')->delete($item->image_path);
                $item->update(['image_path' => null]);
            }
        } elseif ($fileId) {
            $file = OrderFile::where('order_id', $orderId)
                ->where('type', 'product_image')
                ->findOrFail($fileId);
            Storage::disk('public')->delete($file->path);
            $file->delete();
        } else {
            abort(400, __('orders.delete_image_param_required'));
        }

        return redirect()->route('orders.show', $orderId)
            ->with('success', __('orders.product_image_deleted'));
    }

    /** Staff: export single order to Excel (links, specs, image URLs) */
    public function exportExcel(int $id)
    {
        $user = auth()->user();
        if (! $user->hasAnyRole(['editor', 'admin', 'superadmin'])) {
            abort(403);
        }

        $order = Order::with(['items' => fn ($q) => $q->orderBy('sort_order'), 'files'])->findOrFail($id);
        $this->authorize('view', $order);

        $filename = 'order-'.$order->order_number.'-'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(new OrderExport($order), $filename, \Maatwebsite\Excel\Excel::XLSX);
    }
}
