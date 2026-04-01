<?php

namespace App\Services;

use App\DTOs\OrderSubmissionData;
use App\DTOs\OrderSubmissionResult;
use App\Models\Activity;
use App\Models\AdCampaign;
use App\Models\Order;
use App\Models\OrderComment;
use App\Models\OrderFile;
use App\Models\OrderItem;
use App\Models\OrderTimeline;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Support\Facades\DB;

class OrderSubmissionService
{
    public function __construct(
        private ImageConversionService $imageConversionService,
    ) {}

    /**
     * Submit a new order or edit an existing one. Does not dispatch or redirect;
     * returns a result for the caller to handle.
     */
    public function submit(OrderSubmissionData $data): OrderSubmissionResult
    {
        if ($data->editingOrderId !== null) {
            return $this->submitEdit($data);
        }

        return $this->submitNew($data);
    }

    private function submitNew(OrderSubmissionData $data): OrderSubmissionResult
    {
        $hourlyLimit = $data->isStaff
            ? (int) Setting::get('orders_per_hour_admin', 50)
            : (int) Setting::get('orders_per_hour_customer', 30);

        if ($hourlyLimit > 0) {
            $hourlyCount = Order::where('user_id', $data->userId)
                ->where('created_at', '>=', now()->subHour())
                ->count();

            if ($hourlyCount >= $hourlyLimit) {
                return new OrderSubmissionResult(
                    success: false,
                    errorMessage: __('order.rate_limit_exceeded', ['max' => $hourlyLimit]),
                    errorType: 'notify',
                );
            }
        }

        $dayLimit = $data->isStaff
            ? (int) Setting::get('orders_per_day_staff', 100)
            : (int) Setting::get('orders_per_day_customer', 200);

        if ($dayLimit > 0) {
            $todayCount = Order::where('user_id', $data->userId)
                ->whereDate('created_at', today())
                ->count();

            if ($todayCount >= $dayLimit) {
                return new OrderSubmissionResult(
                    success: false,
                    errorMessage: __('order.daily_limit_reached', ['max' => $dayLimit]),
                    errorType: 'notify',
                );
            }
        }

        $monthLimit = $data->isStaff
            ? (int) Setting::get('orders_per_month_admin', 1000)
            : (int) Setting::get('orders_per_month_customer', 500);

        if ($monthLimit > 0) {
            $monthCount = Order::where('user_id', $data->userId)
                ->where('created_at', '>=', now()->startOfMonth())
                ->count();

            if ($monthCount >= $monthLimit) {
                return new OrderSubmissionResult(
                    success: false,
                    errorMessage: __('order.monthly_limit_reached', ['max' => $monthLimit]),
                    errorType: 'notify',
                );
            }
        }

        $createdOrder = null;

        DB::transaction(function () use ($data, &$createdOrder): void {
            $user = User::findOrFail($data->userId);
            $rates = $data->exchangeRates;

            $defaultAddress = $user->addresses()->where('is_default', true)->first();
            $addressSnapshot = $defaultAddress
                ? $defaultAddress->only([
                    'id', 'label', 'recipient_name', 'phone',
                    'country', 'city', 'address',
                ])
                : null;

            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => $data->userId,
                'status' => 'pending',
                'layout_option' => (string) (Setting::get('order_new_layout') ?? config('order.default_layout') ?? 'table'),
                'notes' => trim($data->orderNotes) !== '' ? trim($data->orderNotes) : null,
                'shipping_address_id' => $defaultAddress?->id,
                'shipping_address_snapshot' => $addressSnapshot,
                'subtotal' => 0,
                'total_amount' => 0,
                'currency' => 'SAR',
                'can_edit_until' => null,
            ]);

            $rawSubtotal = 0;

            foreach ($data->items as $sortOrder => $entry) {
                $item = $entry['data'];
                $origIndex = $entry['orig'];
                $price = is_numeric($item['price'] ?? null) ? (float) $item['price'] : null;
                $qty = max(1, (int) ($item['qty'] ?? 1));
                $curr = $item['currency'] ?? 'USD';
                $rate = $rates[$curr] ?? 0;

                if ($price !== null && $rate > 0) {
                    $rawSubtotal += $price * $qty * $rate;
                }

                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'url' => $item['url'] ?? '',
                    'is_url' => safe_item_url($item['url'] ?? '') !== null,
                    'source_host' => order_item_source_host($item['url'] ?? null),
                    'qty' => $qty,
                    'color' => ($item['color'] ?? '') !== '' ? $item['color'] : null,
                    'size' => ($item['size'] ?? '') !== '' ? $item['size'] : null,
                    'notes' => ($item['notes'] ?? '') !== '' ? $item['notes'] : null,
                    'currency' => $curr,
                    'unit_price' => $price,
                    'sort_order' => $sortOrder,
                ]);

