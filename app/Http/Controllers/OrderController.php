<?php

namespace App\Http\Controllers;

use App\Http\Requests\Order\BulkUpdateOrdersRequest;
use App\Http\Requests\Order\CustomerMergeRequestRequest;
use App\Http\Requests\Order\PaymentNotifyRequest;
use App\Http\Requests\Order\StoreOrderItemFilesRequest;
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
use App\Models\ShippingCompany;
use App\Models\UserActivityLog;
use App\Models\UserAddress;
use App\Services\CommissionCalculator;
use App\Services\ImageConversionService;
use App\Services\OrderCommentFilterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    public function show(Request $request, Order $order)
    {
        $user = auth()->user();
        $order->loadMissing([
            'user' => fn ($q) => $q->with(['addresses' => fn ($a) => $a->orderByDesc('is_default')]),
            'mergedInto',
            'items' => fn ($q) => $q->orderBy('sort_order'),
            'files' => fn ($q) => $q->orderBy('created_at'),
            'timeline' => fn ($q) => $q->with('user')->orderBy('created_at'),
            'comments' => fn ($q) => $q
                ->with(['user', 'edits.editor', 'reads.user', 'notificationLogs.user'])
                ->when(auth()->user()?->isStaffOrAbove(), fn ($q) => $q->withTrashed())
                ->orderBy('created_at'),
        ]);

        $this->authorize('view', $order);

        $isOwner = $order->user_id === $user->id;
        $isStaff = $user->isStaffOrAbove();

        // Read state is recorded by viewport-based tracking (JS), not on page load (matches WordPress).

        // Two-window edit flow: do NOT set can_edit_until on first view.
        // Window 1 (click): show Edit link when within click_window of submission.
        // Window 2 (resubmit): can_edit_until is set by NewOrder when user clicks Edit.

        $orderEditEnabled = (bool) Setting::get('order_edit_enabled', true);
        $clickWindowMinutes = (int) Setting::get('order_edit_click_window_minutes', 10);
        $clickEditDeadline = $order->created_at->copy()->addMinutes($clickWindowMinutes);
        $inResubmitWindow = $order->can_edit_until && now()->lt($order->can_edit_until);

        $canEditItems = $orderEditEnabled
            && $isOwner
            && ! $order->is_paid
            && (now()->lt($clickEditDeadline) || $inResubmitWindow);

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

        // Comments discovery banner: first order only, first 2 visits, unless dismissed forever
        $isFirstOrder = Order::where('user_id', $order->user_id)
            ->orderBy('created_at')
            ->value('id') === $order->id;
        $dismissedForever = $request->cookie('comments_discovery_dismissed_forever') === '1';
        $cookieName = 'order_discovery_visits_'.$order->id;
        $visits = (int) $request->cookie($cookieName, 0);
        $showCommentsDiscovery = $isFirstOrder && ! $dismissedForever && $visits < 2;
        if ($showCommentsDiscovery) {
            cookie()->queue($cookieName, (string) ($visits + 1), 60 * 24 * 365); // 1 year
        }

        $clickEditRemaining = $canEditItems
            ? now()->diffForHumans($inResubmitWindow ? $order->can_edit_until : $clickEditDeadline, true)
            : null;

        $invoiceDefaults = $this->invoiceDefaultsForOrder($order);
        $commissionSettings = CommissionCalculator::getSettings();

        $trackingCarriers = ShippingCompany::forTracking()->get();

        return view('orders.show', compact(
            'order', 'isOwner', 'isStaff', 'orderEditEnabled', 'canEditItems', 'clickEditRemaining', 'recentOrders', 'customerRecentOrders', 'orderCreationLog', 'showCommentsDiscovery', 'invoiceDefaults', 'commissionSettings', 'trackingCarriers'
        ));
    }

    /**
     * Set "don't show again" cookie for comments discovery banner.
     */
    public function dismissCommentsDiscovery(Request $request)
    {
        cookie()->queue('comments_discovery_dismissed_forever', '1', 60 * 24 * 365); // 1 year

        return $request->wantsJson()
            ? response()->json(['ok' => true])
            : redirect()->back();
    }

    /**
     * Order success page — shown after new order submission for first N orders per customer.
     * Configurable via Settings: enable/disable, threshold (N), countdown seconds, and all text.
     */
    public function success(Order $order)
    {
        $this->authorize('view', $order);

        $locale = app()->getLocale();
        $seconds = max(0, min(120, (int) Setting::get('order_success_redirect_seconds', 30)));

        $titleRaw = trim((string) Setting::get('order_success_title_'.$locale, ''));
        $title = $titleRaw !== '' ? $titleRaw : __('order.success_title');

        $subtitleRaw = trim((string) Setting::get('order_success_subtitle_'.$locale, ''));
        $subtitle = $subtitleRaw !== ''
            ? str_replace([':number', ':order_number'], $order->order_number, $subtitleRaw)
            : __('order.success_subtitle', ['number' => $order->order_number]);

        $messageRaw = trim((string) Setting::get('order_success_message_'.$locale, ''));
        $message = $messageRaw !== ''
            ? str_replace([':number', ':order_number'], $order->order_number, $messageRaw)
            : __('order.success_message');

        $goToOrderRaw = trim((string) Setting::get('order_success_go_to_order_'.$locale, ''));
        $goToOrder = $goToOrderRaw !== '' ? $goToOrderRaw : __('order.success_go_to_order');

        $prefixRaw = trim((string) Setting::get('order_success_redirect_prefix_'.$locale, ''));
        $prefix = $prefixRaw !== '' ? $prefixRaw : __('order.success_redirect_countdown_prefix');

        $suffixRaw = trim((string) Setting::get('order_success_redirect_suffix_'.$locale, ''));
        $suffix = $suffixRaw !== '' ? $suffixRaw : __('order.success_redirect_countdown_suffix');

        return view('orders.success', compact(
            'order', 'title', 'subtitle', 'message', 'goToOrder', 'prefix', 'suffix', 'seconds'
        ));
    }

    // ─── Staff: update prices on items ───────────────────────────────────

    public function updatePrices(UpdatePricesRequest $request, Order $order)
    {
        $this->authorize('edit-prices');

        $order->load('items');
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

        return redirect()->route('orders.show', $order)->with('success', __('orders.prices_updated'));
    }

    /** @return array<string, mixed> */
    private function invoiceDefaultsForOrder(Order $order): array
    {
        $order->loadMissing('items');
        $productValue = (float) ($order->subtotal ?? 0);
        if ($productValue <= 0 && $order->items->isNotEmpty()) {
            $productValue = (float) $order->items->sum(fn (OrderItem $i) => ((float) ($i->final_price ?? $i->unit_price ?? 0)) * ($i->qty ?? 1));
        }
        $agentFee = (float) ($order->agent_fee ?? 0);
        $shippingCost = (float) ($order->international_shipping ?? 0);
        $firstPayment = (float) ($order->payment_amount ?? 0);
        $total = $productValue + $agentFee + $shippingCost;
        $remaining = max(0, $total - $firstPayment);

        $customLines = Setting::get('invoice_custom_lines', []);
        $customLines = is_array($customLines) ? $customLines : [];

        $firstItemsTotal = $productValue;
        $firstAgentFee = $agentFee > 0 ? $agentFee : CommissionCalculator::calculate($firstItemsTotal);

        return [
            'first_items_total' => $firstItemsTotal,
            'first_agent_fee' => $firstAgentFee,
            'first_other_label' => '',
            'first_other_amount' => 0.0,
            'second_product_value' => $productValue,
            'second_agent_fee' => $agentFee,
            'second_shipping_cost' => $shippingCost,
            'second_first_payment' => $firstPayment,
            'second_remaining' => $remaining,
            'second_weight' => '',
            'second_shipping_company' => $order->tracking_company ?? '',
            'show_order_items' => (bool) Setting::get('invoice_show_order_items', false),
            'custom_lines' => $customLines,
        ];
    }

    // ─── Update shipping address on order ────────────────────────────────

    public function updateShippingAddress(UpdateShippingAddressRequest $request, Order $order)
    {
        $this->authorize('update', $order);

        $user = auth()->user();

        // Only allow change while order is in an editable state
        $editableStatuses = ['pending', 'needs_payment', 'on_hold'];
        if (! in_array($order->status, $editableStatuses)) {
            return redirect()->route('orders.show', $order)
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

        return redirect()->route('orders.show', $order)
            ->with('success', __('orders.address_updated'));
    }

    public function index(Request $request)
    {
        return $this->customerIndex(auth()->user(), $request);
    }

    public function allOrders(Request $request)
    {
        $user = auth()->user();
        $this->authorize('view-all-orders');

        if ($request->get('export') === 'csv' && $user->can('export-csv')) {
            return app(OrderExportController::class)->exportCsv($request);
        }

        return $this->staffIndex($request);
    }

    public function indexVariant(Request $request, string $variant)
    {
        $user = auth()->user();
        if ($user->isStaffOrAbove()) {
            return redirect()->route('orders.index');
        }

        $data = $this->customerIndexData($user, $request);
        $data['listRoute'] = route('orders.list-variant', ['variant' => $variant]);
        $data['clearFiltersRoute'] = route('orders.list-variant', ['variant' => $variant]);

        return view("orders.index-{$variant}", $data);
    }

    private function customerIndex($user, Request $request)
    {
        $data = $this->customerIndexData($user, $request);
        $data['formAction'] = route('orders.index');
        $data['clearFiltersUrl'] = route('orders.index');

        return view('orders.index', $data);
    }

    /** @return array{orders: \Illuminate\Contracts\Pagination\LengthAwarePaginator, statuses: array, sort: string, perPage: int, lastOrder: ?Order, orderStats: array} */
    private function customerIndexData($user, Request $request): array
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

        $lastOrder = Order::where('user_id', $user->id)
            ->withCount('items')
            ->latest()
            ->first();

        $orderStats = [
            'total' => Order::where('user_id', $user->id)->count(),
            'active' => Order::where('user_id', $user->id)
                ->whereNotIn('status', ['cancelled', 'delivered', 'completed'])
                ->count(),
            'delivered' => Order::where('user_id', $user->id)
                ->where('status', 'delivered')
                ->count(),
            'cancelled' => Order::where('user_id', $user->id)
                ->where('status', 'cancelled')
                ->count(),
        ];

        return compact('orders', 'statuses', 'sort', 'perPage', 'lastOrder', 'orderStats');
    }

    private function staffIndex(Request $request)
    {
        $query = Order::query()
            ->select(['id', 'order_number', 'user_id', 'status', 'created_at', 'subtotal', 'total_amount', 'currency', 'is_paid'])
            ->with(['user:id,name,email', 'lastComment.user'])
            ->withCount(['items', 'comments']);

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
        $orders = Order::whereIn('id', $validated['order_ids'])->with('user')->get();

        if (in_array($validated['new_status'], ['cancelled', 'shipped', 'delivered'])) {
            foreach ($orders as $order) {
                \App\Models\AdCampaign::incrementForOrderStatus($order, $validated['new_status']);
            }
        }

        $commentBody = isset($validated['comment']) ? trim($validated['comment']) : null;
        $hasComment = $commentBody !== null && $commentBody !== '';

        foreach ($orders as $order) {
            $oldStatus = $order->status;
            $order->update([
                'status' => $validated['new_status'],
                'status_changed_at' => now(),
            ]);

            if ($hasComment) {
                $order->comments()->create([
                    'user_id' => auth()->id(),
                    'body' => $commentBody,
                    'is_internal' => false,
                ]);
            }

            $order->timeline()->create([
                'user_id' => auth()->id(),
                'type' => 'status_change',
                'status_from' => $oldStatus,
                'status_to' => $validated['new_status'],
                'body' => null,
            ]);
        }

        return back()->with('success', __('orders.bulk_status_updated', ['count' => $count]));
    }

    /**
     * POST /orders/{id}/send-email
     * Staff-only: manually send an order confirmation email for a given order.
     */
    public function sendEmail(Request $request, Order $order): \Illuminate\Http\JsonResponse
    {
        $this->authorize('view-all-orders');

        $staff = auth()->user();
        $order->load(['user', 'items']);

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

        if (! Setting::get('email_order_confirmation', true)) {
            return response()->json([
                'success' => false,
                'message' => __('Order confirmation emails are disabled. Enable them in Email Settings.'),
            ], 422);
        }

        $log = EmailLog::create([
            'order_id' => $order->id,
            'sent_by' => $staff->id,
            'recipient_email' => $order->user->email,
            'recipient_name' => $order->user->name,
            'type' => 'order_confirmation',
            'subject' => __('orders.order_confirmation_email_subject', ['number' => $order->order_number, 'site_name' => Setting::get('site_name') ?: config('app.name')]),
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
    public function paymentNotify(PaymentNotifyRequest $request, Order $order)
    {
        $this->authorize('performCustomerAction', $order);

        $user = auth()->user();
        $validated = $request->validated();

        $bankLabel = match ($validated['transfer_bank']) {
            'other' => __('orders.bank_other'),
            'visa_mastercard' => __('orders.payment_method_visa_mastercard'),
            'international_bank_transfer' => __('orders.payment_method_international_bank_transfer'),
            default => __('orders.banks.'.$validated['transfer_bank']),
        };

        $body = __('orders.payment_notify_comment', [
            'amount' => $validated['transfer_amount'],
            'bank' => $bankLabel,
        ]);

        if (! empty($validated['transfer_notes'])) {
            $body .= "\n".__('orders.payment_notify_notes').': '.$validated['transfer_notes'];
        }

        $comment = $order->comments()->create([
            'user_id' => $user->id,
            'body' => $body,
            'is_internal' => false,
        ]);

        $maxFiles = max(0, (int) Setting::get('payment_notify_order_max_files', 5));
        $receiptFiles = $request->file('receipts', []);
        if (count($receiptFiles) > $maxFiles) {
            $receiptFiles = array_slice($receiptFiles, 0, $maxFiles);
            session()->flash('payment_notify_max_exceeded', __('payment_notify.max_files_exceeded'));
        }
        if (count($receiptFiles) > 0) {
            $imageService = app(ImageConversionService::class);
            foreach ($receiptFiles as $file) {
                $stored = $imageService->storeForDisplay($file, 'order-files/'.$order->id, 'public');
                $order->files()->create([
                    'user_id' => $user->id,
                    'comment_id' => $comment->id,
                    'path' => $stored['path'],
                    'original_name' => $stored['original_name'],
                    'mime_type' => $stored['mime_type'],
                    'size' => $stored['size'],
                    'type' => 'receipt',
                ]);
            }
        }

        $order->timeline()->create([
            'user_id' => $user->id,
            'type' => 'payment',
            'body' => __('orders.timeline_payment_notify', ['amount' => $validated['transfer_amount']]),
        ]);

        return redirect()->route('orders.show', $order)
            ->with('success', __('orders.payment_notify_sent'));
    }

    /** Customer: cancel own order (only when pending or needs_payment) */
    public function cancelOrder(Request $request, Order $order)
    {
        $this->authorize('performCustomerAction', $order);

        $user = auth()->user();

        if (! $order->isCancellable()) {
            return redirect()->route('orders.show', $order)
                ->with('error', __('orders.cancel_not_allowed'));
        }

        $oldStatus = $order->status;
        $order->update([
            'status' => 'cancelled',
            'status_changed_at' => now(),
        ]);

        \App\Models\AdCampaign::incrementCancelledForOrder($order);

        $order->timeline()->create([
            'user_id' => $user->id,
            'type' => 'status_change',
            'status_from' => $oldStatus,
            'status_to' => 'cancelled',
        ]);

        return redirect()->route('orders.show', $order)
            ->with('success', __('orders.cancelled_by_customer'));
    }

    /** Customer: request merge with another of their own orders → posts a comment */
    public function customerMerge(CustomerMergeRequestRequest $request, Order $order)
    {
        $this->authorize('performCustomerAction', $order);

        $user = auth()->user();
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

        return redirect()->route('orders.show', $order)
            ->with('success', __('orders.customer_merge_sent'));
    }

    // ─── Staff quick actions ──────────────────────────────────────────────────

    /** Staff: transfer order ownership to another customer by email */
    public function transferOrder(TransferOrderRequest $request, Order $order)
    {
        $this->authorize('view-all-orders');

        $user = auth()->user();
        $order->load('user');
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

            return redirect()->route('orders.show', $order)
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

        return redirect()->route('orders.show', $order)
            ->with('success', __('orders.order_transferred'));
    }

    /** Staff: update tracking number and shipping company */
    public function updateShippingTracking(UpdateTrackingRequest $request, Order $order)
    {
        $this->authorize('view-all-orders');

        $user = auth()->user();
        $validated = $request->validated();

        $order->update($validated);

        if (! empty($validated['tracking_number'])) {
            $order->timeline()->create([
                'user_id' => $user->id,
                'type' => 'note',
                'body' => __('orders.timeline_tracking_updated', ['number' => $validated['tracking_number']]),
            ]);
        }

        return redirect()->route('orders.show', $order)
            ->with('success', __('orders.tracking_updated'));
    }

    /** Staff: record payment details (amount, date, method, receipt) */
    public function updatePayment(UpdatePaymentRequest $request, Order $order)
    {
        $this->authorize('view-all-orders');

        $user = auth()->user();
        $validated = $request->validated();

        $data = collect($validated)->except('payment_receipts')->toArray();

        $receiptFiles = $request->file('payment_receipts', []);

        if (count($receiptFiles) > 0) {
            $comment = $order->comments()->create([
                'user_id' => $user->id,
                'body' => __('orders.payment_receipt_comment'),
                'is_internal' => false,
            ]);

            $imageService = app(ImageConversionService::class);
            foreach ($receiptFiles as $file) {
                $stored = $imageService->storeForDisplay($file, 'order-files/'.$order->id, 'public');
                $order->files()->create([
                    'user_id' => $user->id,
                    'comment_id' => $comment->id,
                    'path' => $stored['path'],
                    'original_name' => $stored['original_name'],
                    'mime_type' => $stored['mime_type'],
                    'size' => $stored['size'],
                    'type' => 'receipt',
                ]);
            }
        }

        $order->update($data);

        $order->timeline()->create([
            'user_id' => $user->id,
            'type' => 'payment',
            'body' => __('orders.timeline_payment_updated', [
                'amount' => $validated['payment_amount'] ?? '—',
            ]),
        ]);

        return redirect()->route('orders.show', $order)
            ->with('success', __('orders.payment_updated'));
    }

    /** Staff: update internal notes about this order/customer */
    public function updateStaffNotes(UpdateStaffNotesRequest $request, Order $order)
    {
        $this->authorize('view-all-orders');

        $user = auth()->user();
        $validated = $request->validated();

        $order->update(['staff_notes' => $validated['staff_notes'] ?? null]);

        return redirect()->route('orders.show', $order)
            ->with('success', __('orders.staff_notes_saved'));
    }

    /** Staff or customer (if allowed): add files to an order item */
    public function storeItemFiles(StoreOrderItemFilesRequest $request, Order $order, int $itemId)
    {
        $this->authorize('addItemFiles', $order);

        $user = auth()->user();
        $item = OrderItem::where('order_id', $order->id)->findOrFail($itemId);
        $wantsJson = $request->expectsJson() || $request->ajax();

        $maxPerItem = max(1, (int) Setting::get('max_files_per_item_after_submit', 5));
        $currentCount = ($item->image_path ? 1 : 0) + $order->files()->where('order_item_id', $itemId)->count();
        $newCount = count($request->file('files', []));

        if ($currentCount + $newCount > $maxPerItem) {
            $msg = __('orders.item_file_limit_reached', ['max' => $maxPerItem]);

            return $wantsJson
                ? response()->json(['success' => false, 'message' => $msg], 422)
                : redirect()->route('orders.show', $order)->with('error', $msg);
        }

        try {
            foreach ($request->file('files', []) as $file) {
                $stored = app(\App\Services\ImageConversionService::class)->storeForDisplay($file, "orders/{$order->id}", 'public');
                $order->files()->create([
                    'order_id' => $order->id,
                    'order_item_id' => $item->id,
                    'user_id' => $user->id,
                    'comment_id' => null,
                    'path' => $stored['path'],
                    'original_name' => $stored['original_name'],
                    'mime_type' => $stored['mime_type'],
                    'size' => $stored['size'],
                    'type' => 'product_image',
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Order item file upload failed', ['order_id' => $order->id, 'item_id' => $itemId, 'error' => $e->getMessage()]);
            $msg = __('orders.item_files_upload_failed');

            return $wantsJson
                ? response()->json(['success' => false, 'message' => $msg], 500)
                : redirect()->route('orders.show', $order)->with('error', $msg);
        }

        $msg = __('orders.item_files_uploaded');

        return $wantsJson
            ? response()->json(['success' => true, 'message' => $msg])
            : redirect()->route('orders.show', $order)->with('success', $msg);
    }

    /** Staff: delete product image (from order_items.image_path or order_files) */
    public function deleteProductImage(Request $request, Order $order)
    {
        $this->authorize('view-all-orders');

        $user = auth()->user();
        $itemId = $request->input('item_id');
        $fileId = $request->input('file_id');

        if ($itemId) {
            $item = OrderItem::where('order_id', $order->id)->findOrFail($itemId);
            if ($item->image_path) {
                Storage::disk('public')->delete($item->image_path);
                $item->update(['image_path' => null]);
            }
        } elseif ($fileId) {
            $file = OrderFile::where('order_id', $order->id)
                ->where('type', 'product_image')
                ->findOrFail($fileId);
            Storage::disk('public')->delete($file->path);
            OrderItem::where('order_id', $order->id)
                ->where('image_path', $file->path)
                ->update(['image_path' => null]);
            $file->delete();
        } else {
            abort(400, __('orders.delete_image_param_required'));
        }

        return redirect()->route('orders.show', $order)
            ->with('success', __('orders.product_image_deleted'));
    }
}
