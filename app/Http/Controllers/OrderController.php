<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceType;
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
use App\Services\CommissionCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
                ->when(auth()->user()?->hasAnyRole(['staff', 'admin', 'superadmin']), fn ($q) => $q->withTrashed())
                ->orderBy('created_at'),
        ])->findOrFail($id);

        $this->authorize('view', $order);

        $isOwner = $order->user_id === $user->id;
        $isStaff = $user->hasAnyRole(['staff', 'admin', 'superadmin']);

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

        $invoiceDefaults = $this->invoiceDefaultsForOrder($order);
        $commissionSettings = CommissionCalculator::getSettings();

        return view('orders.show', compact(
            'order', 'isOwner', 'isStaff', 'orderEditEnabled', 'canEditItems', 'clickEditRemaining', 'recentOrders', 'customerRecentOrders', 'orderCreationLog', 'showCommentsDiscovery', 'invoiceDefaults', 'commissionSettings'
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

        $invoiceType = $validated['invoice_type'] ?? InvoiceType::FirstPayment->value;
        $notes = $validated['custom_notes'] ?? '';
        $showOriginalCurrency = filter_var($validated['show_original_currency'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $invoiceLanguage = $validated['invoice_language'] ?? $order->user?->locale ?? config('app.locale', 'ar');
        if (! in_array($invoiceLanguage, ['ar', 'en', 'both'], true)) {
            $invoiceLanguage = 'ar';
        }

        // Auto-fill first_agent_fee when not overridden (First Payment only)
        if ($invoiceType === InvoiceType::FirstPayment->value) {
            $firstItemsTotal = (float) ($validated['first_items_total'] ?? 0);
            $firstAgentFee = (float) ($validated['first_agent_fee'] ?? 0);
            $overridden = filter_var($validated['first_commission_overridden'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if ($firstItemsTotal > 0 && ! $overridden && $firstAgentFee <= 0) {
                $validated['first_agent_fee'] = CommissionCalculator::calculate($firstItemsTotal);
            }
        }

        $extra = $this->buildInvoiceExtra($validated, $invoiceType);

        $invoiceCount = $order->files()->where('type', 'invoice')->count() + 1;
        $filename = $this->resolveInvoiceFilename(
            $order,
            $invoiceType,
            $invoiceCount,
            $validated['custom_filename'] ?? null
        );

        $settings = $this->invoiceSettings();

        try {
            $pdfContent = $this->buildInvoicePdf(
                $order,
                $validated,
                $invoiceType,
                $notes,
                $filename,
                $extra,
                $settings,
                $invoiceLanguage,
                $showOriginalCurrency
            );
        } catch (\Throwable $e) {
            Log::error('Invoice PDF generation failed', [
                'order_id' => $order->id,
                'invoice_type' => $invoiceType,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('orders.show', $id)
                ->with('error', __('orders.invoice_generation_failed'));
        }

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

        $firstLocale = $invoiceLanguage === 'both' ? 'ar' : $invoiceLanguage;
        [, $total] = $this->buildInvoiceLinesForLocale($validated, $order, $invoiceType, $showOriginalCurrency, $firstLocale, $extra);

        $commentBody = $this->resolveInvoiceCommentMessage(
            $validated['comment_message'] ?? '',
            $total,
            $order,
            $invoiceType,
            $validated
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

        if (in_array($invoiceType, [InvoiceType::FirstPayment->value, InvoiceType::SecondFinal->value], true)) {
            $updates = [];
            if ($invoiceType === InvoiceType::FirstPayment->value && isset($validated['first_agent_fee'])) {
                $v = (float) $validated['first_agent_fee'];
                if ($v > 0) {
                    $updates['agent_fee'] = $v;
                }
            }
            if ($invoiceType === InvoiceType::SecondFinal->value) {
                if (isset($validated['second_agent_fee'])) {
                    $v = (float) $validated['second_agent_fee'];
                    if ($v > 0) {
                        $updates['agent_fee'] = $v;
                    }
                }
                if (isset($validated['second_shipping_cost'])) {
                    $v = (float) $validated['second_shipping_cost'];
                    if ($v > 0) {
                        $updates['international_shipping'] = $v;
                    }
                }
            }
            if (! empty($updates)) {
                $order->update($updates);
            }
        }

        return redirect()->route('orders.show', $id)->with('success', __('orders.invoice_generated'));
    }

    /** @return array<string, mixed> */
    private function invoiceSettings(): array
    {
        $siteName = Setting::get('site_name') ?: config('app.name');
        $invoiceSiteName = trim((string) Setting::get('invoice_site_name', ''));
        $invoiceLogo = Setting::get('invoice_logo', '');
        $logoImage = Setting::get('logo_image', '');
        $rawLogo = $invoiceLogo ?: $logoImage;
        $logoPath = is_array($rawLogo) ? ($rawLogo[0] ?? '') : (string) $rawLogo;

        $fullLogoPath = null;
        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            $fullLogoPath = Storage::disk('public')->path($logoPath);
        }

        $invoiceCustomLines = Setting::get('invoice_custom_lines', []);
        $customLines = is_array($invoiceCustomLines) ? $invoiceCustomLines : [];

        return [
            'site_name' => $invoiceSiteName !== '' ? $invoiceSiteName : $siteName,
            'logo_path' => $fullLogoPath,
            'logo_url' => $logoPath ? Storage::disk('public')->url($logoPath) : null,
            'show_order_number' => (bool) Setting::get('invoice_show_order_number', true),
            'show_customer_name' => (bool) Setting::get('invoice_show_customer_name', true),
            'show_email' => (bool) Setting::get('invoice_show_email', true),
            'show_phone' => (bool) Setting::get('invoice_show_phone', true),
            'invoice_greeting' => trim((string) Setting::get('invoice_greeting', '')),
            'invoice_confirmation' => trim((string) Setting::get('invoice_confirmation', '')),
            'invoice_payment_instructions' => trim((string) Setting::get('invoice_payment_instructions', '')),
            'invoice_footer_text' => trim((string) Setting::get('invoice_footer_text', '')),
            'invoice_show_order_items' => (bool) Setting::get('invoice_show_order_items', false),
            'invoice_custom_lines' => $customLines,
            'primary_color' => trim((string) Setting::get('primary_color', '#f97316')) ?: '#f97316',
        ];
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

    /** @return array<string, mixed> */
    private function buildInvoiceExtra(array $validated, string $invoiceType): array
    {
        $extra = [];
        if ($invoiceType === InvoiceType::SecondFinal->value) {
            $productValue = (float) ($validated['second_product_value'] ?? 0);
            $agentFee = (float) ($validated['second_agent_fee'] ?? 0);
            $shippingCost = (float) ($validated['second_shipping_cost'] ?? 0);
            $firstPayment = (float) ($validated['second_first_payment'] ?? 0);
            $remaining = (float) ($validated['second_remaining'] ?? 0);
            $total = $productValue + $agentFee + $shippingCost;
            $extra['weight'] = $validated['second_weight'] ?? '';
            $extra['shipping_company'] = $validated['second_shipping_company'] ?? '';
            $extra['first_payment'] = $firstPayment;
            $extra['remaining'] = $remaining > 0 ? $remaining : max(0, $total - $firstPayment);

            $showOrderItems = isset($validated['show_order_items'])
                ? filter_var($validated['show_order_items'], FILTER_VALIDATE_BOOLEAN)
                : (bool) Setting::get('invoice_show_order_items', false);
            $extra['show_order_items'] = $showOrderItems;

            $formCustomLines = $validated['custom_lines'] ?? null;
            if (is_array($formCustomLines) && count($formCustomLines) > 0) {
                $lines = [];
                foreach ($formCustomLines as $cl) {
                    $label = trim($cl['label'] ?? '');
                    $amount = (float) ($cl['amount'] ?? 0);
                    $visible = isset($cl['visible']) ? filter_var($cl['visible'], FILTER_VALIDATE_BOOLEAN) : true;
                    if ($label !== '' || $amount > 0) {
                        $lines[] = ['label' => $label, 'amount' => $amount, 'visible' => $visible];
                    }
                }
                $extra['custom_lines'] = $lines;
            } else {
                $settingsLines = Setting::get('invoice_custom_lines', []);
                $extra['custom_lines'] = is_array($settingsLines) ? $settingsLines : [];
            }
        }

        return $extra;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  array<string, mixed>  $extra
     * @param  array<string, mixed>  $settings
     */
    private function buildInvoiceLinesForLocale(array $validated, Order $order, string $invoiceType, bool $showOriginalCurrency, string $locale, array $extra = []): array
    {
        $originalLocale = app()->getLocale();
        app()->setLocale($locale);

        try {
            $lines = [];
            $total = 0.0;

            switch ($invoiceType) {
                case InvoiceType::FirstPayment->value:
                    $itemsTotal = (float) ($validated['first_items_total'] ?? 0);
                    $agentFee = (float) ($validated['first_agent_fee'] ?? 0);
                    $lines[] = ['description' => __('orders.invoice_items_total'), 'qty' => 1, 'unit_price' => number_format($itemsTotal, 2), 'line_total' => $itemsTotal, 'is_fee' => false];
                    if ($agentFee > 0) {
                        $lines[] = ['description' => __('orders.fee_agent_fee'), 'qty' => 1, 'unit_price' => number_format($agentFee, 2), 'line_total' => $agentFee, 'is_fee' => true];
                    }
                    $otherLabel = trim((string) ($validated['first_other_label'] ?? ''));
                    $otherAmount = (float) ($validated['first_other_amount'] ?? 0);
                    if ($otherAmount > 0) {
                        $lines[] = ['description' => $otherLabel !== '' ? $otherLabel : __('orders.invoice_other'), 'qty' => 1, 'unit_price' => number_format($otherAmount, 2), 'line_total' => $otherAmount, 'is_fee' => true];
                    }
                    $extras = $validated['first_extras'] ?? [];
                    foreach (is_array($extras) ? $extras : [] as $e) {
                        $label = trim($e['label'] ?? '');
                        $amt = (float) ($e['amount'] ?? 0);
                        if ($label !== '' && $amt > 0) {
                            $lines[] = ['description' => $label, 'qty' => 1, 'unit_price' => number_format($amt, 2), 'line_total' => $amt, 'is_fee' => true];
                        }
                    }
                    $total = array_sum(array_column($lines, 'line_total'));
                    if (filter_var($validated['first_total_overridden'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                        $overrideTotal = (float) ($validated['first_total'] ?? 0);
                        if ($overrideTotal > 0) {
                            $total = $overrideTotal;
                        }
                    }
                    break;

                case InvoiceType::SecondFinal->value:
                    $productValue = (float) ($validated['second_product_value'] ?? 0);
                    $agentFee = (float) ($validated['second_agent_fee'] ?? 0);
                    $shippingCost = (float) ($validated['second_shipping_cost'] ?? 0);
                    $showOrderItems = (bool) ($extra['show_order_items'] ?? false);
                    $customLines = $extra['custom_lines'] ?? [];
                    $customLines = is_array($customLines) ? $customLines : [];

                    if ($productValue > 0) {
                        $lines[] = ['description' => __('orders.invoice_product_value'), 'qty' => 1, 'unit_price' => number_format($productValue, 2), 'line_total' => $productValue, 'is_fee' => false, 'is_sub_item' => false];
                        if ($showOrderItems && $order->items->isNotEmpty()) {
                            foreach ($order->items as $item) {
                                $priceSar = (float) ($item->final_price ?? $item->unit_price ?? 0);
                                $lineTotalSar = $priceSar * ($item->qty ?? 1);
                                if ($lineTotalSar > 0) {
                                    $desc = $item->url
                                        ? (strlen($item->url) > 60 ? substr($item->url, 0, 57).'...' : $item->url)
                                        : ($item->notes ?: __('orders.item').' #'.(count($lines) + 1));
                                    $lines[] = [
                                        'description' => $desc,
                                        'qty' => $item->qty,
                                        'unit_price' => number_format($priceSar, 2),
                                        'line_total' => $lineTotalSar,
                                        'is_fee' => false,
                                        'is_sub_item' => true,
                                    ];
                                }
                            }
                        }
                    }
                    foreach ($customLines as $cl) {
                        $visible = isset($cl['visible']) ? filter_var($cl['visible'], FILTER_VALIDATE_BOOLEAN) : true;
                        if (! $visible) {
                            continue;
                        }
                        $label = trim($cl['label'] ?? '');
                        $amt = (float) ($cl['amount'] ?? 0);
                        if ($label !== '' || $amt > 0) {
                            $lines[] = ['description' => $label ?: __('orders.invoice_line_item'), 'qty' => 1, 'unit_price' => number_format($amt, 2), 'line_total' => $amt, 'is_fee' => true, 'is_sub_item' => false];
                        }
                    }
                    if ($agentFee > 0) {
                        $lines[] = ['description' => __('orders.fee_agent_fee'), 'qty' => 1, 'unit_price' => number_format($agentFee, 2), 'line_total' => $agentFee, 'is_fee' => true, 'is_sub_item' => false];
                    }
                    if ($shippingCost > 0) {
                        $lines[] = ['description' => __('orders.invoice_shipping_cost'), 'qty' => 1, 'unit_price' => number_format($shippingCost, 2), 'line_total' => $shippingCost, 'is_fee' => true, 'is_sub_item' => false];
                    }
                    $total = $productValue + $agentFee + $shippingCost;
                    foreach ($customLines as $cl) {
                        $visible = isset($cl['visible']) ? filter_var($cl['visible'], FILTER_VALIDATE_BOOLEAN) : true;
                        if ($visible) {
                            $total += (float) ($cl['amount'] ?? 0);
                        }
                    }
                    break;

                case InvoiceType::ItemsCost->value:
                    $formItems = $validated['items'] ?? [];
                    if (! empty($formItems)) {
                        foreach ($formItems as $it) {
                            $desc = trim($it['description'] ?? '');
                            $qty = (int) ($it['qty'] ?? 1) ?: 1;
                            $unitPrice = (float) ($it['unit_price'] ?? 0);
                            $currency = trim($it['currency'] ?? 'SAR') ?: 'SAR';
                            $lineTotal = $unitPrice * $qty;
                            if ($lineTotal > 0 || $desc !== '') {
                                $lines[] = [
                                    'description' => $desc ?: __('orders.item').' #'.(count($lines) + 1),
                                    'qty' => $qty,
                                    'unit_price' => number_format($unitPrice, 2),
                                    'unit_price_original' => $unitPrice,
                                    'currency' => $currency,
                                    'line_total' => $lineTotal,
                                    'show_original' => $showOriginalCurrency && $currency !== 'SAR' && $unitPrice > 0,
                                    'is_fee' => false,
                                ];
                            }
                        }
                    } else {
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
                                    'is_fee' => false,
                                ];
                            }
                        }
                    }
                    $total = array_sum(array_column($lines, 'line_total'));
                    break;

                case InvoiceType::General->value:
                    $generalLines = $validated['general_lines'] ?? [];
                    foreach (is_array($generalLines) ? $generalLines : [] as $gl) {
                        $label = trim($gl['label'] ?? '');
                        $amt = (float) ($gl['amount'] ?? 0);
                        if ($label !== '' || $amt > 0) {
                            $lines[] = ['description' => $label ?: __('orders.invoice_line_item'), 'qty' => 1, 'unit_price' => number_format($amt, 2), 'line_total' => $amt, 'is_fee' => false];
                        }
                    }
                    $total = array_sum(array_column($lines, 'line_total'));
                    break;

                default:
                    $total = (float) ($validated['custom_amount'] ?? 0);
                    if ($total > 0) {
                        $lines[] = ['description' => __('orders.invoice_total'), 'qty' => 1, 'unit_price' => number_format($total, 2), 'line_total' => $total, 'is_fee' => false];
                    }
            }

            return [$lines, $total];
        } finally {
            app()->setLocale($originalLocale);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  array<string, mixed>  $extra
     * @param  array<string, mixed>  $settings
     */
    private function buildInvoicePdf(
        Order $order,
        array $validated,
        string $invoiceType,
        string $notes,
        string $filename,
        array $extra,
        array $settings,
        string $invoiceLanguage,
        bool $showOriginalCurrency
    ): string {
        $locales = $invoiceLanguage === 'both' ? ['ar', 'en'] : [$invoiceLanguage];
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
            'default_font' => 'dejavusans',
            'autoScriptToLang' => false,
            'autoLangToFont' => false,
        ]);

        $firstLocale = $locales[0];
        $mpdf->SetTitle($firstLocale === 'ar' ? 'فاتورة رقم '.$order->order_number : 'Invoice '.$order->order_number);

        foreach ($locales as $idx => $locale) {
            if ($idx > 0) {
                $mpdf->AddPage();
            }

            $isRtl = $locale === 'ar';
            $mpdf->SetDirectionality($isRtl ? 'rtl' : 'ltr');

            [$lines, $total] = $this->buildInvoiceLinesForLocale($validated, $order, $invoiceType, $showOriginalCurrency, $locale, $extra);

            $html = view('orders.invoice-pdf-mpdf', [
                'order' => $order,
                'lines' => $lines,
                'total' => $total,
                'invoiceType' => $invoiceType,
                'notes' => $notes,
                'isRtl' => $isRtl,
                'invoiceLocale' => $locale,
                'extra' => $extra,
                'settings' => $settings,
            ])->render();

            $mpdf->WriteHTML($html);
        }

        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    private function resolveInvoiceFilename(Order $order, string $invoiceType, int $invoiceCount, ?string $customOverride): string
    {
        $replacements = [
            '{order_number}' => $order->order_number,
            '{date}' => now()->format('Y-m-d'),
            '{type}' => $invoiceType,
            '{site_name}' => $this->sanitizeFilenamePart(Setting::get('site_name') ?: config('app.name')),
            '{count}' => (string) $invoiceCount,
        ];

        $pattern = $customOverride;
        if (trim((string) $pattern) === '') {
            $pattern = trim((string) Setting::get('invoice_filename_pattern', ''));
        }
        if (trim($pattern) === '') {
            $pattern = $invoiceCount > 1
                ? 'Invoice-{order_number}-{count}.pdf'
                : 'Invoice-{order_number}.pdf';
        }

        $filename = str_replace(array_keys($replacements), array_values($replacements), $pattern);
        $filename = $this->sanitizeFilename($filename);

        return $filename !== '' ? $filename : 'Invoice-'.$order->order_number.'.pdf';
    }

    private function sanitizeFilename(string $filename): string
    {
        $filename = preg_replace('/[^\p{L}\p{N}\s_\-\.\(\)]/u', '', $filename) ?? $filename;
        $filename = preg_replace('/\.\.+/', '.', $filename) ?? $filename;
        $filename = trim($filename, " \t\n\r\0\x0B/\\");

        return str_ends_with(strtolower($filename), '.pdf') ? $filename : $filename.'.pdf';
    }

    private function sanitizeFilenamePart(string $value): string
    {
        return preg_replace('/[^\p{L}\p{N}\s_\-]/u', '', $value) ?? $value;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveInvoiceCommentMessage(string $message, float $total, Order $order, string $invoiceType, array $validated = []): string
    {
        $text = trim($message);

        if ($text === '' && $invoiceType === InvoiceType::FirstPayment->value) {
            $template = trim((string) Setting::get('invoice_first_payment_comment_template', ''));
            if ($template === '') {
                $template = __('orders.invoice_first_payment_comment_default');
            }
            $subtotal = (float) ($validated['first_items_total'] ?? 0);
            $commission = (float) ($validated['first_agent_fee'] ?? 0);
            $whatsappDisplay = Setting::get('whatsapp', '') ?: '-';
            $replacements = [
                ':subtotal' => number_format($subtotal, 0, '.', ','),
                ':commission' => number_format($commission, 0, '.', ','),
                ':total' => number_format($total, 0, '.', ','),
                ':site_name' => Setting::get('site_name') ?: config('app.name'),
                ':whatsapp' => $whatsappDisplay,
            ];
            $text = str_replace(array_keys($replacements), array_values($replacements), $template);
        } elseif ($text === '') {
            $default = Setting::get('invoice_comment_default') ?: __('orders.invoice_attached');
            $text = $default;
        }

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
        $isStaff = $user->hasAnyRole(['staff', 'admin', 'superadmin']);

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

        if ($user->hasAnyRole(['staff', 'admin', 'superadmin'])) {
            if ($request->get('export') === 'csv' && $user->can('export-csv')) {
                return $this->exportCsv($request);
            }

            return $this->staffIndex($request);
        }

        return $this->customerIndex($user, $request);
    }

    public function indexVariant(Request $request, string $variant)
    {
        $user = auth()->user();
        if ($user->hasAnyRole(['staff', 'admin', 'superadmin'])) {
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
            'shipped' => Order::where('user_id', $user->id)
                ->where('status', 'shipped')
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

        if (! $staff->hasAnyRole(['staff', 'admin', 'superadmin'])) {
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

        if (! $user->hasAnyRole(['staff', 'admin', 'superadmin'])) {
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

        if (! $user->hasAnyRole(['staff', 'admin', 'superadmin'])) {
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

        if (! $user->hasAnyRole(['staff', 'admin', 'superadmin'])) {
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

        if (! $user->hasAnyRole(['staff', 'admin', 'superadmin'])) {
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
        if (! $user->hasAnyRole(['staff', 'admin', 'superadmin'])) {
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
        if (! $user->hasAnyRole(['staff', 'admin', 'superadmin'])) {
            abort(403);
        }

        $order = Order::with(['items' => fn ($q) => $q->orderBy('sort_order'), 'files'])->findOrFail($id);
        $this->authorize('view', $order);

        $filename = 'order-'.$order->order_number.'-'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(new OrderExport($order), $filename, \Maatwebsite\Excel\Excel::XLSX);
    }
}