                $files = $data->normalizedFiles[$origIndex] ?? [];
                $firstPath = null;
                foreach ($files as $file) {
                    if (! $file) {
                        continue;
                    }
                    $stored = $this->imageConversionService->storeForDisplay($file, "orders/{$order->id}", 'public');
                    if ($firstPath === null) {
                        $firstPath = $stored['path'];
                        $orderItem->update(['image_path' => $stored['path']]);
                    }
                    OrderFile::create([
                        'order_id' => $order->id,
                        'order_item_id' => $orderItem->id,
                        'user_id' => $data->userId,
                        'path' => $stored['path'],
                        'original_name' => $stored['original_name'],
                        'mime_type' => $stored['mime_type'],
                        'size' => $stored['size'],
                        'type' => 'product_image',
                    ]);
                }
            }

            $commission = CommissionCalculator::calculate($rawSubtotal);
            $order->update([
                'subtotal' => round($rawSubtotal, 2),
                'total_amount' => round($rawSubtotal + $commission, 2),
            ]);

            OrderTimeline::create([
                'order_id' => $order->id,
                'user_id' => $data->userId,
                'type' => 'status_change',
                'status_to' => 'pending',
            ]);

            $createdOrder = $order;
        });

        if ($createdOrder === null) {
            return new OrderSubmissionResult(
                success: false,
                errorMessage: __('orders.submission_failed'),
                errorType: 'notify',
            );
        }

        $this->insertSystemComment($createdOrder);

        Activity::create([
            'type' => 'new_order',
            'subject_type' => Order::class,
            'subject_id' => $createdOrder->id,
            'causer_id' => $data->userId,
            'data' => [
                'order_number' => $createdOrder->order_number,
                'note' => null,
            ],
            'created_at' => now(),
        ]);

        $user = User::find($data->userId);
        $campaignId = $user?->ad_campaign_id;
        if ($campaignId !== null) {
            AdCampaign::where('id', $campaignId)->increment('order_count');
        }

        if ($data->request !== null) {
            UserActivityLog::fromRequest($data->request, [
                'user_id' => $data->userId,
                'subject_type' => Order::class,
                'subject_id' => $createdOrder->id,
                'event' => 'order_created',
                'properties' => [
                    'order_number' => $createdOrder->order_number,
                    'total_amount' => $createdOrder->total_amount,
                ],
            ]);
        }

        $enabled = (bool) Setting::get('order_success_screen_enabled', true);
        $threshold = max(0, (int) Setting::get('order_success_screen_threshold', 10));
        $totalOrders = Order::where('user_id', $data->userId)->count();
        $showSuccessPage = $enabled && $totalOrders <= $threshold;

        if ($showSuccessPage) {
            return new OrderSubmissionResult(
                success: true,
                orderId: $createdOrder->id,
                order: $createdOrder,
                redirectUrl: route('orders.success', $createdOrder),
                redirectToSuccessPage: true,
                sessionFlashes: [],
            );
        }

        return new OrderSubmissionResult(
            success: true,
            orderId: $createdOrder->id,
            order: $createdOrder,
            redirectUrl: route('orders.show', $createdOrder),
            redirectToSuccessPage: false,
            sessionFlashes: [
                'order_created' => true,
                'success' => __('order.created_successfully', ['number' => $createdOrder->order_number]),
            ],
        );
    }

    private function submitEdit(OrderSubmissionData $data): OrderSubmissionResult
    {
        $order = Order::with('items')->find($data->editingOrderId);

        if ($order === null || $order->user_id !== $data->userId) {
            return new OrderSubmissionResult(
                success: false,
                errorMessage: __('orders.edit_forbidden'),
                errorType: 'notify',
            );
        }

        if ($order->is_paid) {
            return new OrderSubmissionResult(
                success: false,
                errorMessage: __('orders.edit_already_paid'),
                errorType: 'notify',
            );
        }

        if ($order->can_edit_until === null || now()->gte($order->can_edit_until)) {
            return new OrderSubmissionResult(
                success: false,
                errorMessage: __('orders.edit_resubmit_window_expired'),
                errorType: 'notify',
            );
        }

        if (empty($data->items)) {
            return new OrderSubmissionResult(
                success: false,
                errorMessage: __('orders.edit_empty_items_rejected'),
                errorType: 'notify',
            );
        }

        $hasMeaningfulItem = collect($data->items)->contains(function ($entry) {
            $item = $entry['data'];
            $url = trim($item['url'] ?? '');
            $price = $item['price'] ?? null;

            return $url !== '' || (is_numeric($price) && (float) $price > 0);
        });
        if (! $hasMeaningfulItem) {
            return new OrderSubmissionResult(
                success: false,
                errorMessage: __('orders.edit_empty_items_rejected'),
                errorType: 'notify',
            );
        }

        $rates = $data->exchangeRates;

        DB::transaction(function () use ($order, $data, $rates): void {
            $order->items()->delete();

            $rawSubtotal = 0;

            foreach ($data->items as $sortOrder => $entry) {
                $item = $entry['data'];
                $origIndex = $entry['orig'];
                $price = is_numeric($item['price'] ?? null) ? (float) $item['price'] : null;
                $qty = max(1, (int) ($item['qty'] ?? 1));
                $curr = $item['currency'] ?? 'USD';
                $rate = $rates[$curr] ?? 0;

                if ($price !== null && $rate > 0) {
                    $rawSubtotal += $price * $qty * $rate;
                }

                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'url' => $item['url'] ?? '',
                    'is_url' => safe_item_url($item['url'] ?? '') !== null,
                    'source_host' => order_item_source_host($item['url'] ?? null),
                    'qty' => $qty,
                    'color' => ($item['color'] ?? '') !== '' ? $item['color'] : null,
                    'size' => ($item['size'] ?? '') !== '' ? $item['size'] : null,
                    'notes' => ($item['notes'] ?? '') !== '' ? $item['notes'] : null,
                    'currency' => $curr,
                    'unit_price' => $price,
                    'sort_order' => $sortOrder,
                ]);

                $files = $data->normalizedFiles[$origIndex] ?? [];
                $firstPath = null;
                foreach ($files as $file) {
                    if (! $file) {
                        continue;
                    }
                    $stored = $this->imageConversionService->storeForDisplay($file, "orders/{$order->id}", 'public');
                    if ($firstPath === null) {
                        $firstPath = $stored['path'];
                        $orderItem->update(['image_path' => $stored['path']]);
                    }
                    OrderFile::create([
                        'order_id' => $order->id,
                        'order_item_id' => $orderItem->id,
                        'user_id' => $data->userId,
                        'path' => $stored['path'],
                        'original_name' => $stored['original_name'],
                        'mime_type' => $stored['mime_type'],
                        'size' => $stored['size'],
                        'type' => 'product_image',
                    ]);
                }
            }

            $commission = CommissionCalculator::calculate($rawSubtotal);
            $order->update([
                'notes' => trim($data->orderNotes) !== '' ? trim($data->orderNotes) : null,
                'subtotal' => round($rawSubtotal, 2),
                'total_amount' => round($rawSubtotal + $commission, 2),
                'can_edit_until' => null,
            ]);

            OrderTimeline::create([
                'order_id' => $order->id,
                'user_id' => $data->userId,
                'type' => 'note',
                'body' => __('orders.timeline_items_edited'),
            ]);

            OrderComment::create([
                'order_id' => $order->id,
                'user_id' => null,
                'body' => __('orders.edit_system_comment'),
                'is_system' => true,
            ]);
        });

        $order->refresh();

        return new OrderSubmissionResult(
            success: true,
            orderId: $order->id,
            order: $order,
            redirectUrl: route('orders.show', $order),
            redirectToSuccessPage: false,
            sessionFlashes: [
                'success' => __('orders.edit_saved_successfully', ['number' => $order->order_number]),
            ],
        );
    }

    private function generateOrderNumber(): string
    {
        $query = Order::query()->lockForUpdate();

        if (DB::getDriverName() === 'mysql') {
            $max = (int) $query
                ->whereRaw("order_number REGEXP '^[0-9]+$'")
                ->max(DB::raw('CAST(order_number AS UNSIGNED)'));
        } else {
            $max = (int) $query
                ->whereRaw("order_number GLOB '[0-9]*'")
                ->max(DB::raw('CAST(order_number AS INTEGER)'));
        }

        return (string) ($max + 1);
    }

    private function insertSystemComment(Order $order): void
    {
        $hasPrices = $order->subtotal > 0;

        $siteName = Setting::siteName(app()->getLocale());
        $whatsapp = Setting::get('whatsapp', '');
        $whatsappDisplay = $whatsapp !== '' ? $whatsapp : '-';
        $companyName = Setting::get('payment_company_name') ?: $siteName;
        $baseUrl = rtrim(config('app.url'), '/');

        $replacements = [
            'subtotal' => number_format($order->subtotal, 0, '.', ','),
            'commission' => number_format(max(0, (float) $order->total_amount - (float) $order->subtotal), 0, '.', ','),
            'total' => number_format($order->total_amount, 0, '.', ','),
            'site_name' => $siteName,
            'whatsapp' => $whatsappDisplay,
            'company_name' => $companyName,
            'payment_url' => $baseUrl.'/payment-methods',
            'terms_url' => $baseUrl.'/terms-and-conditions',
            'faq_url' => $baseUrl.'/faq',
            'shipping_url' => $baseUrl.'/shipping-calculator',
        ];

        if ($hasPrices) {
            $template = Setting::get('auto_comment_with_price', '');
            $body = $template !== ''
                ? str_replace(array_map(fn ($k) => ':'.$k, array_keys($replacements)), array_values($replacements), $template)
                : __('orders.auto_comment_with_price', $replacements);
        } else {
            $template = Setting::get('auto_comment_no_price', '');
            $body = $template !== ''
                ? str_replace(array_map(fn ($k) => ':'.$k, array_keys($replacements)), array_values($replacements), $template)
                : __('orders.auto_comment_no_price', ['whatsapp' => $whatsappDisplay]);
        }

        OrderComment::create([
            'order_id' => $order->id,
            'user_id' => null,
            'body' => $body,
            'is_system' => true,
        ]);
    }
}
